<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/core/bootstrap.php';
require __DIR__ . '/support/HttpTest.php';

use App\Core\Database;
use App\Models\User;

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8087', '/');
$url = parse_url($base);
if ($url === false || !in_array($url['scheme'] ?? '', ['http','https'], true)
    || !in_array($url['host'] ?? '', ['127.0.0.1','localhost','[::1]'], true)
    || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
    echo "ERROR: utiliza una URL HTTP local de desarrollo.\n";
    exit(2);
}

function fixture(PDO $pdo, string $sql, array $parameters): int
{
    $pdo->prepare($sql)->execute($parameters);
    return (int) $pdo->lastInsertId();
}

function document(string $html): DOMXPath
{
    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return new DOMXPath($dom);
}

$suffix = bin2hex(random_bytes(12));
$emails = ['dashboard-a-' . $suffix . '@example.invalid', 'dashboard-b-' . $suffix . '@example.invalid'];
$password = 'Temporal-' . bin2hex(random_bytes(16));
$users = [];
$procedures = [];
$pdo = null;
$browser = new TestBrowser();
$other = new TestBrowser();
$failure = null;

try {
    $pdo = Database::connection();
    [$status] = $browser->request($base, '/dashboard');
    ensure($status === 303 && $browser->headerContains('/login'), 'El dashboard exige sesion.');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $model = new User();
    $users[] = $model->create('Usuario A', 'Temporal', $emails[0], $hash);
    $users[] = $model->create('Usuario B', 'Temporal', $emails[1], $hash);
    foreach($users as $id){$pdo->prepare("UPDATE usuarios SET plan_id=(SELECT id FROM planes WHERE nombre='premium') WHERE id=?")->execute([$id]);}
    [, $html] = $browser->request($base, '/login');
    [$status] = $browser->request($base, '/login', ['_token' => token($html), 'email' => $emails[0], 'password' => $password]);
    ensure($status === 303 && $browser->headerContains('/dashboard'), 'El login abre el dashboard.');
    [$status, $html] = $browser->request($base, '/dashboard');
    ensure($status === 200 && strpos($html, 'Hola, Usuario A.') !== false, 'El saludo pertenece a la cuenta autenticada.');
    $xpath = document($html);
    $zeros = [];
    foreach ($xpath->query('//div[@class="dashboard-stats"]/div/strong') as $node) {
        $zeros[] = trim($node->textContent);
    }
    ensure($zeros === ['0','0','0'] && strpos($html, 'dashboard-list') === false, 'Una cuenta nueva muestra ceros y estados vacios reales.');
    ensure($xpath->query('//input[@id="dashboard-search" and @name="q" and not(@disabled)]')->length === 1, 'El buscador real está habilitado.');

    $category = (int) $pdo->query('SELECT id FROM categorias ORDER BY id LIMIT 1')->fetchColumn();
    $names = [
        'active' => 'Visible <script>alert(1)</script> ' . $suffix,
        'done' => 'Terminado ' . $suffix,
        'paused' => 'Pausado ' . $suffix,
        'other' => 'Ajeno ' . $suffix,
        'draft' => 'Borrador ' . $suffix,
        'inactive' => 'Inactivo ' . $suffix,
    ];
    foreach ($names as $key => $name) {
        $state = $key === 'draft' ? 'borrador' : ($key === 'inactive' ? 'inactivo' : 'publicado');
        $procedures[$key] = fixture($pdo, 'INSERT INTO tramites (nombre,slug,categoria_id,estado) VALUES (?,?,?,?)', [$name,'dashboard-test-' . $key . '-' . $suffix,$category,$state]);
    }
    foreach (['active','draft','inactive'] as $key) {
        fixture($pdo, 'INSERT INTO favoritos (usuario_id,tramite_id) VALUES (?,?)', [$users[0],$procedures[$key]]);
    }
    foreach (['active' => 'en_curso', 'done' => 'completado', 'paused' => 'pausado', 'draft' => 'en_curso', 'inactive' => 'en_curso'] as $key => $state) {
        $day = ['active' => '02', 'done' => '03', 'paused' => '04', 'draft' => '05', 'inactive' => '06'][$key];
        $updated = '2026-01-' . $day . ' 14:00:00';
        fixture($pdo, "INSERT INTO progreso_tramites (usuario_id,tramite_id,estado,fecha_inicio,fecha_actualizacion,fecha_finalizacion) VALUES (?,?,?,'2026-01-01 00:00:00',?,?)", [$users[0],$procedures[$key],$state,$updated,$state === 'completado' ? $updated : null]);
    }
    fixture($pdo, 'INSERT INTO favoritos (usuario_id,tramite_id) VALUES (?,?)', [$users[1],$procedures['other']]);
    fixture($pdo, 'INSERT INTO progreso_tramites (usuario_id,tramite_id,estado) VALUES (?,?,?)', [$users[1],$procedures['other'],'en_curso']);

    [$status, $html] = $browser->request($base, '/dashboard?usuario_id=' . $users[1] . '&user_id=' . $users[1]);
    $xpath = document($html);
    ensure($status === 200 && strpos($html, $names['other']) === false && strpos($html, $emails[1]) === false, 'Manipular parametros no expone la actividad de otra cuenta.');
    ensure(strpos($html, $names['draft']) === false && strpos($html, $names['inactive']) === false, 'Borradores e inactivos se excluyen de las listas.');
    ensure(strpos($html, '<script>alert(1)</script>') === false && strpos($html, '&lt;script&gt;alert(1)&lt;/script&gt;') !== false, 'Los nombres de tramites se escapan al mostrarlos.');
    $counts = [];
    foreach ($xpath->query('//div[@class="dashboard-stats"]/div/strong') as $node) {
        $counts[] = trim($node->textContent);
    }
    ensure($counts === ['2','1','1'], 'Los conteos incluyen solo pendientes, favoritos y completados visibles de la cuenta.');
    $recentNames = [];
    foreach ($xpath->query('//section[@aria-labelledby="recent-title"]//li/strong') as $node) {
        $recentNames[] = $node->textContent;
    }
    ensure($recentNames === [$names['paused'],$names['done'],$names['active']], 'La actividad reciente se ordena por actualizacion descendente.');
    $progressText = $xpath->query('//section[@aria-labelledby="progress-title"]')->item(0)->textContent;
    ensure(strpos($progressText, $names['done']) === false && strpos($progressText, $names['active']) !== false && strpos($progressText, $names['paused']) !== false, 'Mi progreso excluye los completados y conserva los pausados.');
    ensure(strpos($html, '02/01/2026 10:00') !== false, 'Las fechas UTC se convierten a hora de Bolivia.');

    $premium = (int) $pdo->query("SELECT id FROM planes WHERE nombre='premium'")->fetchColumn();
    $pdo->prepare('UPDATE usuarios SET plan_id=? WHERE id=?')->execute([$premium,$users[0]]);
    [, $html] = $browser->request($base, '/dashboard');
    ensure(strpos($html, '<strong>Premium</strong>') !== false, 'El plan se obtiene de la base en cada solicitud.');
    $check = $pdo->prepare('SELECT COUNT(*) FROM progreso_tramites WHERE usuario_id=?');
    $check->execute([$users[0]]);
    ensure((int) $check->fetchColumn() === 5, 'Consultar el dashboard no crea ni cambia seguimientos.');
    [$status] = $browser->request($base, '/dashboard', []);
    ensure($status === 405, 'El dashboard es una ruta de solo lectura.');

    [, $html] = $other->request($base, '/login');
    $other->request($base, '/login', ['_token' => token($html), 'email' => $emails[1], 'password' => $password]);
    [$status, $html] = $other->request($base, '/dashboard');
    ensure($status === 200 && strpos($html, $names['other']) !== false && strpos($html, $names['done']) === false, 'Una segunda cuenta ve su propio dashboard.');
    $other->request($base, '/logout', ['_token' => token($html)]);
    $pdo->prepare("UPDATE usuarios SET estado='bloqueado' WHERE id=?")->execute([$users[0]]);
    [$status] = $browser->request($base, '/dashboard');
    ensure($status === 303 && $browser->headerContains('/login'), 'Una cuenta bloqueada pierde acceso al dashboard.');
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    if ($pdo instanceof PDO && $users !== []) {
        $pdo->beginTransaction();
        foreach ($users as $id) {
            $pdo->prepare('DELETE FROM progreso_tramites WHERE usuario_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$id]);
        }
        foreach ($procedures as $id) {
            $pdo->prepare('DELETE FROM tramites WHERE id=?')->execute([$id]);
        }
        $pdo->commit();
    }
    foreach ([$browser->ip,$other->ip] as $ip) {
        foreach ($emails as $email) {
            $file = BASE_PATH . '/storage/cache/login-' . hash('sha256', $email . '|' . $ip) . '.json';
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
if ($failure !== null) {
    echo 'FAIL: ' . ($failure instanceof PDOException ? 'Error SQL durante la prueba de dashboard.' : $failure->getMessage()) . "\n";
    exit(1);
}
echo "OK: dashboard comprobado y fixtures temporales eliminados.\n";

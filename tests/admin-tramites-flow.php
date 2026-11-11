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
use App\Services\TramiteService;
use App\Services\TramiteValidator;

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8087', '/');
$url = parse_url($base);
if ($url === false || !in_array($url['scheme'] ?? '', ['http','https'], true)
    || !in_array($url['host'] ?? '', ['127.0.0.1','localhost','[::1]'], true)
    || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
    echo "ERROR: utiliza una URL local de desarrollo.\n";
    exit(2);
}

function versionFrom(string $html): string
{
    if (!preg_match('/name="version" value="([a-f0-9]{64})"/', $html, $matches)) {
        throw new RuntimeException('El formulario debe contener una version para editar.');
    }
    return $matches[1];
}

function enableTemporaryAdmin(string $email): int
{
    $process = proc_open([PHP_BINARY, BASE_PATH . '/scripts/enable-admin.php', $email], [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('No se pudo ejecutar el comando de configuracion local.');
    }
    fclose($pipes[0]);
    stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return proc_close($process);
}

function loginTemporary(TestBrowser $browser, string $base, string $email, string $password): void
{
    [, $html] = $browser->request($base, '/login');
    [$status] = $browser->request($base, '/login', ['_token' => token($html), 'email' => $email, 'password' => $password]);
    ensure($status === 303, 'Cuenta temporal autenticada.', false);
}

$suffix = bin2hex(random_bytes(12));
$emails = ['crud-admin-' . $suffix . '@example.invalid', 'crud-user-' . $suffix . '@example.invalid'];
$password = 'Temporal-' . bin2hex(random_bytes(16));
$name = 'Prueba CRUD <script>alert(1)</script> ' . $suffix;
$users = [];
$sourceId = null;
$pdo = null;
$admin = new TestBrowser();
$ordinary = new TestBrowser();
$failure = null;

try {
    $pdo = Database::connection();
    [$status] = $admin->request($base, '/admin/tramites');
    ensure($status === 303 && $admin->headerContains('/login'), 'Un visitante no accede a la gestion de tramites.');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $model = new User();
    $users[] = $model->create('Admin de prueba', 'Temporal', $emails[0], $hash);
    $users[] = $model->create('Usuario de prueba', 'Temporal', $emails[1], $hash);
    $adminRole = (int) $pdo->query("SELECT id FROM roles WHERE nombre='administrador'")->fetchColumn();
    $userRole = (int) $pdo->query("SELECT id FROM roles WHERE nombre='usuario'")->fetchColumn();
    $count = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id=? AND estado='activo'");
    $count->execute([$adminRole]);
    if ((int) $count->fetchColumn() === 0) {
        ensure(enableTemporaryAdmin($emails[0]) === 0, 'El comando local habilita un primer administrador temporal.');
        ensure(enableTemporaryAdmin($emails[0]) === 0, 'Repetir la configuracion de la misma cuenta es idempotente.');
    } else {
        ensure(enableTemporaryAdmin($emails[0]) === 1, 'El comando inicial no agrega administradores si ya existe uno activo.');
        // Solo se eleva esta cuenta desechable para ejecutar el resto de pruebas.
        $pdo->prepare('UPDATE usuarios SET rol_id=? WHERE id=?')->execute([$adminRole,$users[0]]);
    }
    ensure(enableTemporaryAdmin($emails[1]) === 1, 'El comando no eleva una segunda cuenta.');
    $check = $pdo->prepare('SELECT rol_id FROM usuarios WHERE id=?');
    $check->execute([$users[1]]);
    ensure((int) $check->fetchColumn() === $userRole, 'El usuario normal conserva su rol.');

    loginTemporary($ordinary, $base, $emails[1], $password);
    foreach (['/admin/tramites','/admin/tramites/crear','/admin/tramites/ver?id=1','/admin/tramites/editar?id=1','/admin/tramites/desactivar?id=1'] as $path) {
        [$status] = $ordinary->request($base, $path);
        ensure($status === 403, 'Una ruta administrativa rechaza usuarios normales.', false);
    }
    [, $html] = $ordinary->request($base, '/dashboard');
    foreach (['crear','editar','desactivar'] as $action) {
        [$status] = $ordinary->request($base, '/admin/tramites/' . $action, ['_token' => token($html), 'id' => '1', 'rol' => 'administrador']);
        ensure($status === 403, 'Una escritura administrativa rechaza usuarios normales.', false);
    }
    ensure(true, 'Los permisos se comprueban en todas las lecturas y escrituras administrativas.');

    loginTemporary($admin, $base, $emails[0], $password);
    [$status, $html] = $admin->request($base, '/admin/tramites');
    ensure($status === 200 && strpos($html, 'Crear tr') !== false, 'Un administrador puede consultar el listado.');
    [$status] = $admin->request($base, '/admin/tramites?pagina=2');
    ensure($status === 200, 'El listado inicial se pagina sin perder registros.');
    foreach (['/admin/tramites/editar?id[]=1','/admin/tramites/ver?id=0','/admin/tramites/ver?id=4294967295','/admin/tramites?estado[]=publicado','/admin/tramites?pagina=0'] as $path) {
        [$status] = $admin->request($base, $path);
        ensure($status === 404, 'Identificador o filtro invalido rechazado.', false);
    }
    ensure(true, 'Identificadores, paginas y filtros invalidos se rechazan con 404.');
    [, $html] = $admin->request($base, '/admin/tramites/crear');
    $csrf = token($html);
    $category = (string) $pdo->query('SELECT id FROM categorias WHERE estado=1 ORDER BY id LIMIT 1')->fetchColumn();
    $diriSource = (string) $pdo->query("SELECT id FROM fuentes WHERE tipo='DIRI' ORDER BY id LIMIT 1")->fetchColumn();
    $data = ['nombre' => $name, 'slug' => '', 'descripcion' => 'Contenido temporal de prueba. No son requisitos ni procedimientos oficiales.', 'categoria_id' => $category, 'estado' => 'borrador', 'dificultad' => '', 'tiempo_estimado' => '', 'fuente_id' => '', 'estado_verificacion' => 'pendiente', 'fecha_verificacion' => ''];
    foreach (['crear','editar','desactivar'] as $action) {
        [$status] = $admin->request($base, '/admin/tramites/' . $action, ['id' => '1']);
        ensure($status === 419, 'Una escritura sin CSRF se rechaza.', false);
    }
    ensure(true, 'Todas las escrituras administrativas exigen CSRF.');
    [$status] = $admin->request($base, '/admin/tramites/crear', array_merge($data, ['_token' => $csrf,'nombre' => []]));
    ensure($status === 422, 'Los tipos incorrectos reciben errores de validacion.');
    [$status] = $admin->request($base, '/admin/tramites/crear', array_merge($data, ['_token' => $csrf,'categoria_id' => '4294967295']));
    ensure($status === 422, 'Se exige una categoria existente y activa.');
    [$status] = $admin->request($base, '/admin/tramites/crear', array_merge($data, ['_token' => $csrf,'estado' => 'publicado']));
    ensure($status === 422, 'No se publica informacion pendiente de verificacion.');

    [$status] = $admin->request($base, '/admin/tramites/crear', array_merge($data, ['_token' => $csrf]));
    ensure($status === 303 && $admin->headerContains('/admin/tramites'), 'Crear un tramite redirige al listado.');
    $query = $pdo->prepare('SELECT * FROM tramites WHERE nombre=?');
    $query->execute([$name]);
    $row = $query->fetch();
    $id = (int) $row['id'];
    ensure($row['estado'] === 'borrador' && $row['dificultad'] === null && $row['tiempo_estimado'] === null && $row['fuente_id'] === null && preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $row['slug']) === 1, 'Los datos desconocidos conservan NULL y el identificador se genera.');
    [$status, $html] = $admin->request($base, '/admin/tramites/crear', array_merge($data, ['_token' => $csrf]));
    ensure($status === 422 && strpos($html, '&lt;script&gt;') !== false && strpos($html, '<script>alert(1)</script>') === false, 'Los identificadores duplicados se controlan y los valores se escapan.');
    [, $html] = $admin->request($base, '/admin/tramites/editar?id=' . $id);
    ensure(strpos($html, 'value="' . e($name) . '"') !== false && strpos($html, e($data['descripcion'])) !== false, 'Editar conserva el nombre y la descripcion existentes.');
    $v1 = versionFrom($html);
    $edit = array_merge($data, ['id' => (string) $id, 'version' => $v1, '_token' => token($html)]);
    [$status] = $admin->request($base, '/admin/tramites/editar', array_merge($edit, ['estado_verificacion' => 'verificado','fuente_id' => $diriSource,'fecha_verificacion' => '2026-01-01']));
    ensure($status === 422, 'Una fuente DIRI no se presenta como verificacion oficial.');
    [$status] = $admin->request($base, '/admin/tramites/editar', array_merge($edit, ['fecha_verificacion' => '2999-01-01','fuente_id' => $diriSource]));
    ensure($status === 422, 'No se aceptan fechas futuras.');
    [$status] = $admin->request($base, '/admin/tramites/editar', array_merge($edit, ['fecha_verificacion' => "2026-01-01\0"]));
    ensure($status === 422, 'Una fecha con bytes invalidos no genera un error interno.');
    [$status] = $admin->request($base, '/admin/tramites/editar', array_merge($edit, ['estado' => 'publicado','estado_verificacion' => 'referencial']));
    ensure($status === 422, 'La publicacion referencial tambien requiere una fuente.');

    $published = array_merge($edit, ['estado' => 'publicado','estado_verificacion' => 'referencial','fuente_id' => $diriSource]);
    [$status] = $admin->request($base, '/admin/tramites/editar', $published);
    ensure($status === 303, 'La informacion referencial respaldada puede publicarse.');
    [$status] = $admin->request($base, '/admin/tramites/editar', array_merge($published, ['nombre' => 'Intento desactualizado ' . $suffix]));
    ensure($status === 409, 'Una version antigua no sobrescribe la actual aunque se guarde en el mismo segundo.');

    $pdo->prepare("INSERT INTO fuentes (nombre,tipo,url) VALUES (?,'oficial','https://example.invalid/prueba')")->execute(['Fuente oficial simulada para prueba ' . $suffix]);
    $sourceId = (int) $pdo->lastInsertId();
    [, $html] = $admin->request($base, '/admin/tramites/editar?id=' . $id);
    $official = array_merge($published, ['version' => versionFrom($html), 'estado_verificacion' => 'verificado','fuente_id' => (string) $sourceId,'fecha_verificacion' => '2026-01-01']);
    [$status] = $admin->request($base, '/admin/tramites/editar', $official);
    ensure($status === 303, 'Una verificacion exige fuente oficial y fecha valida.');
    [$status, $html] = $admin->request($base, '/admin/tramites/ver?id=' . $id);
    ensure($status === 200 && strpos($html, '01/01/2026') !== false && strpos($html, '&lt;script&gt;') !== false && strpos($html, '<script>alert(1)</script>') === false, 'La consulta muestra la fuente y fecha sin interpretar HTML del contenido.');

    // Se crean referencias para comprobar que desactivar no elimina favoritos o progreso.
    $pdo->prepare('INSERT INTO favoritos (usuario_id,tramite_id) VALUES (?,?)')->execute([$users[1],$id]);
    $pdo->prepare('INSERT INTO progreso_tramites (usuario_id,tramite_id) VALUES (?,?)')->execute([$users[1],$id]);
    [, $html] = $admin->request($base, '/admin/tramites/desactivar?id=' . $id);
    $v3 = versionFrom($html);
    $query = $pdo->prepare('SELECT estado FROM tramites WHERE id=?');
    $query->execute([$id]);
    ensure($query->fetchColumn() === 'publicado', 'Abrir la confirmacion no desactiva el tramite.');
    [$status] = $admin->request($base, '/admin/tramites/desactivar', ['_token' => token($html),'id' => (string) $id,'version' => $v1]);
    ensure($status === 409, 'Desactivar tambien exige una version vigente.');
    [$status] = $admin->request($base, '/admin/tramites/desactivar', ['_token' => token($html),'id' => (string) $id,'version' => $v3]);
    $query->execute([$id]);
    ensure($status === 303 && $query->fetchColumn() === 'inactivo', 'Desactivar conserva el registro con estado inactivo.');
    $query = $pdo->prepare('SELECT COUNT(*) FROM favoritos WHERE usuario_id=? AND tramite_id=?');
    $query->execute([$users[1],$id]);
    $favorites = (int) $query->fetchColumn();
    $query = $pdo->prepare('SELECT COUNT(*) FROM progreso_tramites WHERE usuario_id=? AND tramite_id=?');
    $query->execute([$users[1],$id]);
    ensure($favorites === 1 && (int) $query->fetchColumn() === 1, 'Se conservan favoritos y seguimientos relacionados.');
    [, $html] = $ordinary->request($base, '/dashboard');
    ensure(strpos($html, e($name)) === false, 'Un tramite desactivado deja de mostrarse en el dashboard.');
    [$status, $html] = $admin->request($base, '/admin/tramites?estado=inactivo');
    ensure($status === 200 && strpos($html, e($name)) !== false, 'El filtro administrativo permite consultar inactivos.');
    $query = $pdo->prepare("SELECT accion FROM auditoria_administrativa WHERE usuario_id=? AND entidad='tramites' AND entidad_id=? ORDER BY id");
    $query->execute([$users[0],$id]);
    ensure($query->fetchAll(PDO::FETCH_COLUMN) === ['crear_tramite','actualizar_tramite','actualizar_tramite','desactivar_tramite'], 'Solo los cambios exitosos quedan auditados con su actor.');

    [$rollbackData, $errors] = TramiteValidator::validate(array_merge($data, ['slug' => 'rollback-' . $suffix]));
    ensure($errors === [], 'Fixture de transaccion valido.', false);
    $rolledBack = false;
    try {
        (new TramiteService())->create($rollbackData, 0);
    } catch (PDOException $exception) {
        $rolledBack = (int) ($exception->errorInfo[1] ?? 0) === 1452;
    }
    $query = $pdo->prepare('SELECT COUNT(*) FROM tramites WHERE slug=?');
    $query->execute(['rollback-' . $suffix]);
    ensure($rolledBack && (int) $query->fetchColumn() === 0, 'Un fallo de auditoria revierte tambien el cambio del tramite.');

    $pdo->prepare('UPDATE usuarios SET rol_id=? WHERE id=?')->execute([$userRole,$users[0]]);
    [$status] = $admin->request($base, '/admin/tramites');
    ensure($status === 403, 'Quitar el rol administrador revoca el acceso en la siguiente solicitud.');
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    // Solo fixtures aleatorios de esta ejecucion. No modifica cuentas o borradores existentes.
    if ($pdo instanceof PDO) {
        $pdo->beginTransaction();
        foreach ($users as $id) {
            $pdo->prepare('DELETE FROM auditoria_administrativa WHERE usuario_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM progreso_tramites WHERE usuario_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$id]);
        }
        $pdo->prepare('DELETE FROM tramites WHERE nombre IN (?,?) OR slug=?')->execute([$name,'Intento desactualizado ' . $suffix,'rollback-' . $suffix]);
        if ($sourceId !== null) {
            $pdo->prepare('DELETE FROM fuentes WHERE id=?')->execute([$sourceId]);
        }
        $pdo->commit();
    }
    foreach ([$admin->ip,$ordinary->ip] as $ip) {
        foreach ($emails as $email) {
            $file = BASE_PATH . '/storage/cache/login-' . hash('sha256', $email . '|' . $ip) . '.json';
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
if ($failure !== null) {
    echo 'FAIL: ' . ($failure instanceof PDOException ? 'Error SQL en la prueba administrativa, codigo ' . ($failure->errorInfo[1] ?? 'desconocido') : $failure->getMessage()) . "\n";
    exit(1);
}
echo "OK: CRUD administrativo comprobado y fixtures temporales eliminados.\n";

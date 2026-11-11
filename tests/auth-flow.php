<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/core/bootstrap.php';

use App\Core\Database;

// Prueba HTTP local con cuenta desechable. Nunca imprime claves ni cookies.
require __DIR__ . '/support/HttpTest.php';

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8087', '/');
$url = parse_url($base);
if ($url === false || !in_array($url['scheme'] ?? '', ['http','https'], true)
    || !in_array($url['host'] ?? '', ['127.0.0.1','localhost','[::1]'], true)
    || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
    echo "ERROR: usa una URL local, por ejemplo http://127.0.0.1:8087 o http://localhost/DIRI/public\n";
    exit(2);
}

$suffix = bin2hex(random_bytes(12));
$email = 'auth-test-' . $suffix . '@example.invalid';
$missingEmail = 'missing-' . $suffix . '@example.invalid';
$limitedEmail = 'limited-' . $suffix . '@example.invalid';
$password = 'Temporal-' . bin2hex(random_bytes(16));
$pdo = null;
$userId = null;
$browser = new TestBrowser();
$failure = null;

try {
    $pdo = Database::connection();
    [$status] = $browser->request($base, '/perfil');
    ensure($status === 303 && $browser->headerContains('/login'), 'Un visitante no accede al perfil.');
    [$status] = $browser->request($base, '/logout');
    ensure($status === 405 && $browser->headerContains('Allow: POST'), 'Logout no admite GET.');

    [$status, $html] = $browser->request($base, '/registro');
    $csrf = token($html);
    $sessionBefore = $browser->sessionId();
    // La cookie puede haberse emitido en la primera solicitud, antes de esta vista.
    ensure($status === 200 && $sessionBefore !== '', 'Se inicia una sesion con cookie para los formularios.');
    $data = ['nombre' => json_decode('"Mar\u00eda"'), 'apellido' => 'Prueba', 'email' => strtoupper($email), 'password' => $password, 'password_confirmation' => $password];

    [$status] = $browser->request($base, '/registro', $data);
    ensure($status === 419, 'Registro sin CSRF rechazado.');
    [$status] = $browser->request($base, '/registro', array_merge($data, ['_token' => ['invalido']]));
    ensure($status === 419, 'Token CSRF con tipo incorrecto rechazado.');
    $data['_token'] = $csrf;
    [$status] = $browser->request($base, '/registro', array_merge($data, ['nombre' => [], 'email' => [], 'password' => []]));
    ensure($status === 422, 'Las entradas con tipos incorrectos se validan sin errores internos.');
    [$status, $html] = $browser->request($base, '/registro', array_merge($data, ['nombre' => '<script>alert(1)</script>']));
    ensure($status === 422 && strpos($html, '&lt;script&gt;') !== false && strpos($html, '<script>alert(1)</script>') === false && strpos($html, $password) === false, 'Se escapa el HTML y nunca se repite la clave en el formulario.');
    [$status] = $browser->request($base, '/registro', array_merge($data, ['password_confirmation' => 'diferente']));
    ensure($status === 422, 'La confirmacion de clave debe coincidir.');
    [$status] = $browser->request($base, '/registro', array_merge($data, ['password' => 'corta', 'password_confirmation' => 'corta']));
    ensure($status === 422, 'Se rechaza una clave demasiado corta.');
    [$status] = $browser->request($base, '/registro', array_merge($data, ['password' => str_repeat('x', 73), 'password_confirmation' => str_repeat('x', 73)]));
    ensure($status === 422, 'Se evita truncar claves que superen 72 bytes.');

    [$status] = $browser->request($base, '/registro', array_merge($data, ['rol_id' => 1, 'plan_id' => 2, 'estado' => 'bloqueado']));
    ensure($status === 303 && $browser->headerContains('/login'), 'Registro correcto redirige al login.');
    $query = $pdo->prepare('SELECT u.*, r.nombre AS rol, p.nombre AS plan FROM usuarios u JOIN roles r ON r.id=u.rol_id JOIN planes p ON p.id=u.plan_id WHERE u.email=?');
    $query->execute([$email]);
    $user = $query->fetch();
    $userId = $user['id'] ?? null;
    ensure($user !== false && $user['rol'] === 'usuario' && $user['plan'] === 'free' && $user['estado'] === 'activo', 'El servidor asigna usuario/free aunque el cliente solicite otros permisos.');
    ensure($user['email'] === $email && $user['password'] !== $password && password_verify($password, $user['password']), 'El correo se normaliza y solo se guarda un hash verificable.');
    [$status] = $browser->request($base, '/registro', $data);
    ensure($status === 422, 'Un correo duplicado recibe un error controlado.');

    [$status, $html] = $browser->request($base, '/login');
    $csrf = token($html);
    [$status, $wrongBody] = $browser->request($base, '/login', ['_token' => $csrf, 'email' => $email, 'password' => 'incorrecta-123']);
    ensure($status === 401, 'Una clave incorrecta no inicia sesion.');
    [$status, $missingBody] = $browser->request($base, '/login', ['_token' => $csrf, 'email' => $missingEmail, 'password' => 'incorrecta-123']);
    ensure($status === 401 && strpos($wrongBody, 'Correo o contrase') !== false && strpos($missingBody, 'Correo o contrase') !== false, 'Cuenta inexistente y clave incorrecta usan el mismo error.');
    [$status] = $browser->request($base, '/login', ['_token' => $csrf, 'email' => $email, 'password' => $password]);
    $authenticatedId = $browser->sessionId();
    ensure($status === 303 && $browser->headerContains('/dashboard') && $authenticatedId !== $sessionBefore, 'Login correcto renueva la sesion y dirige al dashboard.');
    ensure($browser->headerContains('HttpOnly') && $browser->headerContains('SameSite=Lax'), 'La cookie protege el acceso desde scripts y limita su envio entre sitios.');
    [$status, $html] = $browser->request($base, '/perfil');
    $newCsrf = token($html);
    ensure($status === 200 && strpos($html, $email) !== false && strpos($html, $password) === false && strpos($html, $user['password']) === false && $newCsrf !== $csrf, 'El perfil muestra datos seguros y el token CSRF se renueva al entrar.');
    [$status] = $browser->request($base, '/login');
    ensure($status === 303 && $browser->headerContains('/dashboard'), 'Un usuario autenticado no vuelve al formulario de login.');
    [$status] = $browser->request($base, '/logout', ['_token' => $csrf]);
    ensure($status === 419, 'El token anterior al login no permite cerrar la sesion.');
    [$status] = $browser->request($base, '/perfil');
    ensure($status === 200, 'Un intento de logout invalido conserva la sesion.');
    [$status] = $browser->request($base, '/logout', ['_token' => $newCsrf]);
    ensure($status === 303 && $browser->headerContains('/login'), 'Logout con CSRF valido completa el cierre.');
    [$status] = $browser->request($base, '/perfil');
    ensure($status === 303, 'El perfil queda protegido despues de cerrar sesion.');
    $replay = new TestBrowser();
    $replay->setSessionId($authenticatedId);
    [$status] = $replay->request($base, '/perfil');
    ensure($status === 303, 'La cookie de una sesion destruida no recupera acceso.');

    [$status, $html] = $browser->request($base, '/login');
    $csrf = token($html);
    $browser->request($base, '/login', ['_token' => $csrf, 'email' => $email, 'password' => $password]);
    $pdo->prepare("UPDATE usuarios SET estado='bloqueado' WHERE id=?")->execute([$userId]);
    [$status] = $browser->request($base, '/perfil');
    ensure($status === 303, 'Bloquear una cuenta revoca su acceso en la siguiente solicitud.');
    [$status, $html] = $browser->request($base, '/login');
    $csrf = token($html);
    [$status] = $browser->request($base, '/login', ['_token' => $csrf, 'email' => $email, 'password' => $password]);
    ensure($status === 401, 'Una cuenta bloqueada tampoco puede volver a entrar.');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        [$status] = $browser->request($base, '/login', ['_token' => $csrf, 'email' => $limitedEmail, 'password' => 'incorrecta-123']);
        ensure($status === 401, 'Intento previo al limite.', false);
    }
    [$status] = $browser->request($base, '/login', ['_token' => $csrf, 'email' => $limitedEmail, 'password' => 'incorrecta-123']);
    ensure($status === 429 && $browser->headerContains('Retry-After: 900'), 'El limite de intentos devuelve 429.');
    $another = new TestBrowser();
    [, $html] = $another->request($base, '/login');
    [$status] = $another->request($base, '/login', ['_token' => token($html), 'email' => $limitedEmail, 'password' => 'incorrecta-123']);
    ensure($status === 429, 'Cambiar de cookie no evade el limite de intentos.');
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    // Borra exclusivamente la cuenta y los contadores aleatorios de esta ejecucion.
    if ($pdo instanceof PDO) {
        $pdo->prepare('DELETE FROM usuarios WHERE email = ?')->execute([$email]);
    }
    if ($browser->ip !== '') {
        foreach ([$email, $missingEmail, $limitedEmail] as $temporaryEmail) {
            $path = BASE_PATH . '/storage/cache/login-' . hash('sha256', $temporaryEmail . '|' . $browser->ip) . '.json';
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}

if ($failure !== null) {
    echo 'FAIL: ' . ($failure instanceof PDOException ? 'Error de base de datos durante la prueba.' : $failure->getMessage()) . "\n";
    exit(1);
}
echo "OK: flujo HTTP de autenticacion aprobado y cuenta temporal eliminada.\n";

<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/core/bootstrap.php';

use App\Core\Csrf;
use App\Core\Session;

$results = [];
function sessionCheck(bool $condition, string $description): void
{
    global $results;
    if (!$condition) {
        throw new RuntimeException($description);
    }
    $results[] = 'PASS: ' . $description;
}

// Almacenamiento aislado: esta prueba nunca abre sesiones reales de usuarios.
$directory = BASE_PATH . '/storage/cache/session-test-' . bin2hex(random_bytes(12));
$failure = null;
try {
    if (!mkdir($directory, 0700)) {
        throw new RuntimeException('No se pudo crear el almacenamiento temporal de sesiones.');
    }
    session_save_path($directory);
    $invented = str_repeat('a', 26);
    session_id($invented);
    Session::start();
    sessionCheck(session_id() !== $invented, 'El modo estricto rechaza un identificador inventado.');
    $params = session_get_cookie_params();
    sessionCheck($params['httponly'] && $params['samesite'] === 'Lax' && (bool) ini_get('session.use_only_cookies'), 'Se configuran cookies HttpOnly, SameSite y sin identificadores en URL.');
    $oldToken = Csrf::token();
    sessionCheck(strlen($oldToken) === 64 && Csrf::valid($oldToken) && !Csrf::valid([]) && !Csrf::valid('incorrecto'), 'El token CSRF valida el valor y el tipo recibidos.');
    $oldId = session_id();
    $_SESSION['user_id'] = 123;
    Session::renew();
    sessionCheck(session_id() !== $oldId && Csrf::token() !== $oldToken && $_SESSION['user_id'] === 123, 'Renovar cambia identificador y token sin perder los datos de sesion.');

    $_SESSION['last_activity'] = time() - 1801;
    $_SESSION['created_at'] = time() - 2000;
    $oldId = session_id();
    session_write_close();
    Session::start();
    sessionCheck(!isset($_SESSION['user_id']) && session_id() !== $oldId, 'Treinta minutos sin actividad invalidan la autenticacion.');

    $_SESSION['user_id'] = 123;
    $_SESSION['last_activity'] = time();
    $_SESSION['created_at'] = time() - 43201;
    session_write_close();
    Session::start();
    sessionCheck(!isset($_SESSION['user_id']), 'Doce horas de duracion invalidan incluso una sesion activa.');
    Session::destroy();
    sessionCheck(session_status() !== PHP_SESSION_ACTIVE && $_SESSION === [], 'Logout destruye el estado de sesion.');
} catch (Throwable $exception) {
    $failure = $exception->getMessage();
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) {
        Session::destroy();
    }
    // Solo archivos de esta carpeta aleatoria, sin recorrer otros directorios.
    foreach (glob($directory . '/sess_*') ?: [] as $file) {
        unlink($file);
    }
    if (is_dir($directory)) {
        rmdir($directory);
    }
}

foreach ($results as $result) {
    echo $result . "\n";
}
if ($failure !== null) {
    echo 'FAIL: ' . $failure . "\n";
    exit(1);
}
echo "OK: seguridad de sesiones comprobada sin modificar sesiones reales.\n";

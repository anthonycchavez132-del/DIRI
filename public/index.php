<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require dirname(__DIR__) . '/app/core/bootstrap.php';

$router = new App\Core\Router($config['base_path']);
require BASE_PATH . '/routes/web.php';
require BASE_PATH . '/routes/api.php';

try {
    App\Core\Session::start();
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $exception) {
    // Los detalles técnicos van al registro de PHP, nunca al navegador.
    // No registrar la traza: podría incluir contraseñas recibidas como argumentos.
    error_log('DIRI: error interno [' . get_class($exception) . '] código ' . $exception->getCode());
    http_response_code(500);
    if($router->isApiUri($_SERVER['REQUEST_URI'])){App\Core\ApiResponse::error(500,'internal_error','No se pudo completar la consulta.');return;}
    (new App\Core\Controller())->render('errors/500', ['title' => 'Ocurrió un problema']);
}

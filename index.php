<?php

declare(strict_types=1);

date_default_timezone_set('America/La_Paz');

define('DIRI_ENTRADA', true);

session_name('DIRI2_SESION');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/DIRI2/',
]);
session_start();

if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/controladores/InicioController.php';
require_once __DIR__ . '/controladores/UsuarioController.php';
require_once __DIR__ . '/controladores/TramiteController.php';
require_once __DIR__ . '/controladores/DocumentoController.php';
require_once __DIR__ . '/controladores/EstablecimientoController.php';
require_once __DIR__ . '/controladores/GestionController.php';
require_once __DIR__ . '/controladores/HorarioController.php';
require_once __DIR__ . '/controladores/RecorridoController.php';

$pagina = $_GET['pagina'] ?? 'inicio';

if (!is_string($pagina)) {
    http_response_code(400);
    exit('La página solicitada es inválida.');
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Método no permitido.');
}

try {
    switch ($pagina) {
        case 'inicio':
            $controlador = new InicioController();
            $controlador->index();
            break;

        case 'registro':
            $controlador = new UsuarioController();
            $controlador->registro();
            break;

        case 'login':
            $controlador = new UsuarioController();
            $controlador->login();
            break;

        case 'panel':
            $controlador = new UsuarioController();
            $controlador->panel();
            break;

        case 'cambiar_contrasena':
            $controlador = new UsuarioController();
            $controlador->cambiarContrasena();
            break;

        case 'salir':
            $controlador = new UsuarioController();
            $controlador->cerrarSesion();
            break;

        case 'usuarios':
            $controlador = new UsuarioController();
            $controlador->listarUsuarios();
            break;

        case 'eliminar_usuario':
            $controlador = new UsuarioController();
            $controlador->eliminarUsuario();
            break;

        case 'tramites':
            $controlador = new TramiteController();
            $controlador->listar();
            break;

        case 'nuevo_tramite':
        case 'editar_tramite':
            $controlador = new TramiteController();
            $controlador->formulario();
            break;

        case 'guardar_tramite':
            $controlador = new TramiteController();
            $controlador->guardar();
            break;

        case 'desactivar_tramite':
            $controlador = new TramiteController();
            $controlador->desactivar();
            break;

        case 'documentos':
            $controlador = new DocumentoController();
            $controlador->listar();
            break;

        case 'nuevo_documento':
        case 'editar_documento':
            $controlador = new DocumentoController();
            $controlador->formulario();
            break;

        case 'guardar_documento':
            $controlador = new DocumentoController();
            $controlador->guardar();
            break;

        case 'desactivar_documento':
            $controlador = new DocumentoController();
            $controlador->desactivar();
            break;

        case 'establecimientos':
            $controlador = new EstablecimientoController();
            $controlador->listar();
            break;

        case 'nuevo_establecimiento':
        case 'editar_establecimiento':
            $controlador = new EstablecimientoController();
            $controlador->formulario();
            break;

        case 'guardar_establecimiento':
            $controlador = new EstablecimientoController();
            $controlador->guardar();
            break;

        case 'desactivar_establecimiento':
            $controlador = new EstablecimientoController();
            $controlador->desactivar();
            break;

        case 'gestiones':
            $controlador = new GestionController();
            $controlador->listar();
            break;

        case 'nueva_gestion':
        case 'editar_gestion':
            $controlador = new GestionController();
            $controlador->formulario();
            break;

        case 'guardar_gestion':
            $controlador = new GestionController();
            $controlador->guardar();
            break;

        case 'desactivar_gestion':
            $controlador = new GestionController();
            $controlador->desactivar();
            break;

        case 'horarios':
            $controlador = new HorarioController();
            $controlador->listar();
            break;

        case 'nuevo_horario':
        case 'editar_horario':
            $controlador = new HorarioController();
            $controlador->formulario();
            break;

        case 'guardar_horario':
            $controlador = new HorarioController();
            $controlador->guardar();
            break;

        case 'desactivar_horario':
            $controlador = new HorarioController();
            $controlador->desactivar();
            break;

        case 'preparar_recorrido':
            $controlador = new RecorridoController();
            $controlador->formulario();
            break;

        case 'calcular_recorrido':
            $controlador = new RecorridoController();
            $controlador->calcular();
            break;

        default:
            http_response_code(404);
            echo 'La página solicitada no existe.';
    }
} catch (Throwable $error) {
    error_log('DIRI2: ' . $error->getMessage());
    http_response_code(500);
    echo 'No se pudo completar la solicitud. Revisa que MySQL esté iniciado en XAMPP.';
}

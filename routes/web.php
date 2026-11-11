<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\AdminMiddleware;
use App\Controllers\AdminTramiteController;
use App\Controllers\AdminContenidoController;
use App\Controllers\AdminResourceController;
use App\Controllers\AdminHomeController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/buscar', [\App\Controllers\CatalogController::class, 'search']);
$router->get('/tramite', [\App\Controllers\CatalogController::class, 'detail']);
$router->get('/guias', [\App\Controllers\SimpleGuideController::class, 'index']);
$router->get('/guia', [\App\Controllers\SimpleGuideController::class, 'show']);
$router->get('/mi-ruta', [\App\Controllers\LicenseJourneyController::class, 'index']);
$router->post('/mi-ruta', [\App\Controllers\LicenseJourneyController::class, 'calculate'], [CsrfMiddleware::class]);
$router->post('/mi-ruta/reiniciar', [\App\Controllers\LicenseJourneyController::class, 'reset'], [CsrfMiddleware::class]);
$premium=[AuthMiddleware::class,\App\Middleware\PremiumMiddleware::class];
$premiumPost=[AuthMiddleware::class,\App\Middleware\PremiumMiddleware::class,CsrfMiddleware::class];
$router->get('/mi-checklist', [\App\Controllers\PersonalController::class,'checklist'],$premium);
$router->post('/mi-checklist/guardar', [\App\Controllers\PersonalController::class,'change'],$premiumPost);
$router->get('/comparar-sucursales',[\App\Controllers\RecommendationController::class,'branches'],$premium);
$router->get('/rutas',[\App\Controllers\RecommendationController::class,'routes'],$premium);
$router->get('/ruta',[\App\Controllers\RecommendationController::class,'route'],$premium);
$router->post('/ruta/guardar',[\App\Controllers\RecommendationController::class,'saveRoute'],$premiumPost);
$router->get('/mi-biblioteca',[\App\Controllers\PersonalController::class,'library'],$premium);
$router->post('/mi-biblioteca/borrar-historial',[\App\Controllers\PersonalController::class,'clearHistory'],$premiumPost);
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/registro', [AuthController::class, 'showRegister']);
$router->post('/registro', [AuthController::class, 'register'], [CsrfMiddleware::class]);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
$router->get('/perfil', [AuthController::class, 'profile'], [AuthMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [CsrfMiddleware::class, AuthMiddleware::class]);
$router->get('/reportar',[\App\Controllers\ReportController::class,'form'],[AuthMiddleware::class]);
$router->post('/reportar',[\App\Controllers\ReportController::class,'submit'],[AuthMiddleware::class,CsrfMiddleware::class]);

// Los permisos se comprueban también al enviar formularios.
$admin = [AuthMiddleware::class, AdminMiddleware::class];
$adminPost = [AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class];
$router->get('/admin/reportes',[\App\Controllers\ReportController::class,'moderation'],$admin);
$router->post('/admin/reportes/revisar',[\App\Controllers\ReportController::class,'moderate'],$adminPost);
$router->get('/admin/atencion',[\App\Controllers\AdminOperationsController::class,'attention'],$admin);
$router->post('/admin/atencion',[\App\Controllers\AdminOperationsController::class,'saveAttention'],$adminPost);
$router->get('/admin/auditoria',[\App\Controllers\AdminOperationsController::class,'audit'],$admin);
$router->get('/admin', [AdminHomeController::class, 'index'], $admin);
$router->get('/admin/gestion', [AdminResourceController::class, 'index'], $admin);
$router->post('/admin/gestion/guardar', [AdminResourceController::class, 'save'], $adminPost);
$router->post('/admin/gestion/quitar', [AdminResourceController::class, 'remove'], $adminPost);
$router->get('/admin/tramites', [AdminTramiteController::class, 'index'], $admin);
$router->get('/admin/tramites/ver', [AdminTramiteController::class, 'show'], $admin);
$router->get('/admin/tramites/crear', [AdminTramiteController::class, 'create'], $admin);
$router->post('/admin/tramites/crear', [AdminTramiteController::class, 'save'], $adminPost);
$router->get('/admin/tramites/editar', [AdminTramiteController::class, 'edit'], $admin);
$router->post('/admin/tramites/editar', [AdminTramiteController::class, 'update'], $adminPost);
$router->get('/admin/tramites/desactivar', [AdminTramiteController::class, 'confirmDeactivate'], $admin);
$router->post('/admin/tramites/desactivar', [AdminTramiteController::class, 'deactivate'], $adminPost);

$router->get('/admin/tramites/contenido', [AdminContenidoController::class, 'overview'], $admin);
$router->get('/admin/documentos', [AdminContenidoController::class, 'catalog'], $admin);
$router->get('/admin/requisitos/crear', [AdminContenidoController::class, 'newRequirement'], $admin);
$router->post('/admin/requisitos/crear', [AdminContenidoController::class, 'createRequirement'], $adminPost);
$router->get('/admin/requisitos/editar', [AdminContenidoController::class, 'editRequirement'], $admin);
$router->post('/admin/requisitos/editar', [AdminContenidoController::class, 'updateRequirement'], $adminPost);
$router->get('/admin/documentos/crear', [AdminContenidoController::class, 'newDocument'], $admin);
$router->post('/admin/documentos/crear', [AdminContenidoController::class, 'createDocument'], $adminPost);
$router->get('/admin/documentos/editar', [AdminContenidoController::class, 'editDocument'], $admin);
$router->post('/admin/documentos/editar', [AdminContenidoController::class, 'updateDocument'], $adminPost);
$router->get('/admin/tramites/documentos/asociar', [AdminContenidoController::class, 'newAssociation'], $admin);
$router->post('/admin/tramites/documentos/asociar', [AdminContenidoController::class, 'createAssociation'], $adminPost);
$router->get('/admin/tramites/documentos/editar', [AdminContenidoController::class, 'editAssociation'], $admin);
$router->post('/admin/tramites/documentos/editar', [AdminContenidoController::class, 'updateAssociation'], $adminPost);

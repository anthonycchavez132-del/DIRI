<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Dashboard;
use App\Services\AuthService;

class DashboardController extends Controller
{
    public function index(): void
    {
        // AuthMiddleware comprueba el acceso antes de ejecutar este controlador.
        $user = AuthService::user();
        $dashboard = (new Dashboard())->forUser((int) $user['id']);
        $this->render('dashboard/index', [
            'title' => 'Mi espacio',
            'activePage' => 'dashboard',
            'user' => $user,
            'dashboard' => $dashboard,
            'hasPremium'=>\App\Services\PlanAccess::premium((int)$user['id']),
        ]);
    }
}

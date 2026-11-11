<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Controller;
use App\Services\AuthService;

class AdminMiddleware
{
    public function handle(): bool
    {
        if ((AuthService::user()['rol'] ?? null) !== 'administrador') {
            http_response_code(403);
            (new Controller())->render('errors/403', ['title' => 'Acceso restringido']);
            return false;
        }
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (AuthService::user() === null) {
            redirect('login');
        }
        return true;
    }
}

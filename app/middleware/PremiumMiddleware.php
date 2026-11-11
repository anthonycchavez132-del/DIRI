<?php

declare(strict_types=1);
namespace App\Middleware;
use App\Services\AuthService;
use App\Services\PlanAccess;
use App\Core\Controller;
class PremiumMiddleware
{
    public function handle(): bool
    {
        $user=AuthService::user();
        if($user===null||!PlanAccess::premium((int)$user['id'])) {
            http_response_code(403);
            (new Controller())->render('personal/premium',['title'=>'Herramienta Premium']);
            return false;
        }
        return true;
    }
}

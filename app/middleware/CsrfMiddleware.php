<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Controller;
use App\Core\Csrf;

class CsrfMiddleware
{
    public function handle(): bool
    {
        if (!Csrf::valid($_POST['_token'] ?? null)) {
            http_response_code(419);
            // Apache necesita una frase explícita para este código no estándar.
            header('HTTP/1.1 419 Page Expired');
            (new Controller())->render('errors/419', ['title' => 'Formulario vencido']);
            return false;
        }
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    public function render(string $view, array $data = []): void
    {
        // El nombre de la vista lo define el código, nunca una entrada del usuario.
        $viewFile = BASE_PATH . '/app/views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException('No se encontró la vista solicitada.');
        }

        extract($data, EXTR_SKIP);
        require BASE_PATH . '/app/views/layouts/main.php';
    }
}

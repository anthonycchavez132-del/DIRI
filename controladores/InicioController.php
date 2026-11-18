<?php

declare(strict_types=1);

final class InicioController
{
    public function index(): void
    {
        $nombreProyecto = 'DIRI';
        $ciudad = 'La Paz';

        header('Content-Type: text/html; charset=UTF-8');

        require __DIR__ . '/../vistas/inicio.php';
    }
}

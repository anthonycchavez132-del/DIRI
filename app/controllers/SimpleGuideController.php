<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CatalogService;
use App\Services\SimpleGuideService;

class SimpleGuideController extends Controller
{
    public function index(): void
    {
        $this->render('guides/index', ['title' => 'Rutas sencillas · Beta',
            'rows' => (new SimpleGuideService())->listing()]);
    }

    public function show(): void
    {
        $slug = $_GET['slug'] ?? '';
        $item = is_string($slug) ? (new CatalogService())->detail($slug) : null;
        if ($item === null) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Guía no disponible']);
            return;
        }
        $this->render('guides/show', ['title' => 'Ruta sencilla: ' . $item['nombre'],
            'item' => $item, 'guide' => (new SimpleGuideService())->forItem($item)]);
    }
}

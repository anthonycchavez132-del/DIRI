<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $catalog=(new \App\Services\DatabaseSearch())->search('');
        $this->render('home/index', ['title' => 'Tu guía para realizar trámites en Bolivia','catalog'=>$catalog]);
    }
}

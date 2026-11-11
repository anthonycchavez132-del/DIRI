<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
class AdminHomeController extends Controller
{
    public function index(): void
    {
        $this->render('admin/resources/home',['title'=>'Administración','activePage'=>'admin-tramites','resources'=>require BASE_PATH.'/config/resources.php']);
    }
}

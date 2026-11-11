<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Services\DatabaseSearch;
use App\Services\CatalogService;
use App\Services\TramiteValidator;
class CatalogController extends Controller
{
    public function search(): void
    {
        $query=$_GET['q']??'';
        $page=TramiteValidator::id($_GET['pagina']??'1');
        $error=null;
        try {
            if(!is_string($query)||$page===null) {
                throw new \InvalidArgumentException('Consulta inválida.');
            }
            $results=(new DatabaseSearch())->search($query,$page);
            $user=\App\Services\AuthService::user();
            if($user!==null&&($_SERVER['REQUEST_METHOD']??'GET')==='GET') {
                (new \App\Services\LibraryService())->remember((int)$user['id'],$query);
            }
        }
        catch(\InvalidArgumentException $e) {
            http_response_code(422);
            $error=$e->getMessage();
            $query='';
            $results=['items'=>[],'total'=>0,'page'=>1];
        }
        $this->render('catalog/search',['title'=>'Buscar trámites','query'=>$query,'results'=>$results,'error'=>$error]);
    }
    public function detail(): void
    {
        $slug=$_GET['slug']??'';
        $item=is_string($slug)?(new CatalogService())->detail($slug):null;
        if($item===null) {
            http_response_code(404);
            $this->render('errors/404',['title'=>'Trámite no disponible']);
            return;
        }
        $guide=(new \App\Services\SimpleGuideService())->forItem($item);
        $this->render('catalog/detail',['title'=>$item['nombre'],'item'=>$item,'guide'=>$guide]);
    }
}

<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\CatalogService;
use App\Services\RecommendationService;
use App\Services\RoutePlanner;
use App\Services\TramiteValidator;
class RecommendationController extends Controller
{
    public function branches(): void
    {
        $slug=$_GET['slug']??'';
        $item=is_string($slug)?(new CatalogService())->detail($slug):null;
        if($item===null) {
            $this->problem(404,'Trámite no disponible.');
            return;
        }
        try {
            $lat=$this->coordinate('latitud',90);
            $lon=$this->coordinate('longitud',180);
            $comparison=(new RecommendationService())->compare((int)AuthService::user()['id'],(int)$item['id'],$lat,$lon);
            $this->render('personal/branches',['title'=>'Comparar sucursales','item'=>$item,'comparison'=>$comparison,'latitude'=>$lat,'longitude'=>$lon]);
        }
        catch(\DomainException $e) {
            $this->problem($e->getCode(),$e->getMessage());
        }
    }
    private function coordinate(string $key,int $limit): ?float
    {
        $value=$_GET[$key]??'';
        if($value==='') {
            return null;
        }
        if(!is_string($value)||strlen($value)>20||!preg_match('/\A-?[0-9]+(?:\.[0-9]+)?\z/',$value)||abs((float)$value)>$limit) {
            throw new \DomainException('Coordenadas inválidas.',422);
        }
        return (float)$value;
    }
    public function routes(): void
    {
        $rows=Database::connection()->query("SELECT id,nombre,descripcion FROM rutas WHERE estado='publicada' ORDER BY nombre LIMIT 100")->fetchAll();
        $rows=array_values(array_filter($rows,fn($row)=>(new RoutePlanner())->route((int)$row['id'])!==null));
        $this->render('personal/routes',['title'=>'Rutas de trámites','rows'=>$rows]);
    }
    public function route(): void
    {
        $id=TramiteValidator::id($_GET['id']??null);
        $route=$id===null?null:(new RoutePlanner())->route($id);
        if($route===null) {
            $this->problem(404,'Ruta no disponible.');
            return;
        }
        foreach($route['tramites'] as &$row) {
            $row['detalle']=(new CatalogService())->detail($row['slug']);
            $row['comparacion']=(new RecommendationService())->compare((int)AuthService::user()['id'],(int)$row['id']);
        }
        unset($row);
        $this->render('personal/route',['title'=>$route['nombre'],'route'=>$route]);
    }
    public function saveRoute(): void
    {
        $id=TramiteValidator::id($_POST['ruta_id']??null);
        $value=$_POST['valor']??'';
        if($id===null||!in_array($value,['0','1'],true)||(new RoutePlanner())->route($id)===null) {
            $this->problem(422,'Ruta o selección inválida.');
            return;
        }
        $pdo=Database::connection();
        $userId=(int)AuthService::user()['id'];
        \App\Services\PlanAccess::requirePremium($userId);
        if($value==='1') {
            $pdo->prepare('INSERT INTO rutas_guardadas (usuario_id,ruta_id) VALUES (?,?) ON DUPLICATE KEY UPDATE usuario_id=VALUES(usuario_id)')->execute([$userId,$id]);
        }
        else {
            $pdo->prepare('DELETE FROM rutas_guardadas WHERE usuario_id=? AND ruta_id=?')->execute([$userId,$id]);
        }
        redirect('ruta?id='.$id);
    }
    private function problem(int $code,string $message): void {
        http_response_code($code);
        $this->render('personal/problem',['title'=>'Revisa la recomendación','message'=>$message]);
    }
}

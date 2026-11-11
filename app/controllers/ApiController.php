<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\ApiResponse;
use App\Core\Database;
use App\Services\AttentionService;
use App\Services\CatalogService;
use App\Services\DatabaseSearch;
use App\Services\TramiteValidator;
/** Consulta pública v1. Las escrituras y la autenticación móvil quedan fuera de v1. */
class ApiController
{
    public function search(): void
    {
        $query=$_GET['q']??'';
        $page=TramiteValidator::id($_GET['pagina']??'1');
        try {
            if(!is_string($query)||$page===null) {
                throw new \InvalidArgumentException('Consulta o página inválida.');
            }
            ApiResponse::send((new DatabaseSearch())->search($query,$page));
        }
        catch(\InvalidArgumentException $e) {
            ApiResponse::error(422,'invalid_query',$e->getMessage());
        }
    }
    public function detail(): void
    {
        $slug=$_GET['slug']??'';
        $item=is_string($slug)?(new CatalogService())->detail($slug):null;
        if($item===null) {
            ApiResponse::error(404,'not_found','Trámite no disponible.');
            return;
        }
        ApiResponse::send($item);
    }
    public function attention(): void
    {
        $id=TramiteValidator::id($_GET['sucursal_id']??null);
        if($id===null) {
            ApiResponse::error(422,'invalid_branch','Selecciona una sucursal.');
            return;
        }
        $q=Database::connection()->prepare("SELECT s.id FROM sucursales s JOIN instituciones i ON i.id=s.institucion_id WHERE s.id=? AND s.estado='activa' AND i.estado=1 AND EXISTS(SELECT 1 FROM tramite_sucursales ts JOIN tramites t ON t.id=ts.tramite_id JOIN categorias c ON c.id=t.categoria_id WHERE ts.sucursal_id=s.id AND ts.disponible=1 AND t.estado='publicado' AND c.estado=1)");
        $q->execute([$id]);
        if(!$q->fetch()) {
            ApiResponse::error(404,'not_found','Sucursal no disponible.');
            return;
        }
        $current=(new AttentionService())->current($id);
        ApiResponse::send(['sucursal_id'=>$id,'status'=>$current===null?'insufficient_recent_data':'available','attention'=>$current]);
    }
}

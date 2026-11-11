<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Services\AuthService;
use App\Services\CatalogService;
use App\Services\ProgressService;
use App\Services\TramiteValidator;
class PersonalController extends Controller
{
    public function library(): void
    {
        $library=(new \App\Services\LibraryService())->read((int)AuthService::user()['id']);
        $this->render('personal/library',['title'=>'Mi biblioteca','library'=>$library]);
    }
    public function clearHistory(): void
    {
        (new \App\Services\LibraryService())->clear((int)AuthService::user()['id']);
        redirect('mi-biblioteca');
    }
    public function checklist(): void
    {
        $slug=$_GET['slug']??'';
        $item=is_string($slug)?(new CatalogService())->detail($slug):null;
        if($item===null) {
            $this->problem(404,'Trámite no disponible.');
            return;
        }
        $state=(new ProgressService())->read((int)AuthService::user()['id'],(int)$item['id']);
        $this->render('personal/checklist',['title'=>'Mi checklist','item'=>$item,'state'=>$state]);
    }
    public function change(): void
    {
        $id=TramiteValidator::id($_POST['tramite_id']??null);
        $action=$_POST['accion']??'';
        $value=$_POST['valor']??'';
        $slug=$_POST['slug']??'';
        if($id===null||!is_string($action)||!is_string($value)||!is_string($slug)) {
            $this->problem(422,'Solicitud inválida.');
            return;
        }
        try {
            (new ProgressService())->change((int)AuthService::user()['id'],$id,$action,$value,TramiteValidator::id($_POST['elemento_id']??null));
        }
        catch(\DomainException $e) {
            $this->problem($e->getCode(),$e->getMessage());
            return;
        }
        redirect('mi-checklist?slug='.rawurlencode($slug));
    }
    private function problem(int $code,string $message): void
    {
        http_response_code($code);
        $this->render('personal/problem',['title'=>'Revisa tu seguimiento','message'=>$message]);
    }
}

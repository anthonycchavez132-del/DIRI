<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\ReportService;
use App\Services\TramiteValidator;
class ReportController extends Controller
{
    public function form(): void {
        $this->renderForm([],[]);
    }
    private function renderForm(array $formData,array $errors): void
    {
        $branches=Database::connection()->query("SELECT s.id,s.nombre,i.nombre AS institucion FROM sucursales s JOIN instituciones i ON i.id=s.institucion_id WHERE s.estado='activa' AND i.estado=1 ORDER BY i.nombre,s.nombre")->fetchAll();
        $this->render('reports/form',['title'=>'Reportar atención','branches'=>$branches,'formData'=>$formData,'errors'=>$errors,'sent'=>isset($_GET['enviado'])]);
    }
    public function submit(): void
    {
        [$formData,$errors]=ReportService::validate($_POST);
        if($errors===[]) {
            try {
                (new ReportService())->submit((int)AuthService::user()['id'],$formData);
                redirect('reportar?enviado=1');
            }
            catch(\DomainException $e) {
                http_response_code($e->getCode());
                $errors['reporte']=$e->getMessage();
            }
        }
        else {
            http_response_code(422);
        }
        $this->renderForm($formData,$errors);
    }
    public function moderation(): void
    {
        $state=$_GET['estado']??'pendiente';
        $page=TramiteValidator::id($_GET['pagina']??'1');
        if(!is_string($state)||!in_array($state,['pendiente','aceptado','rechazado'],true)||$page===null||$page>100000) {
            http_response_code(404);
            $this->render('errors/404',['title'=>'Solicitud inválida']);
            return;
        }
        $q=Database::connection()->prepare('SELECT r.*,s.nombre AS sucursal FROM reportes_atencion r JOIN sucursales s ON s.id=r.sucursal_id WHERE estado_revision=? ORDER BY id DESC LIMIT 30 OFFSET ?');
        $q->bindValue(1,$state);
        $q->bindValue(2,($page-1)*30,\PDO::PARAM_INT);
        $q->execute();
        $rows=$q->fetchAll();
        foreach($rows as &$row) {
            $record=$row;
            unset($record['sucursal']);
            $row['version']=\App\Services\ContenidoService::version($record);
        }
        unset($row);
        $this->render('reports/moderation',['title'=>'Revisión de reportes','rows'=>$rows,'state'=>$state,'page'=>$page]);
    }
    public function moderate(): void
    {
        $id=TramiteValidator::id($_POST['id']??null);
        $decision=$_POST['decision']??'';
        $version=$_POST['version']??'';
        try {
            if($id===null||!is_string($decision)||!is_string($version)) {
                throw new \DomainException('Solicitud inválida.',422);
            }
            (new ReportService())->moderate((int)AuthService::user()['id'],$id,$decision,$version);
            redirect('admin/reportes');
        }
        catch(\DomainException $e) {
            http_response_code($e->getCode());
            $this->render('personal/problem',['title'=>'Revisa el reporte','message'=>$e->getMessage()]);
        }
    }
}

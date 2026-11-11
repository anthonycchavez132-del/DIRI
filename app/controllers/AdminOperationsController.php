<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AdminAttentionService;
use App\Services\AuthService;
use App\Services\TramiteValidator;
class AdminOperationsController extends Controller
{
    public function attention(): void {
        $this->attentionForm([],[]);
    }
    private function attentionForm(array $formData,array $errors): void
    {
        $pdo=Database::connection();
        $this->render('admin/attention',['title'=>'Actualizar atención','formData'=>$formData,'errors'=>$errors,'branches'=>$pdo->query('SELECT id,nombre FROM sucursales ORDER BY nombre')->fetchAll(),'sources'=>$pdo->query('SELECT id,nombre FROM fuentes ORDER BY nombre')->fetchAll(),'saved'=>isset($_GET['guardado'])]);
    }
    public function saveAttention(): void
    {
        try {
            (new AdminAttentionService())->record((int)AuthService::user()['id'],$_POST);
            redirect('admin/atencion?guardado=1');
        }
        catch(\DomainException $e) {
            http_response_code($e->getCode());
            $formData=array_filter($_POST,'is_string');
            $this->attentionForm($formData,['atencion'=>$e->getMessage()]);
        }
    }
    public function audit(): void
    {
        $page=TramiteValidator::id($_GET['pagina']??'1');
        if($page===null||$page>100000) {
            http_response_code(404);
            $this->render('errors/404',['title'=>'Página no disponible']);
            return;
        }
        $q=Database::connection()->prepare('SELECT id,usuario_id,entidad,entidad_id,accion,resumen,fecha FROM auditoria_administrativa ORDER BY id DESC LIMIT 50 OFFSET ?');
        $q->bindValue(1,($page-1)*50,\PDO::PARAM_INT);
        $q->execute();
        $this->render('admin/audit',['title'=>'Auditoría administrativa','rows'=>$q->fetchAll(),'page'=>$page]);
    }
}

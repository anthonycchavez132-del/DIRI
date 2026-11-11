<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Models\Resource;
use App\Services\AuthService;
use App\Services\ContenidoService;
use App\Services\ResourceValidator;
use App\Services\ResourceService;
use App\Services\TramiteValidator;
class AdminResourceController extends Controller
{
    public function remove(): void
    {
        try{
            $resource=$this->resource($_POST);$key=$_POST['key']??'';$version=$_POST['version']??'';
            if(!is_string($key)||!is_string($version)||($_POST['confirmar']??'')!=='1'){throw new \DomainException('Confirma la relación que deseas quitar.',422);}
            (new ResourceService())->remove($resource,$key,$version,(int)AuthService::user()['id']);
            $_SESSION['admin_notice']='Relación quitada y auditada. Revisa el contenido antes de publicarlo.';redirect('admin/gestion?recurso='.$resource->table);
        }catch(\DomainException $e){http_response_code($e->getCode());$this->render('personal/problem',['title'=>'Revisa la relación','message'=>$e->getMessage()]);}
        catch(\PDOException $e){if((int)($e->errorInfo[1]??0)!==1451){throw $e;}http_response_code(422);$this->render('personal/problem',['title'=>'Relación en uso','message'=>'Quita primero las dependencias de esta relación. El contenido se ha conservado.']);}
    }

    private function resource(array $input): Resource
    {
        $name=$input['recurso']??'';
        if(!is_string($name)) {
            throw new \DomainException('Recurso no encontrado.',404);
        }
        return new Resource($name);
    }
    private function renderPage(Resource $resource, array $formData, string $key, string $version, array $errors=[], int $page=1, bool $showForm=false): void
    {
        $notice=$_SESSION['admin_notice']??null;
        unset($_SESSION['admin_notice']);
        $this->render('admin/resources/index',[
        'title'=>$resource->definition['title'],'activePage'=>'admin-tramites','resource'=>$resource,
        'rows'=>$resource->rows($page),'total'=>$resource->count(),'page'=>$page,'options'=>$resource->options(),
        'formData'=>$formData,'key'=>$key,'version'=>$version,'errors'=>$errors,'notice'=>$notice,'showForm'=>$showForm,
        ]);
    }
    public function index(): void
    {
        try {
            $resource=$this->resource($_GET);
            $key=$_GET['key']??'';
            $page=TramiteValidator::id($_GET['pagina']??'1');
            if(!is_string($key)||$page===null||$page>1000000) {
                throw new \DomainException('Solicitud inválida.',404);
            }
            $row=$key===''?null:$resource->find($key);
            if(isset($_GET['nuevo'])&&($resource->definition['create']??true)===false) {
                throw new \DomainException('Creación no disponible.',404);
            }
            if($key!==''&&$row===null) {
                throw new \DomainException('Registro no encontrado.',404);
            }
            $this->renderPage($resource,$row??[],$key,$row===null?'':ContenidoService::version($row),[],$page,$key!==''||isset($_GET['nuevo']));
        }
        catch(\DomainException $exception) {
            $this->problem($exception->getCode());
        }
    }
    public function save(): void
    {
        try {
            $resource=$this->resource($_POST);
            $key=$_POST['key']??'';
            $version=$_POST['version']??'';
            if(!is_string($key)||!is_string($version)) {
                throw new \DomainException('Solicitud inválida.',404);
            }
            $before=$key===''?null:$resource->find($key);
            if($key!==''&&$before===null) {
                throw new \DomainException('Registro no encontrado.',404);
            }
            [$formData,$errors]=ResourceValidator::validate($resource,$_POST,$before);
            if($errors===[]) {
                try {
                    (new ResourceService())->save($resource,$formData,$key,$version,(int)AuthService::user()['id']);
                    $_SESSION['admin_notice']='Registro guardado y auditado.';
                    redirect('admin/gestion?recurso='.$resource->table);
                }
                catch(\PDOException $exception) {
                    if(!in_array((int)($exception->errorInfo[1]??0),[1062,1451,1452,1644,4025,3819],true)) {
                        throw $exception;
                    }
                    $errors['registro']='Revisa duplicados, relaciones y coherencia de los datos.';
                }
                catch(\InvalidArgumentException $exception) {
                    $errors['registro']=$exception->getMessage();
                }
            }
            http_response_code(422);
            $this->renderPage($resource,$formData,$key,$version,$errors,1,true);
        }
        catch(\DomainException $exception) {
            $this->problem($exception->getCode());
        }
    }
    private function problem(int $code): void
    {
        $status=in_array($code,[403,409],true)?$code:404;
        http_response_code($status);
        $this->render('errors/'.$status,['title'=>'Revisa la solicitud','backPath'=>'admin']);
    }
}

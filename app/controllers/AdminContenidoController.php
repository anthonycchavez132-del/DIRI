<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Tramite;
use App\Models\Requisito;
use App\Models\Documento;
use App\Models\TramiteDocumento;
use App\Models\Fuente;
use App\Services\AuthService;
use App\Services\TramiteValidator;
use App\Services\ContenidoValidator;
use App\Services\ContenidoService;
use DomainException;
use PDOException;

class AdminContenidoController extends Controller
{
    private function problem(int $status, string $backPath = 'admin/tramites'): void
    {
        http_response_code($status);
        $this->render('errors/' . $status, ['title' => $status === 404 ? 'Contenido no encontrado' : 'El contenido cambió', 'backPath' => $backPath]);
    }

    private function notice(): ?string
    {
        $notice = $_SESSION['admin_notice'] ?? null;
        unset($_SESSION['admin_notice']);
        return $notice;
    }

    /** El tipo lo define la ruta, nunca un campo enviado por el cliente. */
    private function context(string $kind, array $input, bool $editing): ?array
    {
        $parent = null;
        $record = null;
        $id = null;
        if ($kind !== 'documento') {
            $parentId = TramiteValidator::id($input['tramite_id'] ?? null);
            $parent = $parentId === null ? null : (new Tramite())->find($parentId);
            if ($parent === null) {
                $this->problem(404);
                return null;
            }
        }
        if ($editing) {
            $id = TramiteValidator::id($input[$kind === 'asociacion' ? 'documento_id' : 'id'] ?? null);
            if ($id !== null) {
                $record = match ($kind) {
                    'requisito' => (new Requisito())->find($id, (int) $parent['id']),
                    'documento' => (new Documento())->find($id),
                    'asociacion' => (new TramiteDocumento())->find((int) $parent['id'], $id),
                };
            }
            if ($record === null) {
                $this->problem(404);
                return null;
            }
        }
        return ['parent' => $parent, 'record' => $record, 'id' => $id];
    }

    public function overview(): void
    {
        $context = $this->context('requisito', $_GET, false);
        if ($context === null) {
            return;
        }
        $parent = $context['parent'];
        $this->render('admin/contenido/index', [
            'title' => 'Requisitos y documentos', 'activePage' => 'admin-tramites', 'tramite' => $parent,
            'requirements' => (new Requisito())->forTramite((int) $parent['id']),
            'documents' => (new TramiteDocumento())->forTramite((int) $parent['id']),
            'sources' => array_column((new Fuente())->all(), null, 'id'), 'notice' => $this->notice(),
        ]);
    }

    public function catalog(): void
    {
        $this->render('admin/contenido/catalog', [
            'title' => 'Documentos reutilizables', 'activePage' => 'admin-tramites',
            'documents' => (new Documento())->all(), 'notice' => $this->notice(),
            'sources' => array_column((new Fuente())->all(), null, 'id'),
        ]);
    }

    public function newRequirement(): void { $this->open('requisito', false); }
    public function editRequirement(): void { $this->open('requisito', true); }
    public function createRequirement(): void { $this->write('requisito', false); }
    public function updateRequirement(): void { $this->write('requisito', true); }
    public function newDocument(): void { $this->open('documento', false); }
    public function editDocument(): void { $this->open('documento', true); }
    public function createDocument(): void { $this->write('documento', false); }
    public function updateDocument(): void { $this->write('documento', true); }
    public function newAssociation(): void { $this->open('asociacion', false); }
    public function editAssociation(): void { $this->open('asociacion', true); }
    public function createAssociation(): void { $this->write('asociacion', false); }
    public function updateAssociation(): void { $this->write('asociacion', true); }

    private function open(string $kind, bool $editing): void
    {
        $context = $this->context($kind, $_GET, $editing);
        if ($context === null) {
            return;
        }
        $record = $context['record'];
        $this->form($kind, $context, $record ?? ['estado_verificacion' => 'pendiente'], [], $record === null ? '' : ContenidoService::version($record));
    }

    private function returnPath(array $context): string
    {
        return $context['parent'] === null ? 'admin/documentos' : 'admin/tramites/contenido?tramite_id=' . $context['parent']['id'];
    }

    private function form(string $kind, array $context, array $formData, array $errors, string $version): void
    {
        $labels = ['requisito' => 'requisito', 'documento' => 'documento reutilizable', 'asociacion' => 'condiciones del documento'];
        $this->render('admin/contenido/form', [
            'title' => ($context['id'] === null ? 'Agregar ' : 'Editar ') . $labels[$kind],
            'activePage' => 'admin-tramites', 'kind' => $kind, 'context' => $context,
            'formData' => $formData, 'errors' => $errors, 'version' => $version,
            'sources' => (new Fuente())->all(), 'documents' => (new Documento())->all(),
            'backPath' => $this->returnPath($context),
            'usageCount' => $kind === 'documento' && $context['id'] !== null ? count((new TramiteDocumento())->parents($context['id'])) : 0,
        ]);
    }

    private function write(string $kind, bool $editing): void
    {
        $context = $this->context($kind, $_POST, $editing);
        if ($context === null) {
            return;
        }
        [$formData, $errors] = ContenidoValidator::validate($kind, $_POST);
        $version = is_string($_POST['version'] ?? null) ? $_POST['version'] : '';
        if ($errors === []) {
            try {
                $service = new ContenidoService();
                $actor = (int) AuthService::user()['id'];
                $parentId = (int) ($context['parent']['id'] ?? 0);
                match ($kind) {
                    'requisito' => $service->requisito($parentId, $context['id'], $formData, $version, $actor),
                    'documento' => $service->documento($context['id'], $formData, $version, $actor),
                    'asociacion' => $service->asociacion($parentId, $formData['documento_id'], $editing, $formData, $version, $actor),
                };
                $_SESSION['admin_notice'] = 'Contenido guardado. Los trámites afectados quedan pendientes de revisión.';
                redirect($this->returnPath($context));
            } catch (DomainException $exception) {
                $this->problem($exception->getCode() === 404 ? 404 : 409, $this->returnPath($context));
                return;
            } catch (PDOException $exception) {
                $code = (int) ($exception->errorInfo[1] ?? 0);
                if ($code === 1062) {
                    $errors['duplicate'] = $kind === 'documento' ? 'Ya existe un documento con ese nombre. Usa el catálogo o elige otro nombre.' : 'Ese documento ya está asociado al trámite. Edita sus condiciones.';
                } elseif (in_array($code, [1452,1644], true)) {
                    $errors['reference'] = 'El trámite, documento o fuente cambió. Revisa tus selecciones.';
                } else {
                    throw $exception;
                }
            }
        }
        http_response_code(422);
        $this->form($kind, $context, $formData, $errors, $version);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Tramite;
use App\Models\Categoria;
use App\Models\Fuente;
use App\Services\AuthService;
use App\Services\TramiteService;
use App\Services\TramiteValidator;
use DomainException;
use PDOException;

class AdminTramiteController extends Controller
{
    private function notFound(): void
    {
        http_response_code(404);
        $this->render('errors/404', ['title' => 'Trámite no encontrado']);
    }

    private function row(array $input): ?array
    {
        $id = TramiteValidator::id($input['id'] ?? null);
        return $id === null ? null : (new Tramite())->find($id);
    }

    public function index(): void
    {
        $page = TramiteValidator::id($_GET['pagina'] ?? '1');
        $state = $_GET['estado'] ?? '';
        if ($page === null || $page > 1000000 || !is_string($state) || ($state !== '' && !isset(TramiteValidator::STATES[$state]))) {
            $this->notFound();
            return;
        }
        $result = (new Tramite())->page($page, $state);
        if ($page > $result['pages']) {
            $this->notFound();
            return;
        }
        $notice = $_SESSION['admin_notice'] ?? null;
        unset($_SESSION['admin_notice']);
        $this->render('admin/tramites/index', [
            'title' => 'Administrar trámites', 'activePage' => 'admin-tramites',
            'result' => $result, 'page' => $page, 'state' => $state, 'notice' => $notice,
        ]);
    }

    public function show(): void
    {
        $row = $this->row($_GET);
        if ($row === null) {
            $this->notFound();
            return;
        }
        $this->render('admin/tramites/show', [
            'title' => 'Consultar trámite', 'activePage' => 'admin-tramites', 'tramite' => $row,
            'categories' => array_column((new Categoria())->all(), null, 'id'),
            'sources' => array_column((new Fuente())->all(), null, 'id'),
        ]);
    }

    private function form(array $data, array $errors = [], ?int $id = null, string $version = ''): void
    {
        $this->render('admin/tramites/form', [
            'title' => $id === null ? 'Crear trámite' : 'Editar trámite', 'activePage' => 'admin-tramites',
            'formData' => $data, 'errors' => $errors, 'id' => $id, 'version' => $version,
            'categories' => (new Categoria())->active(), 'sources' => (new Fuente())->all(),
        ]);
    }

    public function create(): void
    {
        $this->form(['estado' => 'borrador', 'estado_verificacion' => 'pendiente']);
    }

    public function edit(): void
    {
        $row = $this->row($_GET);
        if ($row === null) {
            $this->notFound();
            return;
        }
        $this->form($row, [], (int) $row['id'], TramiteService::version($row));
    }

    public function save(): void
    {
        $this->write(false);
    }

    public function update(): void
    {
        $this->write(true);
    }

    private function write(bool $editing): void
    {
        $id = $editing ? TramiteValidator::id($_POST['id'] ?? null) : null;
        if ($editing && ($id === null || (new Tramite())->find($id) === null)) {
            $this->notFound();
            return;
        }
        [$data, $errors] = TramiteValidator::validate($_POST, $id);
        $version = is_string($_POST['version'] ?? null) ? $_POST['version'] : '';
        if ($errors === []) {
            try {
                $service = new TramiteService();
                $actorId = (int) AuthService::user()['id'];
                if ($editing) {
                    $service->change($id, $data, $version, $actorId);
                } else {
                    $service->create($data, $actorId);
                }
                $_SESSION['admin_notice'] = $editing ? 'Trámite actualizado.' : 'Trámite creado.';
                redirect('admin/tramites');
            } catch (DomainException $exception) {
                $this->conflict($exception);
                return;
            } catch (\InvalidArgumentException $exception) {
                $errors['general']=$exception->getMessage();
            } catch (PDOException $exception) {
                $code = (int) ($exception->errorInfo[1] ?? 0);
                if ($code === 1062) {
                    $errors['slug'] = 'Ese identificador ya está en uso. Elige otro.';
                } elseif ($code === 1452) {
                    $errors['general'] = 'La categoría o fuente cambió. Revisa tus selecciones.';
                } else {
                    throw $exception;
                }
            }
        }
        http_response_code(422);
        $this->form($data, $errors, $id, $version);
    }

    public function confirmDeactivate(): void
    {
        $row = $this->row($_GET);
        if ($row === null) {
            $this->notFound();
            return;
        }
        $this->render('admin/tramites/deactivate', [
            'title' => 'Desactivar trámite', 'activePage' => 'admin-tramites',
            'tramite' => $row, 'version' => TramiteService::version($row),
        ]);
    }

    public function deactivate(): void
    {
        $id = TramiteValidator::id($_POST['id'] ?? null);
        if ($id === null) {
            $this->notFound();
            return;
        }
        try {
            $version = is_string($_POST['version'] ?? null) ? $_POST['version'] : '';
            (new TramiteService())->change($id, [], $version, (int) AuthService::user()['id'], true);
            $_SESSION['admin_notice'] = 'Trámite desactivado. Su información y relaciones se conservan.';
            redirect('admin/tramites');
        } catch (DomainException $exception) {
            $this->conflict($exception);
        }
    }

    private function conflict(DomainException $exception): void
    {
        $status = $exception->getCode() === 404 ? 404 : 409;
        http_response_code($status);
        $this->render('errors/' . $status, ['title' => $status === 404 ? 'Trámite no encontrado' : 'El trámite cambió']);
    }
}

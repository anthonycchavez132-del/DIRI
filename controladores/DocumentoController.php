<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/Documento.php';
require_once __DIR__ . '/UsuarioController.php';

final class DocumentoController
{
    public function listar(): void
    {
        if (!(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        $busqueda = $_GET['buscar'] ?? '';

        if (!is_string($busqueda) || mb_strlen($busqueda, 'UTF-8') > 200) {
            http_response_code(400);
            echo 'Escribe una búsqueda de hasta 200 caracteres.';
            return;
        }

        $busqueda = trim($busqueda);
        $modelo = new Documento();
        $documentos = $modelo->listar($busqueda);
        $mensaje = $_SESSION['mensaje_documentos'] ?? '';
        unset($_SESSION['mensaje_documentos']);

        require __DIR__ . '/../vistas/documentos.php';
    }

    public function formulario(): void
    {
        if (!(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        $id = null;
        $errores = [];
        $datos = [
            'nombre' => '',
            'descripcion' => '',
            'estado' => 'activo',
        ];

        if (($_GET['pagina'] ?? '') === 'editar_documento' && !isset($_GET['id'])) {
            http_response_code(400);
            echo 'Indica el documento que quieres editar.';
            return;
        }

        if (isset($_GET['id'])) {
            $id = $this->idValido($_GET['id']);

            if ($id === null) {
                http_response_code(400);
                echo 'El identificador del documento es inválido.';
                return;
            }

            $modelo = new Documento();
            $documento = $modelo->buscarPorId($id);

            if ($documento === null) {
                http_response_code(404);
                echo 'El documento solicitado no existe.';
                return;
            }

            $datos = $documento;
            $datos['descripcion'] = $documento['descripcion'] ?? '';
        }

        require __DIR__ . '/../vistas/formulario_documento.php';
    }

    public function guardar(): void
    {
        if (!$this->exigirPost() || !(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        $identificador = $_POST['id'] ?? '';
        $id = $identificador === '' ? null : $this->idValido($identificador);

        if ($identificador !== '' && $id === null) {
            http_response_code(400);
            echo 'El identificador del documento es inválido.';
            return;
        }

        $datos = [
            'nombre' => trim($this->campo('nombre')),
            'descripcion' => trim($this->campo('descripcion')),
            'estado' => $this->campo('estado'),
        ];
        $errores = [];

        if (!$this->csrfValido()) {
            http_response_code(403);
            $errores[] = 'El formulario venció. Revisa los datos y vuelve a guardarlo.';
        } else {
            $errores = $this->validar($datos);

            if ($errores === []) {
                $modelo = new Documento();

                if ($id !== null && $modelo->buscarPorId($id) === null) {
                    http_response_code(404);
                    echo 'El documento solicitado no existe.';
                    return;
                }

                $valores = $datos;
                $valores['descripcion'] = $datos['descripcion'] === '' ? null : $datos['descripcion'];

                try {
                    if ($id === null) {
                        $modelo->crear($valores);
                        $_SESSION['mensaje_documentos'] = 'El documento fue creado.';
                    } else {
                        $modelo->actualizar($id, $valores);
                        $_SESSION['mensaje_documentos'] = 'El documento fue actualizado.';
                    }

                    header('Location: index.php?pagina=documentos', true, 303);
                    return;
                } catch (PDOException $error) {
                    if ((int) ($error->errorInfo[1] ?? 0) !== 1062) {
                        throw $error;
                    }

                    $errores[] = 'Ya existe un documento con ese nombre. Puedes editar el registro existente.';
                }
            }

            http_response_code(422);
        }

        require __DIR__ . '/../vistas/formulario_documento.php';
    }

    public function desactivar(): void
    {
        if (!$this->exigirPost() || !(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        if (!$this->csrfValido()) {
            http_response_code(403);
            echo 'El formulario venció. Actualiza el listado y vuelve a intentarlo.';
            return;
        }

        $id = $this->idValido($_POST['id'] ?? null);

        if ($id === null) {
            http_response_code(400);
            echo 'El identificador del documento es inválido.';
            return;
        }

        $modelo = new Documento();

        if ($modelo->buscarPorId($id) === null) {
            http_response_code(404);
            echo 'El documento solicitado no existe.';
            return;
        }

        $_SESSION['mensaje_documentos'] = $modelo->desactivar($id)
            ? 'El documento fue desactivado y conserva su historial.'
            : 'El documento ya estaba inactivo.';

        header('Location: index.php?pagina=documentos', true, 303);
    }

    private function validar(array $datos): array
    {
        $errores = [];

        foreach (array_keys($datos) as $campo) {
            if (array_key_exists($campo, $_POST) && !is_string($_POST[$campo])) {
                return ['Los campos del formulario deben contener texto.'];
            }
        }

        if ($datos['nombre'] === '' || !mb_check_encoding($datos['nombre'], 'UTF-8') || mb_strlen($datos['nombre'], 'UTF-8') > 200) {
            $errores[] = 'Escribe un nombre de hasta 200 caracteres.';
        }

        if (!mb_check_encoding($datos['descripcion'], 'UTF-8') || mb_strlen($datos['descripcion'], 'UTF-8') > 5000) {
            $errores[] = 'La descripción admite hasta 5000 caracteres.';
        }

        if (!in_array($datos['estado'], ['activo', 'inactivo'], true)) {
            $errores[] = 'Selecciona un estado válido.';
        }

        return $errores;
    }

    private function exigirPost(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Utiliza el formulario para realizar esta operación.';
            return false;
        }

        return true;
    }

    private function campo(string $nombre): string
    {
        $valor = $_POST[$nombre] ?? '';

        return is_string($valor) ? $valor : '';
    }

    private function idValido(mixed $valor): ?int
    {
        if (!is_string($valor)) {
            return null;
        }

        $id = filter_var($valor, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 4294967295],
        ]);

        return $id === false ? null : $id;
    }

    private function csrfValido(): bool
    {
        $token = $_POST['csrf'] ?? null;

        return is_string($token) && hash_equals($_SESSION['csrf'], $token);
    }
}

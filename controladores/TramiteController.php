<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/Tramite.php';
require_once __DIR__ . '/../modelos/Documento.php';
require_once __DIR__ . '/UsuarioController.php';

final class TramiteController
{
    public function listar(): void
    {
        $busqueda = $_GET['buscar'] ?? '';

        if (!is_string($busqueda) || mb_strlen($busqueda, 'UTF-8') > 200) {
            http_response_code(400);
            echo 'Escribe una búsqueda de hasta 200 caracteres.';
            return;
        }

        $busqueda = trim($busqueda);
        $usuario = (new UsuarioController())->usuarioActual();
        $esAdministrador = $usuario !== null && $usuario['rol'] === 'administrador';
        $modelo = new Tramite();
        $tramites = $modelo->listar($busqueda, $esAdministrador);
        $mensaje = $_SESSION['mensaje_tramites'] ?? '';
        unset($_SESSION['mensaje_tramites']);

        require __DIR__ . '/../vistas/tramites.php';
    }

    public function formulario(): void
    {
        if (!(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        $id = null;
        $errores = [];
        $requisitos = [];
        $datos = [
            'nombre' => '',
            'descripcion' => '',
            'fuente_url' => '',
            'fecha_verificacion' => '',
            'estado' => 'activo',
        ];

        if (($_GET['pagina'] ?? '') === 'editar_tramite' && !isset($_GET['id'])) {
            http_response_code(400);
            echo 'Indica el trámite que quieres editar.';
            return;
        }

        if (isset($_GET['id'])) {
            $id = $this->idValido($_GET['id']);

            if ($id === null) {
                http_response_code(400);
                echo 'El identificador del trámite es inválido.';
                return;
            }

            $modelo = new Tramite();
            $tramite = $modelo->buscarPorId($id);

            if ($tramite === null) {
                http_response_code(404);
                echo 'El trámite solicitado no existe.';
                return;
            }

            $datos = $tramite;
            $datos['fuente_url'] = $tramite['fuente_url'] ?? '';
            $datos['fecha_verificacion'] = $tramite['fecha_verificacion'] ?? '';

            foreach ($modelo->requisitos($id) as $requisito) {
                $requisitos[(int) $requisito['documento_id']] = $requisito;
            }
        }

        $documentos = (new Documento())->listar('');
        require __DIR__ . '/../vistas/formulario_tramite.php';
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
            echo 'El identificador del trámite es inválido.';
            return;
        }

        $datos = [
            'nombre' => trim($this->campo('nombre')),
            'descripcion' => trim($this->campo('descripcion')),
            'fuente_url' => trim($this->campo('fuente_url')),
            'fecha_verificacion' => $this->campo('fecha_verificacion'),
            'estado' => $this->campo('estado'),
        ];
        $errores = [];
        $requisitos = [];

        if (!$this->csrfValido()) {
            http_response_code(403);
            $errores[] = 'El formulario venció. Revisa los datos y vuelve a guardarlo.';
        } else {
            $errores = $this->validar($datos);
            $requisitos = $this->leerRequisitos($errores);

            if ($errores === []) {
                $modelo = new Tramite();

                if ($id !== null && $modelo->buscarPorId($id) === null) {
                    http_response_code(404);
                    echo 'El trámite solicitado no existe.';
                    return;
                }

                $valores = $datos;
                $valores['fuente_url'] = $datos['fuente_url'] === '' ? null : $datos['fuente_url'];
                $valores['fecha_verificacion'] = $datos['fecha_verificacion'] === '' ? null : $datos['fecha_verificacion'];

                try {
                    if ($id === null) {
                        $modelo->crear($valores, $requisitos ?? []);
                        $_SESSION['mensaje_tramites'] = 'El trámite fue creado.';
                    } else {
                        $modelo->actualizar($id, $valores, $requisitos);
                        $_SESSION['mensaje_tramites'] = 'El trámite fue actualizado.';
                    }

                    header('Location: index.php?pagina=tramites', true, 303);
                    return;
                } catch (DomainException $error) {
                    $errores[] = $error->getMessage();
                }
            }

            http_response_code(422);
        }

        $documentos = (new Documento())->listar('');
        $requisitos = $requisitos ?? [];
        require __DIR__ . '/../vistas/formulario_tramite.php';
    }

    private function leerRequisitos(array &$errores): ?array
    {
        // Conserva los requisitos al recibir un formulario anterior a esta sección.
        if (!isset($_POST['requisitos_presentes'])) {
            return null;
        }

        $entrada = $_POST['requisitos'] ?? [];
        $requisitos = [];

        if (!is_array($entrada)) {
            $errores[] = 'Revisa la selección de documentos.';
            return [];
        }

        foreach ($entrada as $clave => $fila) {
            if (!is_array($fila)) {
                $errores[] = 'Hay un requisito inválido.';
                break;
            }

            if (!isset($fila['seleccionado'])) {
                continue;
            }

            $documentoId = $this->idValido((string) $clave);
            $vigencia = $fila['vigencia_dias'] ?? '';
            $dias = $vigencia === '' ? null : filter_var($vigencia, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 65535],
            ]);
            $observaciones = $fila['observaciones'] ?? '';
            $obligatorio = $fila['obligatorio'] ?? '0';

            if ($documentoId === null || $fila['seleccionado'] !== '1' || !in_array($obligatorio, ['0', '1'], true)
                || (!is_string($vigencia)) || $dias === false || !is_string($observaciones)
                || !mb_check_encoding($observaciones, 'UTF-8') || mb_strlen($observaciones, 'UTF-8') > 5000) {
                $errores[] = 'Revisa los documentos, su vigencia y sus observaciones.';
                break;
            }

            $requisitos[$documentoId] = [
                'obligatorio' => (int) $obligatorio,
                'vigencia_dias' => $dias,
                'observaciones' => trim($observaciones) === '' ? null : trim($observaciones),
            ];
        }

        return $requisitos;
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
            echo 'El identificador del trámite es inválido.';
            return;
        }

        $modelo = new Tramite();

        if ($modelo->buscarPorId($id) === null) {
            http_response_code(404);
            echo 'El trámite solicitado no existe.';
            return;
        }

        $_SESSION['mensaje_tramites'] = $modelo->desactivar($id)
            ? 'El trámite fue desactivado y conserva su historial.'
            : 'El trámite ya estaba inactivo.';

        header('Location: index.php?pagina=tramites', true, 303);
    }

    private function validar(array $datos): array
    {
        $errores = [];

        if ($datos['nombre'] === '' || mb_strlen($datos['nombre'], 'UTF-8') > 200) {
            $errores[] = 'Escribe un nombre de hasta 200 caracteres.';
        }

        if ($datos['descripcion'] === '' || mb_strlen($datos['descripcion'], 'UTF-8') > 5000) {
            $errores[] = 'Escribe una descripción de hasta 5000 caracteres.';
        }

        if (!in_array($datos['estado'], ['activo', 'inactivo'], true)) {
            $errores[] = 'Selecciona un estado válido.';
        }

        if ($datos['fuente_url'] !== '') {
            $protocolo = parse_url($datos['fuente_url'], PHP_URL_SCHEME);

            if (strlen($datos['fuente_url']) > 2048 || !filter_var($datos['fuente_url'], FILTER_VALIDATE_URL) || !is_string($protocolo) || !in_array(strtolower($protocolo), ['http', 'https'], true)) {
                $errores[] = 'La fuente debe ser una dirección HTTP o HTTPS válida de hasta 2048 caracteres.';
            }
        }

        if ($datos['fecha_verificacion'] !== '') {
            $formatoValido = preg_match('/\A[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}\z/', $datos['fecha_verificacion']) === 1;
            $fecha = $formatoValido
                ? DateTimeImmutable::createFromFormat('!Y-m-d', $datos['fecha_verificacion'])
                : false;

            if ($fecha === false || $fecha->format('Y-m-d') !== $datos['fecha_verificacion'] || $fecha > new DateTimeImmutable('today')) {
                $errores[] = 'La fecha de verificación debe ser válida y corresponder a hoy o a un día anterior.';
            }

            if ($datos['fuente_url'] === '') {
                $errores[] = 'Indica la fuente consultada para registrar una fecha de verificación.';
            }
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

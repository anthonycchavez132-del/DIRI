<?php

declare(strict_types=1);

require_once __DIR__ . '/UsuarioController.php';
require_once __DIR__ . '/../modelos/Gestion.php';
require_once __DIR__ . '/../modelos/Tramite.php';
require_once __DIR__ . '/../modelos/Documento.php';
require_once __DIR__ . '/../modelos/Establecimiento.php';

final class GestionController
{
    public function listar(): void
    {
        if (!(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        $filtro = $_GET['tramite_id'] ?? '';
        $tramiteId = $filtro === '' ? null : $this->entero($filtro);

        if ($filtro !== '' && $tramiteId === null) {
            http_response_code(400);
            echo 'Selecciona un trámite válido.';
            return;
        }

        $tramites = (new Tramite())->listar('', true);
        $gestiones = (new Gestion())->listar($tramiteId);
        $mensaje = $_SESSION['mensaje_gestiones'] ?? '';
        $error = $_SESSION['error_gestiones'] ?? '';
        unset($_SESSION['mensaje_gestiones'], $_SESSION['error_gestiones']);

        require __DIR__ . '/../vistas/gestiones.php';
    }

    public function formulario(): void
    {
        if (!(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        $id = null;
        $datos = $this->datosIniciales();

        if (($_GET['pagina'] ?? '') === 'editar_gestion' || isset($_GET['id'])) {
            $id = $this->entero($_GET['id'] ?? null);

            if ($id === null) {
                http_response_code(400);
                echo 'Indica la gestión que quieres editar.';
                return;
            }

            $gestion = (new Gestion())->buscarPorId($id);

            if ($gestion === null) {
                http_response_code(404);
                echo 'La gestión solicitada no existe.';
                return;
            }

            $datos = $gestion;
            $datos['documento_resultado_id'] = $gestion['documento_resultado_id'] ?? '';
        } elseif (isset($_GET['tramite_id']) && $_GET['tramite_id'] !== '') {
            $tramiteId = $this->entero($_GET['tramite_id']);

            if ($tramiteId === null || (new Tramite())->buscarPorId($tramiteId) === null) {
                http_response_code(400);
                echo 'Selecciona un trámite existente.';
                return;
            }

            $datos['tramite_id'] = $tramiteId;
        }

        $this->mostrarFormulario($id, $datos, []);
    }

    public function guardar(): void
    {
        if (!$this->exigirPost() || !(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        $identificador = $_POST['id'] ?? '';
        $id = $identificador === '' ? null : $this->entero($identificador);

        if ($identificador !== '' && $id === null) {
            http_response_code(400);
            echo 'El identificador de la gestión es inválido.';
            return;
        }

        $datos = $this->datosIniciales();
        $errores = [];

        foreach (['tramite_id', 'documento_resultado_id', 'nombre', 'descripcion', 'orden', 'estado'] as $campo) {
            $valor = $_POST[$campo] ?? '';

            if (!is_string($valor)) {
                $errores[] = 'Revisa el campo ' . $campo . '.';
                continue;
            }

            $datos[$campo] = trim($valor);
        }

        $token = $_POST['csrf'] ?? null;

        if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(403);
            $this->mostrarFormulario($id, $datos, ['El formulario venció. Vuelve a enviarlo.']);
            return;
        }

        $tramiteId = $this->entero($datos['tramite_id']);
        $documentoId = $datos['documento_resultado_id'] === '' ? null : $this->entero($datos['documento_resultado_id']);
        $orden = $this->entero($datos['orden'], 65535);

        if ($tramiteId === null || $orden === null || ($datos['documento_resultado_id'] !== '' && $documentoId === null)) {
            $errores[] = 'Selecciona un trámite válido y un orden entre 1 y 65535. Revisa el documento de resultado.';
        }

        foreach (['nombre' => 200, 'descripcion' => 5000] as $campo => $limite) {
            if ($datos[$campo] === '' || !mb_check_encoding($datos[$campo], 'UTF-8') || mb_strlen($datos[$campo], 'UTF-8') > $limite) {
                $errores[] = 'El campo ' . $campo . ' es obligatorio y admite hasta ' . $limite . ' caracteres.';
            }
        }

        if (!in_array($datos['estado'], ['activo', 'inactivo'], true)) {
            $errores[] = 'Selecciona un estado válido.';
        }

        $dependencias = $_POST['dependencias'] ?? [];

        if (!is_array($dependencias)) {
            $errores[] = 'Selecciona las gestiones previas usando las casillas.';
        } else {
            foreach ($dependencias as $valor) {
                $previaId = $this->entero($valor);

                if ($previaId === null) {
                    $errores[] = 'Hay una gestión previa inválida.';
                    break;
                }

                $datos['dependencias'][] = $previaId;
            }

            $datos['dependencias'] = array_values(array_unique($datos['dependencias']));
        }

        $esperas = $_POST['esperas'] ?? [];

        if (!is_array($esperas)) {
            $errores[] = 'Revisa los días de espera entre gestiones.';
        } else {
            foreach ($datos['dependencias'] as $previaId) {
                $valor = $esperas[$previaId] ?? '0';
                $dias = is_string($valor) ? filter_var($valor, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 0, 'max_range' => 65535],
                ]) : false;

                if ($dias === false) {
                    $errores[] = 'Los días de espera deben ser números enteros entre 0 y 65535.';
                    break;
                }

                $datos['esperas'][$previaId] = $dias;
            }
        }

        $establecimientos = $_POST['establecimientos'] ?? [];

        if (!is_array($establecimientos)) {
            $errores[] = 'Selecciona los establecimientos usando las casillas.';
        } else {
            foreach ($establecimientos as $clave => $fila) {
                if (!is_array($fila)) {
                    $errores[] = 'Hay un establecimiento inválido.';
                    break;
                }

                if (!array_key_exists('seleccionado', $fila)) {
                    continue;
                }

                $establecimientoId = $this->entero($clave);
                $duracion = $fila['duracion'] ?? '';
                $minutos = $duracion === '' ? null : $this->entero($duracion, 65535);

                if ($fila['seleccionado'] !== '1' || $establecimientoId === null || ($duracion !== '' && $minutos === null)) {
                    $errores[] = 'Revisa los establecimientos y sus duraciones. Usa minutos enteros positivos o deja la duración vacía.';
                    break;
                }

                $datos['establecimientos'][$establecimientoId] = $minutos;
            }
        }

        if ($errores === []) {
            try {
                (new Gestion())->guardar($id, [
                    'tramite_id' => $tramiteId,
                    'documento_resultado_id' => $documentoId,
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'],
                    'orden' => $orden,
                    'estado' => $datos['estado'],
                ], $datos['dependencias'], $datos['establecimientos'], $datos['esperas']);

                $_SESSION['mensaje_gestiones'] = 'La gestión y sus relaciones fueron guardadas.';
                header('Location: index.php?pagina=gestiones&tramite_id=' . $tramiteId, true, 303);
                return;
            } catch (DomainException $error) {
                $errores[] = $error->getMessage();
            } catch (PDOException $error) {
                if ((int) ($error->errorInfo[1] ?? 0) !== 1062) {
                    throw $error;
                }

                $errores[] = 'Ya existe una gestión con ese orden en el trámite.';
            }
        }

        http_response_code(422);
        $this->mostrarFormulario($id, $datos, $errores);
    }

    public function desactivar(): void
    {
        if (!$this->exigirPost() || !(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        $token = $_POST['csrf'] ?? null;

        if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(403);
            echo 'Actualiza el listado y vuelve a intentarlo.';
            return;
        }

        $id = $this->entero($_POST['id'] ?? null);

        if ($id === null) {
            http_response_code(400);
            echo 'Selecciona una gestión válida.';
            return;
        }

        try {
            $_SESSION['mensaje_gestiones'] = (new Gestion())->desactivar($id)
                ? 'La gestión fue desactivada.' : 'La gestión ya estaba inactiva.';
            header('Location: index.php?pagina=gestiones', true, 303);
        } catch (DomainException $error) {
            http_response_code(422);
            $_SESSION['error_gestiones'] = $error->getMessage();
            $this->listar();
        }
    }

    private function datosIniciales(): array
    {
        return [
            'tramite_id' => '', 'documento_resultado_id' => '', 'nombre' => '',
            'descripcion' => '', 'orden' => '', 'estado' => 'activo',
            'dependencias' => [], 'establecimientos' => [], 'esperas' => [],
        ];
    }

    private function mostrarFormulario(?int $id, array $datos, array $errores): void
    {
        $tramites = (new Tramite())->listar('', true);
        $documentos = (new Documento())->listar('');
        $establecimientos = (new Establecimiento())->listar('');
        $tramiteId = $this->entero($datos['tramite_id']);
        $gestionesPrevias = $tramiteId === null ? [] : (new Gestion())->listar($tramiteId);

        require __DIR__ . '/../vistas/formulario_gestion.php';
    }

    private function entero(mixed $valor, int $maximo = 4294967295): ?int
    {
        if (!is_string($valor) && !is_int($valor)) {
            return null;
        }

        $numero = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $maximo]]);

        return $numero === false ? null : $numero;
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
}

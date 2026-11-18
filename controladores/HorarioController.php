<?php

declare(strict_types=1);

require_once __DIR__ . '/UsuarioController.php';
require_once __DIR__ . '/../modelos/Horario.php';
require_once __DIR__ . '/../modelos/Establecimiento.php';

final class HorarioController
{
    public function listar(): void
    {
        if (!(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        $filtro = $_GET['establecimiento_id'] ?? '';
        $establecimientoId = $filtro === '' ? null : $this->entero($filtro);

        if ($filtro !== '' && $establecimientoId === null) {
            http_response_code(400);
            echo 'Selecciona un establecimiento válido.';
            return;
        }

        $establecimientos = (new Establecimiento())->listar('');
        $horarios = (new Horario())->listar($establecimientoId);
        $dias = $this->dias();
        $mensaje = $_SESSION['mensaje_horarios'] ?? '';
        unset($_SESSION['mensaje_horarios']);

        require __DIR__ . '/../vistas/horarios.php';
    }

    public function formulario(): void
    {
        if (!(new UsuarioController())->exigirAdministrador()) {
            return;
        }

        $id = null;
        $datos = ['establecimiento_id' => '', 'dia_semana' => '', 'hora_apertura' => '', 'hora_cierre' => '', 'estado' => 'activo'];

        if (($_GET['pagina'] ?? '') === 'editar_horario' || isset($_GET['id'])) {
            $id = $this->entero($_GET['id'] ?? null);

            if ($id === null) {
                http_response_code(400);
                echo 'Indica el horario que quieres editar.';
                return;
            }

            $horario = (new Horario())->buscarPorId($id);

            if ($horario === null) {
                http_response_code(404);
                echo 'El horario solicitado no existe.';
                return;
            }

            $datos = $horario;
        } elseif (isset($_GET['establecimiento_id'])) {
            $datos['establecimiento_id'] = $this->entero($_GET['establecimiento_id']) ?? '';
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
            echo 'El identificador del horario es inválido.';
            return;
        }

        $datos = [];
        $errores = [];

        foreach (['establecimiento_id', 'dia_semana', 'hora_apertura', 'hora_cierre', 'estado'] as $campo) {
            $valor = $_POST[$campo] ?? '';
            $datos[$campo] = is_string($valor) ? $valor : '';

            if (!is_string($valor)) {
                $errores[] = 'Revisa el campo ' . $campo . '.';
            }
        }

        $token = $_POST['csrf'] ?? null;

        if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(403);
            $this->mostrarFormulario($id, $datos, ['El formulario venció. Vuelve a enviarlo.']);
            return;
        }

        $establecimientoId = $this->entero($datos['establecimiento_id']);
        $dia = $this->entero($datos['dia_semana'], 7);
        $apertura = $this->hora($datos['hora_apertura']);
        $cierre = $this->hora($datos['hora_cierre']);

        if ($establecimientoId === null || $dia === null) {
            $errores[] = 'Selecciona un establecimiento y un día válidos.';
        }

        if ($apertura === null || $cierre === null || $cierre <= $apertura) {
            $errores[] = 'Indica horas válidas. La hora de cierre debe ser posterior a la apertura del mismo día.';
        }

        if (!in_array($datos['estado'], ['activo', 'inactivo'], true)) {
            $errores[] = 'Selecciona un estado válido.';
        }

        if ($errores === []) {
            try {
                (new Horario())->guardar($id, [
                    'establecimiento_id' => $establecimientoId,
                    'dia_semana' => $dia,
                    'hora_apertura' => $apertura,
                    'hora_cierre' => $cierre,
                    'estado' => $datos['estado'],
                ]);

                $_SESSION['mensaje_horarios'] = 'El horario fue guardado.';
                header('Location: index.php?pagina=horarios&establecimiento_id=' . $establecimientoId, true, 303);
                return;
            } catch (DomainException $error) {
                $errores[] = $error->getMessage();
            } catch (PDOException $error) {
                if ((int) ($error->errorInfo[1] ?? 0) !== 1062) {
                    throw $error;
                }

                $errores[] = 'Ya existe un horario con esa apertura para el establecimiento y día seleccionados. Edita el registro existente.';
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

        if ($id === null || (new Horario())->buscarPorId($id) === null) {
            http_response_code(404);
            echo 'El horario solicitado no existe.';
            return;
        }

        $_SESSION['mensaje_horarios'] = (new Horario())->desactivar($id)
            ? 'El horario fue desactivado.' : 'El horario ya estaba inactivo.';
        header('Location: index.php?pagina=horarios', true, 303);
    }

    private function mostrarFormulario(?int $id, array $datos, array $errores): void
    {
        $establecimientos = (new Establecimiento())->listar('');
        $dias = $this->dias();

        require __DIR__ . '/../vistas/formulario_horario.php';
    }

    private function dias(): array
    {
        return [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    }

    private function entero(mixed $valor, int $maximo = 4294967295): ?int
    {
        if (!is_string($valor) && !is_int($valor)) {
            return null;
        }

        $numero = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $maximo]]);

        return $numero === false ? null : $numero;
    }

    private function hora(string $valor): ?string
    {
        if (preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?\z/', $valor) !== 1) {
            return null;
        }

        return strlen($valor) === 5 ? $valor . ':00' : $valor;
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

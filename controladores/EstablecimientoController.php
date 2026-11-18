<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/Establecimiento.php';
require_once __DIR__ . '/UsuarioController.php';

final class EstablecimientoController
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
        $modelo = new Establecimiento();
        $establecimientos = $modelo->listar($busqueda);
        $mensaje = $_SESSION['mensaje_establecimientos'] ?? '';
        unset($_SESSION['mensaje_establecimientos']);

        require __DIR__ . '/../vistas/establecimientos.php';
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
            'direccion' => '',
            'zona' => '',
            'latitud' => '',
            'longitud' => '',
            'telefono' => '',
            'fuente_url' => '',
            'fecha_verificacion' => '',
            'estado' => 'activo',
        ];

        if (($_GET['pagina'] ?? '') === 'editar_establecimiento' && !isset($_GET['id'])) {
            http_response_code(400);
            echo 'Indica el establecimiento que quieres editar.';
            return;
        }

        if (isset($_GET['id'])) {
            $id = $this->idValido($_GET['id']);

            if ($id === null) {
                http_response_code(400);
                echo 'El identificador del establecimiento es inválido.';
                return;
            }

            $modelo = new Establecimiento();
            $establecimiento = $modelo->buscarPorId($id);

            if ($establecimiento === null) {
                http_response_code(404);
                echo 'El establecimiento solicitado no existe.';
                return;
            }

            $datos = $establecimiento;
            $datos['telefono'] = $establecimiento['telefono'] ?? '';
            $datos['fuente_url'] = $establecimiento['fuente_url'] ?? '';
            $datos['fecha_verificacion'] = $establecimiento['fecha_verificacion'] ?? '';
        }

        require __DIR__ . '/../vistas/formulario_establecimiento.php';
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
            echo 'El identificador del establecimiento es inválido.';
            return;
        }

        $datos = [
            'nombre' => trim($this->campo('nombre')),
            'direccion' => trim($this->campo('direccion')),
            'zona' => trim($this->campo('zona')),
            'latitud' => trim($this->campo('latitud')),
            'longitud' => trim($this->campo('longitud')),
            'telefono' => trim($this->campo('telefono')),
            'fuente_url' => trim($this->campo('fuente_url')),
            'fecha_verificacion' => $this->campo('fecha_verificacion'),
            'estado' => $this->campo('estado'),
        ];
        $errores = [];

        if (!$this->csrfValido()) {
            http_response_code(403);
            $errores[] = 'El formulario venció. Revisa los datos y vuelve a guardarlo.';
        } else {
            $errores = $this->validar($datos);

            if ($errores === []) {
                $modelo = new Establecimiento();

                if ($id !== null && $modelo->buscarPorId($id) === null) {
                    http_response_code(404);
                    echo 'El establecimiento solicitado no existe.';
                    return;
                }

                $valores = $datos;
                $valores['telefono'] = $datos['telefono'] === '' ? null : $datos['telefono'];
                $valores['fuente_url'] = $datos['fuente_url'] === '' ? null : $datos['fuente_url'];
                $valores['fecha_verificacion'] = $datos['fecha_verificacion'] === '' ? null : $datos['fecha_verificacion'];

                if ($id === null) {
                    $modelo->crear($valores);
                    $_SESSION['mensaje_establecimientos'] = 'El establecimiento fue creado.';
                } else {
                    $modelo->actualizar($id, $valores);
                    $_SESSION['mensaje_establecimientos'] = 'El establecimiento fue actualizado.';
                }

                header('Location: index.php?pagina=establecimientos', true, 303);
                return;
            }

            http_response_code(422);
        }

        require __DIR__ . '/../vistas/formulario_establecimiento.php';
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
            echo 'El identificador del establecimiento es inválido.';
            return;
        }

        $modelo = new Establecimiento();

        if ($modelo->buscarPorId($id) === null) {
            http_response_code(404);
            echo 'El establecimiento solicitado no existe.';
            return;
        }

        $_SESSION['mensaje_establecimientos'] = $modelo->desactivar($id)
            ? 'El establecimiento fue desactivado y conserva su historial.'
            : 'El establecimiento ya estaba inactivo.';

        header('Location: index.php?pagina=establecimientos', true, 303);
    }

    private function validar(array $datos): array
    {
        $errores = [];

        foreach (array_keys($datos) as $campo) {
            if (array_key_exists($campo, $_POST) && !is_string($_POST[$campo])) {
                return ['Los campos del formulario deben contener texto.'];
            }
        }

        foreach (['nombre' => 200, 'direccion' => 500, 'zona' => 100] as $campo => $limite) {
            if ($datos[$campo] === '' || !mb_check_encoding($datos[$campo], 'UTF-8') || mb_strlen($datos[$campo], 'UTF-8') > $limite) {
                $errores[] = 'Revisa el campo ' . $campo . '. Admite hasta ' . $limite . ' caracteres y es obligatorio.';
            }
        }

        foreach (['latitud' => 90, 'longitud' => 180] as $campo => $limite) {
            $formatoValido = preg_match('/\A-?[0-9]{1,3}(?:\.[0-9]{1,7})?\z/', $datos[$campo]) === 1;

            if (!$formatoValido || (float) $datos[$campo] < -$limite || (float) $datos[$campo] > $limite) {
                $errores[] = 'La ' . $campo . ' debe estar entre -' . $limite . ' y ' . $limite . ', con punto y hasta 7 decimales.';
            }
        }

        if ($datos['telefono'] !== '' && (preg_match('/\A[0-9+(). -]{3,30}\z/', $datos['telefono']) !== 1 || preg_match('/[0-9]/', $datos['telefono']) !== 1)) {
            $errores[] = 'Escribe un teléfono de hasta 30 caracteres con números y signos +, -, punto o paréntesis.';
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

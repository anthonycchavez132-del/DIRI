<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/Recorrido.php';

final class RecorridoController
{
    public function formulario(): void
    {
        $datos = $_SESSION['perfil_recorrido'] ?? $this->datosIniciales();
        $errores = [];
        $contexto = null;
        $identificador = $_GET['tramite_id'] ?? $datos['tramite_id'];

        if ($identificador !== '') {
            $id = $this->entero($identificador);

            if ($id === null) {
                http_response_code(400);
                echo 'Selecciona un trámite válido.';
                return;
            }

            if ((string) $datos['tramite_id'] !== (string) $id) {
                $datos['documentos'] = [];
            }

            $datos['tramite_id'] = (string) $id;

            try {
                $contexto = (new Recorrido())->contexto($id);
            } catch (DomainException $error) {
                http_response_code(422);
                $errores[] = $error->getMessage();
            }
        }

        $this->mostrarFormulario($datos, $errores, $contexto);
    }

    public function calcular(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Utiliza el formulario para calcular el recorrido.';
            return;
        }

        $datos = $this->datosIniciales();
        $errores = [];

        foreach (['tramite_id', 'latitud', 'longitud', 'salida'] as $campo) {
            $valor = $_POST[$campo] ?? '';

            if (!is_string($valor)) {
                $errores[] = in_array($campo, ['latitud', 'longitud'], true)
                    ? 'No se pudo leer tu ubicación. Pulsa «Usar mi ubicación» para obtenerla nuevamente.'
                    : 'Revisa el campo ' . $campo . '.';
                continue;
            }

            $datos[$campo] = $valor;
        }

        $datos['salida_actual'] = ($_POST['salida_actual'] ?? '') === '1' ? '1' : '0';

        if ($datos['salida_actual'] === '1') {
            $datos['salida'] = date('Y-m-d\TH:i');
        }

        $token = $_POST['csrf'] ?? null;

        if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(403);
            $this->mostrarFormulario($datos, ['El formulario venció. Selecciona el trámite y vuelve a intentarlo.'], null);
            return;
        }

        $id = $this->entero($datos['tramite_id']);

        if ($id === null) {
            http_response_code(422);
            $this->mostrarFormulario($datos, ['Selecciona un trámite válido.'], null);
            return;
        }

        $modelo = new Recorrido();

        try {
            $contexto = $modelo->contexto($id);
        } catch (DomainException $error) {
            http_response_code(422);
            $this->mostrarFormulario($datos, [$error->getMessage()], null);
            return;
        }

        $ubicacionValida = true;

        foreach (['latitud' => 90, 'longitud' => 180] as $campo => $limite) {
            if (preg_match('/\A-?[0-9]{1,3}(?:\.[0-9]{1,7})?\z/', $datos[$campo]) !== 1
                || (float) $datos[$campo] < -$limite || (float) $datos[$campo] > $limite) {
                $ubicacionValida = false;
            }
        }

        if (!$ubicacionValida) {
            $errores[] = 'Necesitamos tu punto de partida. Pulsa «Usar mi ubicación» y permite el acceso antes de calcular el recorrido.';
        }

        $salida = $this->fecha($datos['salida'], true);
        $ahora = new DateTimeImmutable();
        $minutoActual = $ahora->setTime((int) $ahora->format('H'), (int) $ahora->format('i'));

        if ($salida === null || $salida < $minutoActual) {
            $errores[] = 'Selecciona una fecha y hora de salida actuales o futuras.';
        }

        $entrada = $_POST['documentos'] ?? [];
        $documentos = [];

        if (!is_array($entrada)) {
            $errores[] = 'Selecciona tus documentos usando las casillas.';
        } else {
            foreach ($entrada as $clave => $fila) {
                if (!is_array($fila)) {
                    $errores[] = 'Hay un documento inválido.';
                    break;
                }

                if (!isset($fila['tengo'])) {
                    continue;
                }

                $documentoId = $this->entero($clave);
                $emision = $fila['emision'] ?? '';
                $vence = $fila['vence'] ?? '';

                if ($fila['tengo'] !== '1' || $documentoId === null || !isset($contexto['documentos'][$documentoId])
                    || !is_string($emision) || !is_string($vence)) {
                    $errores[] = 'Revisa los documentos seleccionados y sus fechas.';
                    break;
                }

                $datos['documentos'][$documentoId] = ['emision' => $emision, 'vence' => $vence];
                $fechaEmision = $emision === '' ? null : $this->fecha($emision);
                $fechaVence = $vence === '' ? null : $this->fecha($vence);

                if (($emision !== '' && $fechaEmision === null) || ($vence !== '' && $fechaVence === null)
                    || ($fechaEmision !== null && $salida !== null && $fechaEmision > $salida)
                    || ($fechaEmision !== null && $fechaVence !== null && $fechaVence < $fechaEmision)) {
                    $errores[] = 'Revisa las fechas de ' . $contexto['documentos'][$documentoId]['nombre'] . '.';
                    continue;
                }

                $documentos[$documentoId] = [
                    'emision' => $fechaEmision,
                    'vence' => $fechaVence === null ? null : $fechaVence->setTime(23, 59, 59),
                ];
            }
        }

        if ($errores === []) {
            $_SESSION['perfil_recorrido'] = $datos;

            try {
                $resultado = $modelo->calcular($contexto, [
                    'latitud' => (float) $datos['latitud'], 'longitud' => (float) $datos['longitud'],
                    'salida' => $salida, 'documentos' => $documentos,
                ]);

                require __DIR__ . '/../vistas/recorrido.php';
                return;
            } catch (DomainException $error) {
                $errores[] = $error->getMessage();
            }
        }

        http_response_code(422);
        $this->mostrarFormulario($datos, $errores, $contexto);
    }

    private function mostrarFormulario(array $datos, array $errores, ?array $contexto): void
    {
        $tramites = (new Tramite())->listar('', false);

        require __DIR__ . '/../vistas/formulario_recorrido.php';
    }

    private function datosIniciales(): array
    {
        return [
            'tramite_id' => '', 'latitud' => '', 'longitud' => '',
            'salida' => date('Y-m-d\TH:i'), 'salida_actual' => '1', 'documentos' => [],
        ];
    }

    private function entero(mixed $valor): ?int
    {
        if (!is_string($valor) && !is_int($valor)) {
            return null;
        }

        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);

        return $id === false ? null : $id;
    }

    private function fecha(string $valor, bool $incluyeHora = false): ?DateTimeImmutable
    {
        $patron = $incluyeHora
            ? '/\A[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}\z/'
            : '/\A[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}\z/';
        $formato = $incluyeHora ? 'Y-m-d\TH:i' : 'Y-m-d';

        if (preg_match($patron, $valor) !== 1) {
            return null;
        }

        $fecha = DateTimeImmutable::createFromFormat('!' . $formato, $valor, new DateTimeZone('America/La_Paz'));

        return $fecha !== false && $fecha->format($formato) === $valor ? $fecha : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Fuente;
use App\Models\Documento;

class ContenidoValidator
{
    public const PRESENTATIONS = ['original' => 'Original', 'copia' => 'Copia', 'original_y_copia' => 'Original y copia', 'digital' => 'Digital'];

    private static function text(array $input, string $key, int $max, array &$errors, bool $multiline = false): string
    {
        $value = $input[$key] ?? '';
        $controls = $multiline ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u';
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $max || preg_match($controls, $value)) {
            $errors[$key] = 'Revisa el campo ' . $key . ': debe contener texto válido de hasta ' . $max . ' caracteres.';
            return '';
        }
        return trim($value);
    }

    public static function validate(string $kind, array $input): array
    {
        $errors = [];
        $data = [];
        if ($kind !== 'asociacion') {
            $data['nombre'] = self::text($input, 'nombre', 200, $errors);
            if ($data['nombre'] === '') {
                $errors['nombre'] = 'Escribe un nombre de hasta 200 caracteres.';
            }
            $data['descripcion'] = self::text($input, 'descripcion', 5000, $errors, true);
            $data['descripcion'] = $data['descripcion'] === '' ? null : $data['descripcion'];
        }
        if ($kind !== 'documento') {
            $flag = self::text($input, 'obligatorio', 1, $errors);
            $data['obligatorio'] = in_array($flag, ['0','1'], true) ? (int) $flag : null;
            if ($data['obligatorio'] === null) {
                $errors['obligatorio'] = 'Indica expresamente si es obligatorio u opcional.';
            }
            $data['observaciones'] = self::text($input, 'observaciones', 5000, $errors, true);
            $data['observaciones'] = $data['observaciones'] === '' ? null : $data['observaciones'];
        }
        if ($kind === 'asociacion') {
            $data['documento_id'] = TramiteValidator::id(self::text($input, 'documento_id', 10, $errors));
            if ($data['documento_id'] === null || (new Documento())->find($data['documento_id']) === null) {
                $errors['documento_id'] = 'Selecciona un documento del catálogo.';
            }
            $quantity = self::text($input, 'cantidad', 5, $errors);
            $data['cantidad'] = $quantity === '' ? null : TramiteValidator::id($quantity);
            if ($quantity !== '' && ($data['cantidad'] === null || $data['cantidad'] > 65535)) {
                $errors['cantidad'] = 'La cantidad debe ser un entero entre 1 y 65535, o quedar sin definir.';
            }
            $presentation = self::text($input, 'presentacion', 30, $errors);
            if ($presentation !== '' && !isset(self::PRESENTATIONS[$presentation])) {
                $errors['presentacion'] = 'Selecciona una presentación válida.';
            }
            $data['presentacion'] = $presentation === '' ? null : $presentation;
        }
        $source = self::text($input, 'fuente_id', 10, $errors);
        $data['fuente_id'] = $source === '' ? null : TramiteValidator::id($source);
        $sources = array_column((new Fuente())->all(), null, 'id');
        if ($source !== '' && ($data['fuente_id'] === null || !isset($sources[$data['fuente_id']]))) {
            $errors['fuente_id'] = 'Selecciona una fuente existente.';
        }
        $date = self::text($input, 'fecha_verificacion', 10, $errors);
        $data['fecha_verificacion'] = $date === '' ? null : $date;
        if ($date !== '') {
            $parsed = preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $date) ? \DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
            if ($parsed === false || $parsed->format('Y-m-d') !== $date || $date > date('Y-m-d')) {
                $errors['fecha_verificacion'] = 'Indica una fecha válida que no sea futura.';
            }
            if ($data['fuente_id'] === null) {
                $errors['fuente_id'] = 'Asocia una fuente antes de registrar su revisión.';
            }
        }
        if ($kind === 'requisito') {
            $state = self::text($input, 'estado_verificacion', 20, $errors);
            $data['estado_verificacion'] = $state;
            if (!isset(TramiteValidator::VERIFICATIONS[$state])) {
                $errors['estado_verificacion'] = 'Selecciona una clasificación válida.';
            }
            if (in_array($state, ['referencial','verificado'], true) && $data['fuente_id'] === null) {
                $errors['fuente_id'] = 'Asocia la fuente del requisito.';
            }
            if ($state === 'verificado') {
                if (($sources[$data['fuente_id']]['tipo'] ?? null) !== 'oficial') {
                    $errors['fuente_id'] = 'Verificado requiere una fuente oficial.';
                }
                if ($data['fecha_verificacion'] === null) {
                    $errors['fecha_verificacion'] = 'Indica cuándo verificaste el requisito.';
                }
            }
        }
        return [$data, $errors];
    }
}

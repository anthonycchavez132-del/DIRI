<?php

declare(strict_types=1);

/** Escapa texto antes de insertarlo en HTML. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Construye enlaces compatibles con una instalación en subcarpetas. */
function url(string $path = ''): string
{
    global $config;
    return $config['base_path'] . '/' . ltrim($path, '/');
}

/** Solo recibe rutas internas definidas por los controladores. */
function redirect(string $path, int $status = 303): void
{
    header('Location: ' . url($path), true, $status);
    exit;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(App\Core\Csrf::token()) . '">';
}

/** Las marcas internas se guardan en UTC y se muestran en hora de Bolivia. */
function local_datetime(string $value): string
{
    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('America/La_Paz'))
        ->format('d/m/Y H:i');
}

/** Una fecha editorial es un día de calendario: no se convierte de zona horaria. */
function verification_date(?string $value): string
{
    return $value === null ? 'Pendiente' : (new DateTimeImmutable($value))->format('d/m/Y');
}

/** Compatibilidad con PHP 8.0, sin requerir array_is_list de PHP 8.1. */
function array_is_list_compat(array $values): bool
{
    return $values===[] || array_keys($values)===range(0,count($values)-1);
}

/** Defensa adicional para enlaces provenientes de contenido importado. */
function external_url(?string $value): ?string
{
    if($value===null||filter_var($value,FILTER_VALIDATE_URL)===false||!in_array(parse_url($value,PHP_URL_SCHEME),['http','https'],true)||parse_url($value,PHP_URL_USER)!==null||parse_url($value,PHP_URL_PASS)!==null){return null;}
    return $value;
}

/** Una exigencia condicional no equivale a un documento opcional. */
function obligation_label(array $row): string
{
    if ((int) ($row['obligatorio'] ?? 0) === 1) {
        return 'Obligatorio';
    }
    return str_starts_with((string) ($row['observaciones'] ?? ''), 'Condicional:')
        ? 'Según tu caso: revisa la condición' : 'Opcional';
}

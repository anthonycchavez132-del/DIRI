<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Categoria;
use App\Models\Fuente;
use App\Core\Database;

class TramiteValidator
{
    public const STATES = ['borrador' => 'Borrador', 'publicado' => 'Publicado', 'inactivo' => 'Inactivo'];
    public const VERIFICATIONS = ['pendiente' => 'Pendiente de verificación', 'referencial' => 'Información referencial', 'verificado' => 'Verificado con fuente oficial'];
    public const DIFFICULTIES = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'];

    public static function id($value): ?int
    {
        if (!is_string($value) || !preg_match('/\A[1-9][0-9]*\z/', $value)) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
        return $id === false ? null : $id;
    }

    public static function validate(array $input, ?int $tramiteId = null): array
    {
        $errors = [];
        $data = [];
        foreach (['nombre','slug','descripcion','categoria_id','estado','dificultad','tiempo_estimado','fuente_id','estado_verificacion','fecha_verificacion'] as $key) {
            $value = $input[$key] ?? '';
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
                $errors[$key] = 'El campo ' . $key . ' debe contener texto válido.';
                $value = '';
            }
            $data[$key] = trim($value);
        }
        if ($data['nombre'] === '' || mb_strlen($data['nombre']) > 200 || preg_match('/[\x00-\x1F\x7F]/u', $data['nombre'])) {
            $errors['nombre'] = 'Escribe un nombre de hasta 200 caracteres, sin saltos de línea.';
        }
        if ($data['slug'] === '') {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $data['nombre']);
            $data['slug'] = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii ?: '')), '-');
        }
        if (strlen($data['slug']) > 220 || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $data['slug'])) {
            $errors['slug'] = 'Usa un identificador de hasta 220 letras minúsculas, números y guiones.';
        }
        if (mb_strlen($data['descripcion']) > 5000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $data['descripcion'])) {
            $errors['descripcion'] = 'La descripción admite hasta 5000 caracteres de texto.';
        }
        if (mb_strlen($data['tiempo_estimado']) > 150 || preg_match('/[\x00-\x1F\x7F]/u', $data['tiempo_estimado'])) {
            $errors['tiempo_estimado'] = 'El tiempo estimado admite hasta 150 caracteres de texto.';
        }
        $categoryId = self::id($data['categoria_id']);
        $categories = array_column((new Categoria())->active(), null, 'id');
        if ($categoryId === null || !isset($categories[$categoryId])) {
            $errors['categoria_id'] = 'Selecciona una categoría activa.';
        }
        $sourceId = $data['fuente_id'] === '' ? null : self::id($data['fuente_id']);
        $sources = array_column((new Fuente())->all(), null, 'id');
        if ($data['fuente_id'] !== '' && ($sourceId === null || !isset($sources[$sourceId]))) {
            $errors['fuente_id'] = 'Selecciona una fuente existente.';
        }
        if (!isset(self::STATES[$data['estado']])) {
            $errors['estado'] = 'Selecciona un estado válido.';
        }
        if (!isset(self::VERIFICATIONS[$data['estado_verificacion']])) {
            $errors['estado_verificacion'] = 'Selecciona un estado de verificación válido.';
        }
        if ($data['dificultad'] !== '' && !isset(self::DIFFICULTIES[$data['dificultad']])) {
            $errors['dificultad'] = 'Selecciona una dificultad válida o déjala sin definir.';
        }
        $date = $data['fecha_verificacion'];
        if ($date !== '') {
            $parsed = preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $date)
                ? \DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
            if ($parsed === false || $parsed->format('Y-m-d') !== $date || $date > date('Y-m-d')) {
                $errors['fecha_verificacion'] = 'Usa una fecha válida que no sea futura.';
            }
            if ($sourceId === null) {
                $errors['fuente_id'] = 'Asocia una fuente antes de indicar una fecha de verificación.';
            }
        }
        if (in_array($data['estado_verificacion'], ['referencial','verificado'], true) && $sourceId === null) {
            $errors['fuente_id'] = 'Asocia la fuente de esta información.';
        }
        if ($data['estado_verificacion'] === 'verificado') {
            if (($sources[$sourceId]['tipo'] ?? null) !== 'oficial') {
                $errors['fuente_id'] = 'Para marcar como verificado debes asociar una fuente oficial.';
            }
            if ($date === '') {
                $errors['fecha_verificacion'] = 'Indica cuándo verificaste la información.';
            }
        }
        if ($data['estado'] === 'publicado' && ($data['estado_verificacion'] === 'pendiente' || $data['descripcion'] === '')) {
            $errors['estado'] = 'Para publicar agrega una descripción y clasifica la información como referencial o verificada con su fuente.';
        }
        if ($data['estado'] === 'publicado' && $tramiteId !== null) {
            $verified = $data['estado_verificacion'] === 'verificado';
            $rule = $verified
                ? "(r.estado_verificacion <> 'verificado' OR f.tipo <> 'oficial' OR r.fecha_verificacion IS NULL)"
                : "(r.estado_verificacion = 'pendiente' OR r.fuente_id IS NULL)";
            $requirements = Database::connection()->prepare('SELECT COUNT(*) FROM requisitos r LEFT JOIN fuentes f ON f.id=r.fuente_id WHERE r.tramite_id=? AND ' . $rule);
            $requirements->execute([$tramiteId]);
            $rule = $verified
                ? "(td.fuente_id IS NULL OR f.tipo <> 'oficial' OR td.fecha_verificacion IS NULL OR d.fuente_id IS NULL OR df.tipo <> 'oficial' OR d.fecha_verificacion IS NULL)"
                : '(td.fuente_id IS NULL OR d.fuente_id IS NULL)';
            $documents = Database::connection()->prepare('SELECT COUNT(*) FROM tramite_documentos td JOIN documentos d ON d.id=td.documento_id
                LEFT JOIN fuentes f ON f.id=td.fuente_id LEFT JOIN fuentes df ON df.id=d.fuente_id WHERE td.tramite_id=? AND (' . $rule . ')');
            $documents->execute([$tramiteId]);
            $invalidChildren=0;
            foreach(['pasos_tramite','costos'] as $table){
                $rule=$verified?"(x.fuente_id IS NULL OR f.tipo <> 'oficial' OR x.fecha_verificacion IS NULL)":'(x.fuente_id IS NULL)';
                if($table==='costos'){$rule.=$verified?" OR x.estado_verificacion<>'verificado' OR x.monto IS NULL":" OR x.estado_verificacion='pendiente'";}
                $query=Database::connection()->prepare("SELECT COUNT(*) FROM $table x LEFT JOIN fuentes f ON f.id=x.fuente_id WHERE x.tramite_id=? AND ($rule)");$query->execute([$tramiteId]);$invalidChildren+=(int)$query->fetchColumn();
            }
            if ((int) $requirements->fetchColumn() > 0 || (int) $documents->fetchColumn() > 0 || $invalidChildren>0) {
                $errors['contenido'] = 'Revisa las fuentes y la clasificación de requisitos, documentos, pasos y costos antes de publicar.';
            }
        }
        $data['categoria_id'] = $categoryId;
        $data['fuente_id'] = $sourceId;
        foreach (['descripcion','dificultad','tiempo_estimado','fecha_verificacion'] as $key) {
            $data[$key] = $data[$key] === '' ? null : $data[$key];
        }
        return [$data, $errors];
    }
}

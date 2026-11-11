<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class TramiteDocumento
{
    public function forTramite(int $tramiteId): array
    {
        // Incluye borradores para la administración; el catálogo valida antes la publicación.
        $query = Database::connection()->prepare('SELECT tramite_id, documento_id, obligatorio, cantidad,
            presentacion, observaciones, fuente_id, fecha_verificacion, nombre, descripcion,
            documento_fuente_id, documento_fecha_verificacion
            FROM vista_documentos_tramite WHERE tramite_id=? ORDER BY nombre');
        $query->execute([$tramiteId]);
        return $query->fetchAll();
    }

    public function find(int $tramiteId, int $documentId, bool $lock = false): ?array
    {
        $query = Database::connection()->prepare('SELECT * FROM tramite_documentos WHERE tramite_id=? AND documento_id=?' . ($lock ? ' FOR UPDATE' : ''));
        $query->execute([$tramiteId,$documentId]);
        return $query->fetch() ?: null;
    }

    public function parents(int $documentId): array
    {
        $query = Database::connection()->prepare('SELECT tramite_id FROM tramite_documentos WHERE documento_id=? ORDER BY tramite_id');
        $query->execute([$documentId]);
        return array_map('intval', $query->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function save(array $data, int $tramiteId, int $documentId, bool $editing): void
    {
        $values = array_map(fn ($key) => $data[$key], ['obligatorio','cantidad','presentacion','observaciones','fuente_id','fecha_verificacion']);
        if ($editing) {
            $sql = 'UPDATE tramite_documentos SET obligatorio=?,cantidad=?,presentacion=?,observaciones=?,fuente_id=?,fecha_verificacion=? WHERE tramite_id=? AND documento_id=?';
        } else {
            $sql = 'INSERT INTO tramite_documentos (obligatorio,cantidad,presentacion,observaciones,fuente_id,fecha_verificacion,tramite_id,documento_id) VALUES (?,?,?,?,?,?,?,?)';
        }
        Database::connection()->prepare($sql)->execute(array_merge($values, [$tramiteId,$documentId]));
    }
}

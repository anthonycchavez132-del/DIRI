<?php

declare(strict_types=1);

require_once __DIR__ . '/Conexion.php';

final class Tramite
{
    public function listar(string $busqueda, bool $incluirInactivos): array
    {
        $condiciones = [];
        $parametros = [];

        if (!$incluirInactivos) {
            $condiciones[] = "estado = 'activo'";
        }

        if ($busqueda !== '') {

            $texto = strtr($busqueda, ['!' => '!!', '%' => '!%', '_' => '!_']);
            $condiciones[] = "(nombre LIKE :nombre ESCAPE '!' OR descripcion LIKE :descripcion ESCAPE '!')";
            $parametros['nombre'] = '%' . $texto . '%';
            $parametros['descripcion'] = '%' . $texto . '%';
        }

        $sql = 'SELECT id, nombre, descripcion, fuente_url, fecha_verificacion, estado
                FROM tramites';

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY nombre, id';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, descripcion, fuente_url, fecha_verificacion, estado
             FROM tramites WHERE id = :id LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $tramite = $consulta->fetch();

        return $tramite === false ? null : $tramite;
    }

    public function requisitos(int $id): array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT td.*, d.nombre, d.estado AS estado_documento
             FROM tramite_documentos td JOIN documentos d ON d.id = td.documento_id
             WHERE td.tramite_id = ? ORDER BY d.nombre, d.id'
        );
        $consulta->execute([$id]);

        return $consulta->fetchAll();
    }

    public function crear(array $datos, array $requisitos = []): int
    {
        $conexion = Conexion::obtener();
        $conexion->beginTransaction();

        try {
            $consulta = $conexion->prepare(
                'INSERT INTO tramites (nombre, descripcion, fuente_url, fecha_verificacion, estado)
                 VALUES (:nombre, :descripcion, :fuente_url, :fecha_verificacion, :estado)'
            );
            $consulta->execute($datos);
            $id = (int) $conexion->lastInsertId();
            $this->guardarRequisitos($conexion, $id, $requisitos);
            $conexion->commit();

            return $id;
        } catch (Throwable $error) {
            $conexion->rollBack();
            throw $error;
        }
    }

    public function actualizar(int $id, array $datos, ?array $requisitos = null): void
    {
        $conexion = Conexion::obtener();
        $conexion->beginTransaction();

        try {
            $datos['id'] = $id;
            $consulta = $conexion->prepare(
                'UPDATE tramites
                 SET nombre = :nombre, descripcion = :descripcion, fuente_url = :fuente_url,
                     fecha_verificacion = :fecha_verificacion, estado = :estado
                 WHERE id = :id'
            );
            $consulta->execute($datos);

            if ($requisitos !== null) {
                $this->guardarRequisitos($conexion, $id, $requisitos);
            }

            $conexion->commit();
        } catch (Throwable $error) {
            $conexion->rollBack();
            throw $error;
        }
    }

    public function desactivar(int $id): bool
    {
        $consulta = Conexion::obtener()->prepare(
            "UPDATE tramites SET estado = 'inactivo' WHERE id = :id AND estado = 'activo'"
        );
        $consulta->execute(['id' => $id]);

        return $consulta->rowCount() > 0;
    }

    private function guardarRequisitos(PDO $conexion, int $id, array $requisitos): void
    {
        $consulta = $conexion->prepare('SELECT documento_id FROM tramite_documentos WHERE tramite_id = ?');
        $consulta->execute([$id]);
        $anteriores = array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
        $eliminar = $conexion->prepare('DELETE FROM tramite_documentos WHERE tramite_id = ? AND documento_id = ?');

        foreach (array_diff($anteriores, array_keys($requisitos)) as $documentoId) {
            $eliminar->execute([$id, $documentoId]);
        }

        $existe = $conexion->prepare('SELECT id FROM documentos WHERE id = ?');
        $guardar = $conexion->prepare(
            'INSERT INTO tramite_documentos (tramite_id, documento_id, obligatorio, vigencia_dias, observaciones)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE obligatorio = VALUES(obligatorio),
                 vigencia_dias = VALUES(vigencia_dias), observaciones = VALUES(observaciones)'
        );

        foreach ($requisitos as $documentoId => $requisito) {
            $existe->execute([$documentoId]);

            if ($existe->fetch() === false) {
                throw new DomainException('Uno de los documentos seleccionados ya no existe. Revisa los requisitos.');
            }

            $guardar->execute([$id, $documentoId, $requisito['obligatorio'], $requisito['vigencia_dias'], $requisito['observaciones']]);
        }
    }
}

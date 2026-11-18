<?php

declare(strict_types=1);

require_once __DIR__ . '/Conexion.php';

final class Documento
{
    public function listar(string $busqueda): array
    {
        $sql = 'SELECT id, nombre, descripcion, estado FROM documentos';
        $parametros = [];

        if ($busqueda !== '') {
            $texto = strtr($busqueda, ['!' => '!!', '%' => '!%', '_' => '!_']);
            $sql .= " WHERE nombre LIKE :nombre ESCAPE '!' OR descripcion LIKE :descripcion ESCAPE '!'";
            $parametros['nombre'] = '%' . $texto . '%';
            $parametros['descripcion'] = '%' . $texto . '%';
        }

        $sql .= ' ORDER BY nombre, id';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, descripcion, estado FROM documentos WHERE id = :id LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $documento = $consulta->fetch();

        return $documento === false ? null : $documento;
    }

    public function crear(array $datos): int
    {
        $conexion = Conexion::obtener();
        $consulta = $conexion->prepare(
            'INSERT INTO documentos (nombre, descripcion, estado)
             VALUES (:nombre, :descripcion, :estado)'
        );
        $consulta->execute($datos);

        return (int) $conexion->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void
    {
        $datos['id'] = $id;
        $consulta = Conexion::obtener()->prepare(
            'UPDATE documentos SET nombre = :nombre, descripcion = :descripcion, estado = :estado
             WHERE id = :id'
        );
        $consulta->execute($datos);
    }

    public function desactivar(int $id): bool
    {
        $consulta = Conexion::obtener()->prepare(
            "UPDATE documentos SET estado = 'inactivo' WHERE id = :id AND estado = 'activo'"
        );
        $consulta->execute(['id' => $id]);

        return $consulta->rowCount() > 0;
    }
}

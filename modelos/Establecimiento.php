<?php

declare(strict_types=1);

require_once __DIR__ . '/Conexion.php';

final class Establecimiento
{
    public function listar(string $busqueda): array
    {
        $sql = 'SELECT id, nombre, direccion, zona, latitud, longitud, telefono,
                       fuente_url, fecha_verificacion, estado FROM establecimientos';
        $parametros = [];

        if ($busqueda !== '') {
            $texto = strtr($busqueda, ['!' => '!!', '%' => '!%', '_' => '!_']);
            $sql .= " WHERE nombre LIKE :nombre ESCAPE '!'
                      OR direccion LIKE :direccion ESCAPE '!' OR zona LIKE :zona ESCAPE '!'";
            $parametros['nombre'] = '%' . $texto . '%';
            $parametros['direccion'] = '%' . $texto . '%';
            $parametros['zona'] = '%' . $texto . '%';
        }

        $sql .= ' ORDER BY nombre, id';
        $consulta = Conexion::obtener()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, direccion, zona, latitud, longitud, telefono,
                    fuente_url, fecha_verificacion, estado
             FROM establecimientos WHERE id = :id LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $establecimiento = $consulta->fetch();

        return $establecimiento === false ? null : $establecimiento;
    }

    public function crear(array $datos): int
    {
        $conexion = Conexion::obtener();
        $consulta = $conexion->prepare(
            'INSERT INTO establecimientos (nombre, direccion, zona, latitud, longitud,
                    telefono, fuente_url, fecha_verificacion, estado)
             VALUES (:nombre, :direccion, :zona, :latitud, :longitud,
                     :telefono, :fuente_url, :fecha_verificacion, :estado)'
        );
        $consulta->execute($datos);

        return (int) $conexion->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void
    {
        $datos['id'] = $id;
        $consulta = Conexion::obtener()->prepare(
            'UPDATE establecimientos
             SET nombre = :nombre, direccion = :direccion, zona = :zona,
                 latitud = :latitud, longitud = :longitud, telefono = :telefono,
                 fuente_url = :fuente_url, fecha_verificacion = :fecha_verificacion, estado = :estado
             WHERE id = :id'
        );
        $consulta->execute($datos);
    }

    public function desactivar(int $id): bool
    {
        $consulta = Conexion::obtener()->prepare(
            "UPDATE establecimientos SET estado = 'inactivo' WHERE id = :id AND estado = 'activo'"
        );
        $consulta->execute(['id' => $id]);

        return $consulta->rowCount() > 0;
    }
}

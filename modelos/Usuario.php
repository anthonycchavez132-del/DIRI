<?php

declare(strict_types=1);

require_once __DIR__ . '/Conexion.php';

final class Usuario
{
    public function crear(
        string $nombre,
        string $apellidoPaterno,
        ?string $apellidoMaterno,
        string $correo,
        string $contrasena
    ): int {
        $conexion = Conexion::obtener();
        $consulta = $conexion->prepare(
            'INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, correo, contrasena)
             VALUES (:nombre, :apellido_paterno, :apellido_materno, :correo, :contrasena)'
        );
        $consulta->execute([
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'correo' => $correo,
            'contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
        ]);

        return (int) $conexion->lastInsertId();
    }

    public function buscarPorCorreo(string $correo): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, apellido_paterno, apellido_materno, correo, contrasena, rol, estado
             FROM usuarios WHERE correo = :correo LIMIT 1'
        );
        $consulta->execute(['correo' => $correo]);
        $usuario = $consulta->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare(
            'SELECT id, nombre, apellido_paterno, apellido_materno, correo, rol, estado
             FROM usuarios WHERE id = :id LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $usuario = $consulta->fetch();

        return $usuario === false ? null : $usuario;
    }
}

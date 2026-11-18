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

    public function obtenerHashContrasena(int $id): ?string
    {
        $consulta = Conexion::obtener()->prepare('SELECT contrasena FROM usuarios WHERE id = :id LIMIT 1');
        $consulta->execute(['id' => $id]);
        $hash = $consulta->fetchColumn();

        return is_string($hash) ? $hash : null;
    }

    public function cambiarContrasena(int $id, string $contrasenaNueva): void
    {
        $consulta = Conexion::obtener()->prepare('UPDATE usuarios SET contrasena = :contrasena WHERE id = :id');
        $consulta->execute([
            'contrasena' => password_hash($contrasenaNueva, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
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

    public function listar(string $busqueda): array
    {
        $sql = 'SELECT id, nombre, apellido_paterno, apellido_materno, correo, rol, estado, fecha_registro FROM usuarios';
        $parametros = [];

        if ($busqueda !== '') {
            $texto = '%' . strtr($busqueda, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $sql .= " WHERE nombre LIKE ? ESCAPE '!' OR apellido_paterno LIKE ? ESCAPE '!' OR apellido_materno LIKE ? ESCAPE '!' OR correo LIKE ? ESCAPE '!'";
            $parametros = [$texto, $texto, $texto, $texto];
        }

        $consulta = Conexion::obtener()->prepare($sql . ' ORDER BY nombre, apellido_paterno, id');
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    public function administradoresActivos(): int
    {
        return (int) Conexion::obtener()->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador' AND estado = 'activo'")->fetchColumn();
    }

    public function desactivar(int $id, int $responsableId): bool
    {
        $conexion = Conexion::obtener();
        $conexion->beginTransaction();

        try {
            $administradores = $conexion->query("SELECT id, estado FROM usuarios WHERE rol = 'administrador' ORDER BY id FOR UPDATE")->fetchAll();
            $consulta = $conexion->prepare('SELECT id, rol, estado FROM usuarios WHERE id = ? FOR UPDATE');
            $consulta->execute([$responsableId]);
            $responsable = $consulta->fetch();

            if ($responsable === false || $responsable['rol'] !== 'administrador' || $responsable['estado'] !== 'activo') {
                throw new DomainException('La operación requiere un administrador activo.');
            }

            if ($id === $responsableId) {
                throw new DomainException('No puedes eliminar tu propia cuenta.');
            }

            $consulta->execute([$id]);
            $usuario = $consulta->fetch();

            if ($usuario === false) {
                throw new DomainException('El usuario seleccionado ya no existe.');
            }

            if ($usuario['estado'] !== 'activo') {
                $conexion->commit();
                return false;
            }

            $activos = count(array_filter($administradores, static fn(array $administrador): bool => $administrador['estado'] === 'activo'));

            if ($usuario['rol'] === 'administrador' && $activos <= 1) {
                throw new DomainException('Debe quedar al menos un administrador activo.');
            }

            $consulta = $conexion->prepare("UPDATE usuarios SET estado = 'inactivo' WHERE id = ? AND estado = 'activo'");
            $consulta->execute([$id]);
            $cambio = $consulta->rowCount() === 1;
            $conexion->commit();

            return $cambio;
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            throw $error;
        }
    }
}

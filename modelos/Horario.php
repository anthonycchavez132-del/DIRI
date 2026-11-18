<?php

declare(strict_types=1);

require_once __DIR__ . '/Conexion.php';

final class Horario
{
    public function listar(?int $establecimientoId = null): array
    {
        $sql = 'SELECT h.*, e.nombre AS establecimiento
                FROM horarios h JOIN establecimientos e ON e.id = h.establecimiento_id';
        $parametros = [];

        if ($establecimientoId !== null) {
            $sql .= ' WHERE h.establecimiento_id = ?';
            $parametros[] = $establecimientoId;
        }

        $consulta = Conexion::obtener()->prepare($sql . ' ORDER BY e.nombre, h.dia_semana, h.hora_apertura, h.id');
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = Conexion::obtener()->prepare('SELECT * FROM horarios WHERE id = ?');
        $consulta->execute([$id]);
        $horario = $consulta->fetch();

        return $horario === false ? null : $horario;
    }

    public function guardar(?int $id, array $datos): int
    {
        $conexion = Conexion::obtener();
        $conexion->beginTransaction();

        try {
            // Este bloqueo evita registrar dos intervalos superpuestos al mismo tiempo.
            $consulta = $conexion->prepare('SELECT id FROM establecimientos WHERE id = ? FOR UPDATE');
            $consulta->execute([$datos['establecimiento_id']]);

            if ($consulta->fetch() === false) {
                throw new DomainException('Selecciona un establecimiento existente.');
            }

            if ($id !== null) {
                $consulta = $conexion->prepare('SELECT establecimiento_id FROM horarios WHERE id = ? FOR UPDATE');
                $consulta->execute([$id]);
                $existente = $consulta->fetch();

                if ($existente === false || (int) $existente['establecimiento_id'] !== $datos['establecimiento_id']) {
                    throw new DomainException('El horario debe conservar el establecimiento al que pertenece.');
                }
            }

            if ($datos['estado'] === 'activo') {
                $consulta = $conexion->prepare(
                    "SELECT id FROM horarios WHERE establecimiento_id = :establecimiento_id
                     AND dia_semana = :dia_semana AND estado = 'activo' AND id <> :id
                     AND hora_apertura < :cierre AND hora_cierre > :apertura LIMIT 1"
                );
                $consulta->execute([
                    'establecimiento_id' => $datos['establecimiento_id'],
                    'dia_semana' => $datos['dia_semana'],
                    'id' => $id ?? 0,
                    'cierre' => $datos['hora_cierre'],
                    'apertura' => $datos['hora_apertura'],
                ]);

                if ($consulta->fetch() !== false) {
                    throw new DomainException('El intervalo se superpone con otro horario activo del mismo día.');
                }
            }

            if ($id === null) {
                $consulta = $conexion->prepare(
                    'INSERT INTO horarios (establecimiento_id, dia_semana, hora_apertura, hora_cierre, estado)
                     VALUES (:establecimiento_id, :dia_semana, :hora_apertura, :hora_cierre, :estado)'
                );
                $consulta->execute($datos);
                $id = (int) $conexion->lastInsertId();
            } else {
                $datos['id'] = $id;
                $consulta = $conexion->prepare(
                    'UPDATE horarios SET dia_semana = :dia_semana, hora_apertura = :hora_apertura,
                     hora_cierre = :hora_cierre, estado = :estado
                     WHERE id = :id AND establecimiento_id = :establecimiento_id'
                );
                $consulta->execute($datos);
            }

            $conexion->commit();
            return $id;
        } catch (Throwable $error) {
            $conexion->rollBack();
            throw $error;
        }
    }

    public function desactivar(int $id): bool
    {
        $consulta = Conexion::obtener()->prepare(
            "UPDATE horarios SET estado = 'inactivo' WHERE id = ? AND estado = 'activo'"
        );
        $consulta->execute([$id]);

        return $consulta->rowCount() > 0;
    }
}

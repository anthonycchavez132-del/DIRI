<?php

declare(strict_types=1);

require_once __DIR__ . '/Conexion.php';

final class Gestion
{
    public function listar(?int $tramiteId = null): array
    {
        $sql = 'SELECT g.id, g.tramite_id, g.nombre, g.descripcion, g.orden, g.estado,
                       t.nombre AS tramite, d.nombre AS documento_resultado
                FROM gestiones g
                JOIN tramites t ON t.id = g.tramite_id
                LEFT JOIN documentos d ON d.id = g.documento_resultado_id';
        $parametros = [];

        if ($tramiteId !== null) {
            $sql .= ' WHERE g.tramite_id = :tramite_id';
            $parametros['tramite_id'] = $tramiteId;
        }

        $consulta = Conexion::obtener()->prepare($sql . ' ORDER BY t.nombre, g.orden, g.id');
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $conexion = Conexion::obtener();
        $consulta = $conexion->prepare('SELECT * FROM gestiones WHERE id = ?');
        $consulta->execute([$id]);
        $gestion = $consulta->fetch();

        if ($gestion === false) {
            return null;
        }

        $consulta = $conexion->prepare(
            'SELECT gestion_previa_id, dias_espera FROM gestion_dependencias WHERE gestion_id = ?'
        );
        $consulta->execute([$id]);
        $gestion['esperas'] = $consulta->fetchAll(PDO::FETCH_KEY_PAIR);
        $gestion['dependencias'] = array_map('intval', array_keys($gestion['esperas']));

        $consulta = $conexion->prepare(
            "SELECT establecimiento_id, duracion_atencion_minutos
             FROM gestion_establecimientos WHERE gestion_id = ? AND estado = 'activo'"
        );
        $consulta->execute([$id]);
        $gestion['establecimientos'] = [];

        foreach ($consulta->fetchAll() as $establecimiento) {
            $gestion['establecimientos'][(int) $establecimiento['establecimiento_id']] =
                $establecimiento['duracion_atencion_minutos'];
        }

        return $gestion;
    }

    public function guardar(?int $id, array $datos, array $dependencias, array $establecimientos, ?array $esperas = null): int
    {
        $conexion = Conexion::obtener();
        $conexion->beginTransaction();

        try {
            // Todas las ediciones de un mismo trámite se ordenan mediante este bloqueo.
            $consulta = $conexion->prepare('SELECT id FROM tramites WHERE id = ? FOR UPDATE');
            $consulta->execute([$datos['tramite_id']]);

            if ($consulta->fetch() === false) {
                throw new DomainException('Selecciona un trámite existente.');
            }

            if ($id !== null) {
                $consulta = $conexion->prepare('SELECT tramite_id FROM gestiones WHERE id = ? FOR UPDATE');
                $consulta->execute([$id]);
                $existente = $consulta->fetch();

                if ($existente === false || (int) $existente['tramite_id'] !== $datos['tramite_id']) {
                    throw new DomainException('La gestión debe conservar el trámite al que pertenece.');
                }
            }

            if ($datos['documento_resultado_id'] !== null) {
                $consulta = $conexion->prepare('SELECT estado FROM documentos WHERE id = ? FOR UPDATE');
                $consulta->execute([$datos['documento_resultado_id']]);
                $documento = $consulta->fetch();

                if ($documento === false || ($datos['estado'] === 'activo' && $documento['estado'] !== 'activo')) {
                    throw new DomainException('Selecciona un documento disponible para esta gestión.');
                }
            }

            $this->validarDependencias($conexion, $id, $datos['tramite_id'], $dependencias, $datos['estado']);

            if ($datos['estado'] === 'inactivo' && $id !== null) {
                $this->comprobarDependientes($conexion, $id);
            }

            ksort($establecimientos);

            foreach ($establecimientos as $establecimientoId => $duracion) {
                $consulta = $conexion->prepare('SELECT estado FROM establecimientos WHERE id = ? FOR UPDATE');
                $consulta->execute([$establecimientoId]);
                $establecimiento = $consulta->fetch();

                if ($establecimiento === false || ($datos['estado'] === 'activo' && $establecimiento['estado'] !== 'activo')) {
                    throw new DomainException('Selecciona establecimientos activos para una gestión activa.');
                }
            }

            if ($id === null) {
                $consulta = $conexion->prepare(
                    'INSERT INTO gestiones (tramite_id, documento_resultado_id, nombre, descripcion, orden, estado)
                     VALUES (:tramite_id, :documento_resultado_id, :nombre, :descripcion, :orden, :estado)'
                );
                $consulta->execute($datos);
                $id = (int) $conexion->lastInsertId();
            } else {
                $datos['id'] = $id;
                $consulta = $conexion->prepare(
                    'UPDATE gestiones SET documento_resultado_id = :documento_resultado_id,
                     nombre = :nombre, descripcion = :descripcion, orden = :orden, estado = :estado
                     WHERE id = :id AND tramite_id = :tramite_id'
                );
                $consulta->execute($datos);
            }

            $consulta = $conexion->prepare('SELECT gestion_previa_id, dias_espera FROM gestion_dependencias WHERE gestion_id = ?');
            $consulta->execute([$id]);
            $esperasAnteriores = $consulta->fetchAll(PDO::FETCH_KEY_PAIR);
            $anteriores = array_map('intval', array_keys($esperasAnteriores));
            $eliminar = $conexion->prepare('DELETE FROM gestion_dependencias WHERE gestion_id = ? AND gestion_previa_id = ?');
            $agregar = $conexion->prepare(
                'INSERT INTO gestion_dependencias (gestion_id, gestion_previa_id, dias_espera)
                 VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE dias_espera = VALUES(dias_espera)'
            );

            foreach (array_diff($anteriores, $dependencias) as $previaId) {
                $eliminar->execute([$id, $previaId]);
            }

            foreach ($dependencias as $previaId) {
                $dias = $esperas === null ? ($esperasAnteriores[$previaId] ?? 0) : ($esperas[$previaId] ?? 0);
                $agregar->execute([$id, $previaId, $dias]);
            }

            $consulta = $conexion->prepare(
                "SELECT establecimiento_id FROM gestion_establecimientos
                 WHERE gestion_id = ? AND estado = 'activo'"
            );
            $consulta->execute([$id]);
            $anteriores = array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
            $desactivar = $conexion->prepare(
                "UPDATE gestion_establecimientos SET estado = 'inactivo'
                 WHERE gestion_id = ? AND establecimiento_id = ?"
            );

            foreach (array_diff($anteriores, array_keys($establecimientos)) as $establecimientoId) {
                $desactivar->execute([$id, $establecimientoId]);
            }

            $consulta = $conexion->prepare(
                "INSERT INTO gestion_establecimientos
                    (gestion_id, establecimiento_id, duracion_atencion_minutos, estado)
                 VALUES (?, ?, ?, 'activo')
                 ON DUPLICATE KEY UPDATE duracion_atencion_minutos = VALUES(duracion_atencion_minutos),
                                         estado = VALUES(estado)"
            );

            foreach ($establecimientos as $establecimientoId => $duracion) {
                $consulta->execute([$id, $establecimientoId, $duracion]);
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
        $conexion = Conexion::obtener();
        $consulta = $conexion->prepare('SELECT tramite_id FROM gestiones WHERE id = ?');
        $consulta->execute([$id]);
        $tramiteId = $consulta->fetchColumn();

        if ($tramiteId === false) {
            throw new DomainException('La gestión solicitada no existe.');
        }

        $conexion->beginTransaction();

        try {
            $consulta = $conexion->prepare('SELECT id FROM tramites WHERE id = ? FOR UPDATE');
            $consulta->execute([(int) $tramiteId]);
            $this->comprobarDependientes($conexion, $id);
            $consulta = $conexion->prepare("UPDATE gestiones SET estado = 'inactivo' WHERE id = ? AND estado = 'activo'");
            $consulta->execute([$id]);
            $cambio = $consulta->rowCount() > 0;
            $conexion->commit();

            return $cambio;
        } catch (Throwable $error) {
            $conexion->rollBack();
            throw $error;
        }
    }

    private function comprobarDependientes(PDO $conexion, int $id): void
    {
        $consulta = $conexion->prepare(
            "SELECT g.id FROM gestion_dependencias d JOIN gestiones g ON g.id = d.gestion_id
             WHERE d.gestion_previa_id = ? AND g.estado = 'activo' LIMIT 1"
        );
        $consulta->execute([$id]);

        if ($consulta->fetch() !== false) {
            throw new DomainException('Primero revisa o desactiva las gestiones activas que dependen de esta.');
        }
    }

    private function validarDependencias(PDO $conexion, ?int $id, int $tramiteId, array $dependencias, string $estado): void
    {
        $consulta = $conexion->prepare('SELECT id, estado FROM gestiones WHERE tramite_id = ?');
        $consulta->execute([$tramiteId]);
        $gestiones = $consulta->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($dependencias as $previaId) {
            if ($previaId === $id || !isset($gestiones[$previaId])) {
                throw new DomainException('Las gestiones previas deben ser diferentes y pertenecer al mismo trámite.');
            }

            if ($estado === 'activo' && $gestiones[$previaId] !== 'activo') {
                throw new DomainException('Una gestión activa requiere que sus gestiones previas estén activas.');
            }
        }

        $consulta = $conexion->prepare(
            'SELECT d.gestion_id, d.gestion_previa_id FROM gestion_dependencias d
             JOIN gestiones g ON g.id = d.gestion_id WHERE g.tramite_id = ?'
        );
        $consulta->execute([$tramiteId]);
        $anteriores = [];

        foreach ($consulta->fetchAll() as $dependencia) {
            $anteriores[(int) $dependencia['gestion_id']][] = (int) $dependencia['gestion_previa_id'];
        }

        // Un camino que regresa a la gestión editada formaría un ciclo.
        $porVisitar = $dependencias;
        $visitadas = [];

        while ($porVisitar !== []) {
            $actual = array_pop($porVisitar);

            if ($actual === $id) {
                throw new DomainException('Las dependencias forman un ciclo. Revisa el orden de las gestiones.');
            }

            if (isset($visitadas[$actual])) {
                continue;
            }

            $visitadas[$actual] = true;

            foreach ($anteriores[$actual] ?? [] as $previaId) {
                $porVisitar[] = $previaId;
            }
        }
    }
}

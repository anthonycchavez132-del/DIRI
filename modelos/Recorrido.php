<?php

declare(strict_types=1);

require_once __DIR__ . '/Tramite.php';

final class Recorrido
{
    private array $distancias = [];
    private array $duraciones = [];
    private array $requisitos = [];
    private array $disponibles = [];
    private array $productores = [];
    private array $caducanDurante = [];
    private string $claveMatriz = '';
    private ?array $mejor = null;
    private float $limite = 0;
    private bool $comparacionCompleta = true;
    private int $alternativas = 0;

    public function contexto(int $tramiteId): array
    {
        $conexion = Conexion::obtener();
        $conexion->beginTransaction();

        try {
            $modelo = new Tramite();
            $tramite = $modelo->buscarPorId($tramiteId);

            if ($tramite === null || $tramite['estado'] !== 'activo') {
                throw new DomainException('Selecciona un trámite activo.');
            }

            $documentos = [];

            foreach ($modelo->requisitos($tramiteId) as $documento) {
                $documentos[(int) $documento['documento_id']] = $documento;
            }

            $consulta = $conexion->prepare(
                "SELECT g.*, d.nombre AS documento, d.estado AS estado_documento
                 FROM gestiones g LEFT JOIN documentos d ON d.id = g.documento_resultado_id
                 WHERE g.tramite_id = ? AND g.estado = 'activo' ORDER BY g.orden, g.id"
            );
            $consulta->execute([$tramiteId]);
            $gestiones = [];

            foreach ($consulta->fetchAll() as $gestion) {
                $gestion['previas'] = [];
                $gestion['opciones'] = [];
                $gestiones[(int) $gestion['id']] = $gestion;

                if ($gestion['documento_resultado_id'] !== null && !isset($documentos[(int) $gestion['documento_resultado_id']])) {
                    $documentos[(int) $gestion['documento_resultado_id']] = [
                        'documento_id' => (int) $gestion['documento_resultado_id'],
                        'nombre' => $gestion['documento'], 'estado_documento' => $gestion['estado_documento'],
                        'obligatorio' => 0, 'vigencia_dias' => null, 'observaciones' => null,
                    ];
                }
            }

            $consulta = $conexion->prepare(
                "SELECT d.* FROM gestion_dependencias d JOIN gestiones g ON g.id = d.gestion_id
                 WHERE g.tramite_id = ? AND g.estado = 'activo'"
            );
            $consulta->execute([$tramiteId]);

            foreach ($consulta->fetchAll() as $dependencia) {
                $gestiones[(int) $dependencia['gestion_id']]['previas'][(int) $dependencia['gestion_previa_id']] =
                    (int) $dependencia['dias_espera'];
            }

            $consulta = $conexion->prepare(
                "SELECT ge.gestion_id, ge.duracion_atencion_minutos, e.id, e.nombre,
                        e.direccion, e.zona, e.latitud, e.longitud, e.fuente_url
                 FROM gestion_establecimientos ge JOIN establecimientos e ON e.id = ge.establecimiento_id
                 JOIN gestiones g ON g.id = ge.gestion_id
                 WHERE g.tramite_id = ? AND g.estado = 'activo' AND ge.estado = 'activo' AND e.estado = 'activo'
                 ORDER BY e.id"
            );
            $consulta->execute([$tramiteId]);

            foreach ($consulta->fetchAll() as $establecimiento) {
                $establecimiento['horarios'] = [];
                $gestiones[(int) $establecimiento['gestion_id']]['opciones'][(int) $establecimiento['id']] = $establecimiento;
            }

            $consulta = $conexion->prepare(
                "SELECT DISTINCT h.* FROM horarios h
                 JOIN gestion_establecimientos ge ON ge.establecimiento_id = h.establecimiento_id
                 JOIN gestiones g ON g.id = ge.gestion_id
                 WHERE g.tramite_id = ? AND g.estado = 'activo' AND ge.estado = 'activo'"
            );
            $consulta->execute([$tramiteId]);
            $horarios = [];
            $horariosRegistrados = [];

            foreach ($consulta->fetchAll() as $horario) {
                $horariosRegistrados[(int) $horario['establecimiento_id']] = true;

                if ($horario['estado'] === 'activo') {
                    $horarios[(int) $horario['establecimiento_id']][] = $horario;
                }
            }

            foreach ($gestiones as &$gestion) {
                foreach ($gestion['opciones'] as &$opcion) {
                    $opcion['horarios'] = $horarios[(int) $opcion['id']] ?? [];
                    $opcion['horario_pendiente'] = !isset($horariosRegistrados[(int) $opcion['id']]);
                }
                unset($opcion);
            }
            unset($gestion);

            $documentosObtenibles = array_column($gestiones, 'documento_resultado_id');

            foreach ($documentos as $documentoId => &$documento) {
                $documento['se_obtiene_en_recorrido'] = in_array($documentoId, array_map('intval', $documentosObtenibles), true);
            }
            unset($documento);

            $conexion->commit();

            return ['tramite' => $tramite, 'documentos' => $documentos, 'gestiones' => $gestiones];
        } catch (Throwable $error) {
            $conexion->rollBack();
            throw $error;
        }
    }

    public function calcular(array $contexto, array $perfil): array
    {
        $salida = $perfil['salida'];
        $gestiones = $contexto['gestiones'];

        if ($gestiones === []) {
            throw new DomainException('Este trámite todavía necesita sus gestiones y establecimientos. El administrador debe completar su configuración.');
        }

        $this->requisitos = $contexto['documentos'];
        $this->disponibles = [];
        $this->mejor = null;
        $this->alternativas = 0;
        $this->comparacionCompleta = true;
        $this->caducanDurante = [];
        $avisos = [];
        $productores = [];

        foreach ($gestiones as $id => $gestion) {
            foreach ($gestion['previas'] as $previaId => $dias) {
                if (!isset($gestiones[$previaId])) {
                    throw new DomainException('El trámite tiene una dependencia inactiva o ajena. El administrador debe revisarla.');
                }
            }

            if ($gestion['documento_resultado_id'] !== null) {
                $documentoId = (int) $gestion['documento_resultado_id'];

                if (isset($productores[$documentoId])) {
                    throw new DomainException('Hay varias gestiones activas que producen el mismo documento. Agrupa sus establecimientos en una sola gestión.');
                }

                $productores[$documentoId] = $id;
            }
        }

        foreach ($perfil['documentos'] as $id => $declaracion) {
            $documento = $this->requisitos[$id];

            if ($documento['estado_documento'] !== 'activo') {
                throw new DomainException('El documento ' . $documento['nombre'] . ' está inactivo. Revisa la configuración del trámite.');
            }

            $emision = $declaracion['emision'];
            $vence = $declaracion['vence'];

            if ($documento['vigencia_dias'] !== null) {
                if ($emision === null) {
                    throw new DomainException('Indica la fecha de emisión de ' . $documento['nombre'] . ' para calcular su vigencia.');
                }

                $calculado = $emision->modify('+' . (int) $documento['vigencia_dias'] . ' days')->setTime(23, 59, 59);
                $vence = $vence === null || $calculado < $vence ? $calculado : $vence;
            }

            if ($vence !== null && $vence < $salida) {
                $avisos[] = $documento['nombre'] . ' estará vencido en la fecha de salida y vuelve a considerarse pendiente.';
                continue;
            }

            $this->disponibles[$id] = ['emision' => $emision, 'vence' => $vence];
        }

        $documentosPreviosFaltantes = [];

        foreach ($this->requisitos as $id => $documento) {
            if ((int) $documento['obligatorio'] === 1 && $documento['estado_documento'] !== 'activo') {
                throw new DomainException('El documento requerido ' . $documento['nombre'] . ' está inactivo. El administrador debe revisar el catálogo.');
            }

            if ((int) $documento['obligatorio'] === 1 && !isset($this->disponibles[$id]) && !isset($productores[$id])) {
                $documentosPreviosFaltantes[] = $documento['nombre'];
            }
        }

        if ($documentosPreviosFaltantes !== []) {
            throw new DomainException('DIRI todavía no tiene configurada la obtención de: ' . implode('; ', $documentosPreviosFaltantes) . '. Si ya los tienes vigentes, marca sus casillas. Si te faltan, el administrador debe configurar sus gestiones y lugares de atención para incluirlos en la ruta.');
        }

        $completadas = [];
        $this->productores = $productores;
        $pendientes = $gestiones;

        foreach ($gestiones as $id => $gestion) {
            $documentoId = (int) ($gestion['documento_resultado_id'] ?? 0);

            if (isset($this->disponibles[$documentoId])) {
                $emision = $this->disponibles[$documentoId]['emision'];

                foreach ($gestiones as $posterior) {
                    if (($posterior['previas'][$id] ?? 0) > 0 && $emision === null) {
                        throw new DomainException('Indica la fecha de emisión de ' . $this->requisitos[$documentoId]['nombre'] . ' para respetar la espera antes del siguiente paso.');
                    }
                }

                $completadas[$id] = ($emision ?? $salida)->getTimestamp();
                unset($pendientes[$id]);
            }
        }


        $conPosteriores = [];

        foreach ($gestiones as $gestion) {
            foreach ($gestion['previas'] as $previaId => $dias) {
                $conPosteriores[$previaId] = true;
            }
        }

        $necesarias = [];
        $incluir = function (int $id) use (&$incluir, &$necesarias, $completadas, $gestiones): void {
            if (isset($completadas[$id], $gestiones[$id]) || isset($necesarias[$id])) {
                return;
            }

            $necesarias[$id] = true;

            foreach ($gestiones[$id]['previas'] as $previaId => $dias) {
                $incluir((int) $previaId);
            }
        };

        foreach ($gestiones as $id => $gestion) {
            $documentoId = (int) ($gestion['documento_resultado_id'] ?? 0);

            if ($documentoId === 0 || !isset($conPosteriores[$id])
                || (int) ($this->requisitos[$documentoId]['obligatorio'] ?? 0) === 1) {
                $incluir((int) $id);
            }
        }

        $pendientes = array_intersect_key($pendientes, $necesarias);

        $puntos = [['latitud' => $perfil['latitud'], 'longitud' => $perfil['longitud']]];
        $indices = [$this->clavePunto($puntos[0]) => 0];

        foreach ($pendientes as &$gestion) {
            foreach ($gestion['opciones'] as &$opcion) {
                $clave = $this->clavePunto($opcion);

                if (!isset($indices[$clave])) {
                    $indices[$clave] = count($puntos);
                    $puntos[] = ['latitud' => (float) $opcion['latitud'], 'longitud' => (float) $opcion['longitud']];
                }

                $opcion['indice'] = $indices[$clave];
            }
            unset($opcion);

            if ($gestion['opciones'] === []) {
                throw new DomainException('Falta asignar un establecimiento activo a la gestión ' . $gestion['nombre'] . '.');
            }
        }
        unset($gestion);

        $claveMatriz = implode(';', array_map(fn(array $punto): string => $this->clavePunto($punto), $puntos));

        if ($this->claveMatriz !== $claveMatriz) {
            $matriz = count($puntos) === 1
                ? ['distances' => [[0]], 'durations' => [[0]]]
                : $this->solicitar('table', $puntos, 'annotations=distance,duration');

            $this->distancias = $matriz['distances'] ?? [];
            $this->duraciones = $matriz['durations'] ?? [];
            $this->claveMatriz = $claveMatriz;
        }

        if (count($this->distancias) !== count($puntos) || count($this->duraciones) !== count($puntos)) {
            throw new DomainException('El servicio de rutas devolvió una matriz incompleta. Vuelve a intentarlo.');
        }

        $this->limite = microtime(true) + 5;
        $this->explorar($pendientes, $completadas, [
            'indice' => 0, 'instante' => $salida->getTimestamp(), 'distancia' => 0.0,
            'caminata' => 0.0, 'espera' => 0, 'atencion' => 0, 'desconocidas' => 0, 'pasos' => [],
        ]);

        if ($this->mejor === null) {
            if (!$this->comparacionCompleta) {
                throw new DomainException('La comparación de recorridos necesita más tiempo. Reduce las alternativas del trámite y vuelve a intentarlo.');
            }

            $ajustado = $perfil;
            $renovaciones = [];

            foreach (array_keys($this->caducanDurante) as $documentoId) {
                if (isset($perfil['documentos'][$documentoId], $productores[$documentoId])) {
                    unset($ajustado['documentos'][$documentoId]);
                    $renovaciones[] = $this->requisitos[$documentoId]['nombre'];
                }
            }

            if ($renovaciones !== []) {
                $resultado = $this->calcular($contexto, $ajustado);

                foreach ($renovaciones as $nombre) {
                    $resultado['avisos'][] = $nombre . ' vence antes de completar el recorrido. Se incluyó su obtención.';
                }

                $resultado['avisos'] = array_merge($avisos, $resultado['avisos']);
                return $resultado;
            }

            throw new DomainException('No se encontró un recorrido completo con las conexiones peatonales, horarios, dependencias y vigencias disponibles. Revisa los datos o cambia la salida.');
        }

        $resultado = $this->mejor;
        $resultado['tramite'] = $contexto['tramite'];
        $resultado['salida'] = $salida;
        $resultado['origen'] = $puntos[0];
        $resultado['alternativas'] = $this->alternativas;
        $resultado['comparacion_completa'] = $this->comparacionCompleta;
        $resultado['avisos'] = $avisos;
        $resultado['geometry'] = null;
        $resultado['omitidas'] = count($completadas);
        $resultado['horarios_sin_confirmar'] = [];
        $resultado['visitas'] = [];

        foreach ($resultado['pasos'] as $paso) {
            if ($paso['establecimiento']['horario_pendiente'] ?? false) {
                $resultado['horarios_sin_confirmar'][(int) $paso['establecimiento']['id']] = $paso['establecimiento']['nombre'];
            }

            $ultimo = count($resultado['visitas']) - 1;

            if ($ultimo >= 0
                && $resultado['visitas'][$ultimo]['establecimiento']['id'] === $paso['establecimiento']['id']
                && date('Y-m-d', $resultado['visitas'][$ultimo]['fin']) === date('Y-m-d', $paso['inicio'])) {
                $resultado['visitas'][$ultimo]['gestiones'][] = ['nombre' => $paso['gestion'], 'descripcion' => $paso['descripcion']];
                $resultado['visitas'][$ultimo]['fin'] = $paso['fin'];
            } else {
                $paso['gestiones'] = [['nombre' => $paso['gestion'], 'descripcion' => $paso['descripcion']]];
                $resultado['visitas'][] = $paso;
            }
        }

        $resultado['desconocidas'] = count(array_filter($resultado['visitas'],
            fn(array $visita): bool => $visita['establecimiento']['duracion_atencion_minutos'] === null));

        if ($resultado['pasos'] !== [] && $resultado['distancia'] > 0) {
            $visitas = [$puntos[0]];

            foreach ($resultado['visitas'] as $paso) {
                $visitas[] = $paso['establecimiento'];
            }

            try {
                $ruta = $this->solicitar('route', $visitas, 'overview=full&geometries=geojson&steps=false&continue_straight=false');
                $resultado['geometry'] = $ruta['routes'][0]['geometry'] ?? null;
            } catch (DomainException $error) {
                $resultado['avisos'][] = 'El dibujo del mapa no está disponible. Se conserva el recorrido calculado con la matriz peatonal.';
            }
        }

        return $resultado;
    }

    private function explorar(array $pendientes, array $completadas, array $estado): void
    {
        if (microtime(true) > $this->limite) {
            $this->comparacionCompleta = false;
            return;
        }

        if ($this->mejor !== null && $estado['distancia'] > $this->mejor['distancia'] + 0.01) {
            return;
        }

        if ($pendientes === []) {
            if (!$this->documentosVigentes($completadas, $estado['instante'])) {
                return;
            }

            $this->alternativas++;

            if ($this->mejor === null || $estado['distancia'] < $this->mejor['distancia'] - 0.01
                || (abs($estado['distancia'] - $this->mejor['distancia']) <= 0.01 && $estado['instante'] < $this->mejor['instante'])) {
                $this->mejor = $estado;
            }

            return;
        }

        foreach ($pendientes as $id => $gestion) {
            if (array_diff(array_keys($gestion['previas']), array_keys($completadas)) !== []) {
                continue;
            }

            $habilitada = $estado['instante'];

            foreach ($gestion['previas'] as $previaId => $dias) {
                $anterior = (new DateTimeImmutable())->setTimestamp($completadas[$previaId]);
                $liberacion = $dias === 0 ? $completadas[$previaId] : $anterior->setTime(0, 0)->modify('+' . $dias . ' days')->getTimestamp();
                $habilitada = max($habilitada, $liberacion);
            }

            $opciones = array_values($gestion['opciones']);
            usort($opciones, fn(array $a, array $b): int =>
                ($this->distancias[$estado['indice']][$a['indice']] ?? INF) <=> ($this->distancias[$estado['indice']][$b['indice']] ?? INF));

            foreach ($opciones as $opcion) {
                $metros = $this->distancias[$estado['indice']][$opcion['indice']] ?? null;
                $segundos = $this->duraciones[$estado['indice']][$opcion['indice']] ?? null;

                if (!is_numeric($metros) || !is_numeric($segundos) || $metros < 0 || $segundos < 0) {
                    continue;
                }

                $llegada = $estado['instante'] + (int) ceil((float) $segundos);
                $atencion = $this->proximaAtencion($opcion['horarios'], max($llegada, $habilitada), $opcion['duracion_atencion_minutos'], $opcion['horario_pendiente'] ?? false);

                if ($atencion === null) {
                    continue;
                }

                $documentosPreviosValidos = true;

                foreach ($this->productores as $documentoId => $productorId) {
                    if (!array_key_exists($productorId, $gestion['previas'])) {
                        continue;
                    }

                    $vence = $this->disponibles[$documentoId]['vence'] ?? null;

                    if (!isset($this->disponibles[$documentoId]) && $this->requisitos[$documentoId]['vigencia_dias'] !== null) {
                        $emision = (new DateTimeImmutable())->setTimestamp($completadas[$productorId])->setTime(0, 0);
                        $vence = $emision->modify('+' . (int) $this->requisitos[$documentoId]['vigencia_dias'] . ' days')->setTime(23, 59, 59);
                    }

                    if ($vence !== null && $vence->getTimestamp() < $atencion['inicio']) {
                        if (isset($this->disponibles[$documentoId])) {
                            $this->caducanDurante[$documentoId] = true;
                        }

                        $documentosPreviosValidos = false;
                        break;
                    }
                }

                if (!$documentosPreviosValidos) {
                    continue;
                }

                $siguiente = $estado;
                $siguiente['indice'] = $opcion['indice'];
                $siguiente['instante'] = $atencion['fin'];
                $siguiente['distancia'] += (float) $metros;
                $siguiente['caminata'] += (float) $segundos;
                $siguiente['espera'] += $atencion['inicio'] - $llegada;
                $siguiente['atencion'] += $atencion['fin'] - $atencion['inicio'];
                $siguiente['desconocidas'] += $opcion['duracion_atencion_minutos'] === null ? 1 : 0;
                $siguiente['pasos'][] = [
                    'gestion_id' => $id, 'gestion' => $gestion['nombre'], 'descripcion' => $gestion['descripcion'],
                    'establecimiento' => $opcion, 'metros' => (float) $metros, 'segundos' => (float) $segundos,
                    'llegada' => $llegada, 'inicio' => $atencion['inicio'], 'fin' => $atencion['fin'],
                ];
                $terminadas = $completadas;
                $terminadas[$id] = $atencion['fin'];
                $restantes = $pendientes;
                unset($restantes[$id]);
                $this->explorar($restantes, $terminadas, $siguiente);
            }
        }
    }

    private function proximaAtencion(array $horarios, int $llegada, ?int $minutos, bool $horarioPendiente = false): ?array
    {
        if ($horarios === [] && $horarioPendiente) {
            return ['inicio' => $llegada, 'fin' => $llegada + ($minutos ?? 0) * 60];
        }

        $base = (new DateTimeImmutable())->setTimestamp($llegada)->setTime(0, 0);
        $mejor = null;

        foreach ($horarios as $horario) {
            $dias = ((int) $horario['dia_semana'] - (int) $base->format('N') + 7) % 7;
            $fecha = $base->modify('+' . $dias . ' days');
            $apertura = new DateTimeImmutable($fecha->format('Y-m-d') . ' ' . $horario['hora_apertura']);
            $cierre = new DateTimeImmutable($fecha->format('Y-m-d') . ' ' . $horario['hora_cierre']);
            $duracion = ($minutos ?? 0) * 60;

            if ($cierre->getTimestamp() - $apertura->getTimestamp() < $duracion) {
                continue;
            }

            $inicio = max($llegada, $apertura->getTimestamp());

            if ($inicio >= $cierre->getTimestamp() || $inicio + $duracion > $cierre->getTimestamp()) {
                $apertura = $apertura->modify('+7 days');
                $inicio = $apertura->getTimestamp();
            }

            $candidato = ['inicio' => $inicio, 'fin' => $inicio + $duracion];

            if ($mejor === null || $candidato['fin'] < $mejor['fin']) {
                $mejor = $candidato;
            }
        }

        return $mejor;
    }

    private function documentosVigentes(array $completadas, int $fin): bool
    {
        foreach ($this->requisitos as $id => $documento) {
            if ((int) $documento['obligatorio'] !== 1) {
                continue;
            }

            if (isset($this->disponibles[$id])) {
                $vence = $this->disponibles[$id]['vence'];

                if ($vence !== null && $vence->getTimestamp() < $fin) {
                    $this->caducanDurante[$id] = true;
                    return false;
                }
            } elseif ($documento['vigencia_dias'] !== null && isset($this->productores[$id])) {
                $emision = (new DateTimeImmutable())->setTimestamp($completadas[$this->productores[$id]])->setTime(0, 0);
                $vence = $emision->modify('+' . (int) $documento['vigencia_dias'] . ' days')->setTime(23, 59, 59);

                if ($vence->getTimestamp() < $fin) {
                    return false;
                }
            }
        }

        return true;
    }

    private function clavePunto(array $punto): string
    {
        return number_format((float) $punto['longitud'], 7, '.', '') . ',' . number_format((float) $punto['latitud'], 7, '.', '');
    }

    private function solicitar(string $servicio, array $puntos, string $opciones): array
    {
        $conexion = Conexion::obtener();
        $consulta = $conexion->query("SELECT GET_LOCK('diri2_rutas_peatonales', 10)");

        if ((int) $consulta->fetchColumn() !== 1) {
            throw new DomainException('El servicio de recorridos está ocupado. Vuelve a intentarlo en unos segundos.');
        }

        $curl = null;

        try {
            $coordenadas = implode(';', array_map(fn(array $punto): string => $this->clavePunto($punto), $puntos));
            $url = 'https://routing.openstreetmap.de/routed-foot/' . $servicio . '/v1/foot/' . $coordenadas . '?' . $opciones;
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 20,
                CURLOPT_USERAGENT => 'DIRI2/1.0 (local academic project)',
            ]);
            $respuesta = curl_exec($curl);
            $codigo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $datos = is_string($respuesta) ? json_decode($respuesta, true) : null;

            if ($codigo !== 200 || !is_array($datos) || ($datos['code'] ?? '') !== 'Ok') {
                throw new DomainException('El servicio peatonal no pudo calcular estas conexiones. Revisa las coordenadas y la conexión a Internet.');
            }

            return $datos;
        } finally {
            if ($curl !== null) {
                curl_close($curl);
            }


            usleep(1000000);
            $conexion->query("SELECT RELEASE_LOCK('diri2_rutas_peatonales')");
        }
    }
}

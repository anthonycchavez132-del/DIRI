<?php

declare(strict_types=1);

if (!defined('DIRI_ENTRADA')) {
    http_response_code(404);
    exit;
}

$fecha = static fn(int $instante): string => (new DateTimeImmutable())->setTimestamp($instante)->format('d/m/Y H:i');
$tiempo = static function (float $segundos): string {
    $minutos = max(0, (int) ceil($segundos / 60));
    $horas = intdiv($minutos, 60);

    return $horas > 0 ? $horas . ' h ' . ($minutos % 60) . ' min' : $minutos . ' min';
};
$mapa = ['origen' => $resultado['origen'], 'geometry' => $resultado['geometry'], 'paradas' => []];

foreach ($resultado['visitas'] as $paso) {
    $mapa['paradas'][] = [
        'latitud' => (float) $paso['establecimiento']['latitud'],
        'longitud' => (float) $paso['establecimiento']['longitud'],
    ];
}

$coordenadaGoogle = static fn(array $punto): string =>
    number_format((float) $punto['latitud'], 7, '.', '') . ',' . number_format((float) $punto['longitud'], 7, '.', '');
$enlaceGoogle = static function (array $puntos) use ($coordenadaGoogle): string {
    $origen = array_shift($puntos);
    $destino = array_pop($puntos);
    $parametros = [
        'api' => '1', 'origin' => $coordenadaGoogle($origen),
        'destination' => $coordenadaGoogle($destino), 'travelmode' => 'walking',
    ];

    if ($puntos !== []) {
        $parametros['waypoints'] = implode('|', array_map($coordenadaGoogle, $puntos));
    }

    return 'https://www.google.com/maps/dir/?' . http_build_query($parametros, '', '&', PHP_QUERY_RFC3986);
};
$tramosGoogle = [];

// Cada enlace lleva como máximo tres paradas intermedias y un destino.
// Se separan también los días para conservar la planificación de DIRI.
foreach ($resultado['visitas'] as $indice => $paso) {
    $dia = date('Y-m-d', $paso['inicio']);
    $ultimo = count($tramosGoogle) - 1;

    if ($ultimo < 0 || $tramosGoogle[$ultimo]['dia'] !== $dia || count($tramosGoogle[$ultimo]['puntos']) >= 5) {
        $tramosGoogle[] = [
            'dia' => $dia, 'primera' => $indice + 1, 'ultima' => $indice + 1,
            'puntos' => [$indice === 0 ? $resultado['origen'] : $resultado['visitas'][$indice - 1]['establecimiento']],
        ];
        $ultimo = count($tramosGoogle) - 1;
    }

    $tramosGoogle[$ultimo]['puntos'][] = $paso['establecimiento'];
    $tramosGoogle[$ultimo]['ultima'] = $indice + 1;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu recorrido | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=2">
    <link rel="stylesheet" href="publico/recorrido.css?v=1">
</head>
<body class="pagina-recorrido vista-recorrido">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1>Tu recorrido a pie</h1>
        <a href="index.php?pagina=preparar_recorrido&amp;tramite_id=<?= (int) $resultado['tramite']['id'] ?>">Cambiar mis datos</a>
    </header>
    <main id="contenido">
        <ol class="pasos-progreso" aria-label="Preparación del recorrido"><li>Trámite elegido</li><li>Documentos revisados</li><li class="actual">Tu recorrido</li></ol>
        <h2><?= htmlspecialchars($resultado['tramite']['nombre'], ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if ($resultado['horarios_sin_confirmar'] !== []): ?>
            <p role="alert"><strong>Recorrido provisional: confirma los horarios antes de salir.</strong> Falta el horario de <?= htmlspecialchars(implode(', ', $resultado['horarios_sin_confirmar']), ENT_QUOTES, 'UTF-8') ?>. Las horas mostradas suponen que esas oficinas pueden atenderte al llegar; la atención y la finalización todavía no están confirmadas.</p>
        <?php endif; ?>
        <div class="resumen-ruta">
            <div class="dato-ruta"><span>Paradas pendientes</span><strong><?= count($resultado['visitas']) ?></strong><small>Según tus documentos disponibles</small></div>
            <div class="dato-ruta"><span>Distancia a pie</span><strong><?= number_format($resultado['distancia'] / 1000, 2, ',', '.') ?> km</strong><small>Desde tu punto de partida</small></div>
            <div class="dato-ruta"><span>Caminata estimada</span><strong><?= htmlspecialchars($tiempo($resultado['caminata']), ENT_QUOTES, 'UTF-8') ?></strong><small>No incluye atención ni esperas</small></div>
            <div class="dato-ruta"><span>Finalización mínima estimada</span><strong><?= htmlspecialchars((new DateTimeImmutable())->setTimestamp($resultado['instante'])->format('d/m · H:i'), ENT_QUOTES, 'UTF-8') ?></strong><small>Sujeta a atención y horarios confirmados</small></div>
        </div>
        <?php if ($resultado['pasos'] !== []): ?>
            <figure class="mapa-contenedor">
                <canvas id="mapa" width="960" height="520" style="width: 100%; height: auto; border: 1px solid #aaa;" aria-label="Mapa del recorrido y sus paradas">Consulta las direcciones en la lista de visitas.</canvas>
                <figcaption>
                    <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">© OpenStreetMap contributors</a>
                    · <a href="https://routing.openstreetmap.de/about.html" target="_blank" rel="noopener noreferrer">Rutas FOSSGIS / OSRM</a>
                    · <a href="https://www.openstreetmap.org/fixthemap" target="_blank" rel="noopener noreferrer">Corregir el mapa</a>
                </figcaption>
            </figure>
            <p id="estado-mapa" role="status">Cargando el mapa…</p>
        <?php endif; ?>
        <details class="detalles-ruta"><summary>Ver fechas, esperas y tiempo total estimado</summary>
        <dl>
            <dt>Salida prevista</dt>
            <dd><?= htmlspecialchars($resultado['salida']->format('d/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Distancia a pie</dt>
            <dd><?= number_format($resultado['distancia'] / 1000, 2, ',', '.') ?> km</dd>
            <dt>Tiempo de caminata estimado</dt>
            <dd><?= htmlspecialchars($tiempo($resultado['caminata']), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Espera por horarios y dependencias</dt>
            <dd><?= htmlspecialchars($tiempo((float) $resultado['espera']), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Finalización estimada</dt>
            <dd><?= htmlspecialchars($fecha($resultado['instante']), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Tiempo desde la salida hasta la finalización</dt>
            <dd><?= htmlspecialchars($tiempo((float) ($resultado['instante'] - $resultado['salida']->getTimestamp())), ENT_QUOTES, 'UTF-8') ?></dd>
        </dl>
        </details>

        <?php if ($resultado['desconocidas'] > 0): ?>
            <p role="status">Falta la duración de atención en <?= (int) $resultado['desconocidas'] ?> visitas. La finalización indicada es un mínimo estimado y cambiará según la atención real.</p>
        <?php endif; ?>
        <?php foreach ($resultado['avisos'] as $aviso): ?>
            <p role="status"><?= htmlspecialchars($aviso, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endforeach; ?>
        <?php if (!$resultado['comparacion_completa']): ?>
            <p role="status">Se alcanzó el tiempo máximo de comparación. Se muestra la mejor alternativa encontrada.</p>
        <?php endif; ?>
        <p>Se evaluaron <?= (int) $resultado['alternativas'] ?> recorridos completos, priorizando distancia y después tiempo estimado. El recorrido termina en la última gestión.</p>
        <p>La planificación usa horarios habituales. Confirma con cada entidad las citas, evaluaciones y cierres excepcionales.</p>
        <?php if ($resultado['omitidas'] > 0): ?>
            <p><?= (int) $resultado['omitidas'] ?> gestiones de obtención se omitieron por los documentos que declaraste disponibles.</p>
        <?php endif; ?>

        <?php if ($resultado['pasos'] === []): ?>
            <p role="status">No hay visitas pendientes según tus documentos y las gestiones configuradas.</p>
        <?php else: ?>
            <section class="exportar-ruta" aria-labelledby="titulo-google-maps">
                <h2 id="titulo-google-maps">Llevar el recorrido a Google Maps</h2>
                <p>Los enlaces envían el punto de partida y las paradas en el orden de DIRI, con desplazamiento a pie. Google Maps calcula su propio camino; puede mostrar distancias y tiempos diferentes. Los requisitos, horarios y esperas se consultan en DIRI.</p>
                <?php if (count($tramosGoogle) > 1): ?>
                    <p>El recorrido está dividido por día y en tramos de hasta cuatro paradas. Abre cada tramo cuando corresponda.</p>
                <?php endif; ?>
                <ul>
                    <?php foreach ($tramosGoogle as $indice => $tramo): ?>
                        <li>
                            <a class="boton" href="<?= htmlspecialchars($enlaceGoogle($tramo['puntos']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= count($tramosGoogle) === 1 ? 'Abrir recorrido en Google Maps' : 'Abrir tramo ' . ($indice + 1) . ' en Google Maps' ?> ↗</a>
                            — <?= htmlspecialchars((new DateTimeImmutable($tramo['dia']))->format('d/m/Y'), ENT_QUOTES, 'UTF-8') ?>, paradas <?= (int) $tramo['primera'] ?> a <?= (int) $tramo['ultima'] ?>.
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p>También puedes abrir cada parada por separado desde la lista de visitas. Si Google Maps omite paradas intermedias en tu dispositivo, utiliza esos enlaces individuales.</p>
            </section>
            <h2>Tus visitas, paso a paso</h2>
            <ol class="lista-visitas">
                <?php foreach ($resultado['visitas'] as $indice => $paso): ?>
                    <li>
                        <h3><?= htmlspecialchars($paso['establecimiento']['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <?php foreach ($paso['gestiones'] as $actividad): ?>
                            <h4><?= htmlspecialchars($actividad['nombre'], ENT_QUOTES, 'UTF-8') ?></h4>
                            <p><?= htmlspecialchars($actividad['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endforeach; ?>
                        <?php if ($paso['establecimiento']['horario_pendiente'] ?? false): ?>
                            <p><strong>Horario pendiente de confirmación.</strong> Consulta con esta entidad antes de acudir.</p>
                        <?php endif; ?>
                        <p>
                            <?= htmlspecialchars($paso['establecimiento']['nombre'], ENT_QUOTES, 'UTF-8') ?><br>
                            <?= htmlspecialchars($paso['establecimiento']['direccion'], ENT_QUOTES, 'UTF-8') ?><br>
                            <?= htmlspecialchars($paso['establecimiento']['zona'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p>Desde la parada anterior <?= number_format($paso['metros'], 0, ',', '.') ?> m y <?= htmlspecialchars($tiempo($paso['segundos']), ENT_QUOTES, 'UTF-8') ?> a pie.</p>
                        <p>
                            Llegada estimada <?= htmlspecialchars($fecha($paso['llegada']), ENT_QUOTES, 'UTF-8') ?><br>
                            Inicio de atención <?= htmlspecialchars($fecha($paso['inicio']), ENT_QUOTES, 'UTF-8') ?><br>
                            Finalización estimada <?= htmlspecialchars($fecha($paso['fin']), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <a href="https://www.openstreetmap.org/?mlat=<?= (float) $paso['establecimiento']['latitud'] ?>&amp;mlon=<?= (float) $paso['establecimiento']['longitud'] ?>" target="_blank" rel="noopener noreferrer">Ver ubicación</a>
                        · <a href="<?= htmlspecialchars($enlaceGoogle([$indice === 0 ? $resultado['origen'] : $resultado['visitas'][$indice - 1]['establecimiento'], $paso['establecimiento']]), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Ir a esta parada con Google Maps</a>
                        <?php if ($paso['establecimiento']['fuente_url'] !== null): ?>
                            · <a href="<?= htmlspecialchars($paso['establecimiento']['fuente_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Consultar establecimiento</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </main>
    <script>
        const datosMapa = <?= json_encode($mapa, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;
        const canvas = document.getElementById('mapa');

        if (canvas) {
            dibujarMapa().catch(function () {
                document.getElementById('estado-mapa').textContent = 'No se pudo dibujar el mapa. Puedes consultar las direcciones y abrir cada ubicación.';
            });
        }

        async function dibujarMapa() {
            canvas.width = Math.min(960, Math.max(280, Math.floor(canvas.parentElement.clientWidth)));
            canvas.height = Math.min(520, Math.max(320, Math.round(canvas.width * 0.6)));
            const contexto = canvas.getContext('2d');
            const recorrido = datosMapa.geometry?.coordinates ?? [];
            const paradas = [datosMapa.origen, ...datosMapa.paradas];
            const ubicaciones = paradas.map(punto => [Number(punto.longitud), Number(punto.latitud)]);
            const coordenadas = [...recorrido, ...ubicaciones];

            // La proyección Mercator coloca las coordenadas sobre las imágenes del mapa.
            function proyectar(coordenada) {
                const latitud = Math.max(-85, Math.min(85, coordenada[1]));
                const seno = Math.sin(latitud * Math.PI / 180);
                return [(coordenada[0] + 180) / 360, 0.5 - Math.log((1 + seno) / (1 - seno)) / (4 * Math.PI)];
            }

            const proyectadas = coordenadas.map(proyectar);
            const minX = Math.min(...proyectadas.map(punto => punto[0]));
            const maxX = Math.max(...proyectadas.map(punto => punto[0]));
            const minY = Math.min(...proyectadas.map(punto => punto[1]));
            const maxY = Math.max(...proyectadas.map(punto => punto[1]));
            const ajusteX = (canvas.width - 100) / (256 * Math.max(maxX - minX, 0.000001));
            const ajusteY = (canvas.height - 100) / (256 * Math.max(maxY - minY, 0.000001));
            const zoom = Math.max(1, Math.min(17, Math.floor(Math.log2(Math.min(ajusteX, ajusteY)))));
            const escala = 256 * Math.pow(2, zoom);
            const izquierda = (minX + maxX) / 2 * escala - canvas.width / 2;
            const arriba = (minY + maxY) / 2 * escala - canvas.height / 2;

            contexto.fillStyle = '#f0f0f0';
            contexto.fillRect(0, 0, canvas.width, canvas.height);
            const imagenes = [];

            for (let x = Math.floor(izquierda / 256); x <= Math.floor((izquierda + canvas.width) / 256); x++) {
                for (let y = Math.floor(arriba / 256); y <= Math.floor((arriba + canvas.height) / 256); y++) {
                    imagenes.push(new Promise(function (resolver) {
                        const imagen = new Image();
                        imagen.onload = function () {
                            contexto.drawImage(imagen, x * 256 - izquierda, y * 256 - arriba);
                            resolver(true);
                        };
                        imagen.onerror = function () { resolver(false); };
                        imagen.src = 'https://tile.openstreetmap.org/' + zoom + '/' + x + '/' + y + '.png';
                    }));
                }
            }

            const cargadas = await Promise.all(imagenes);

            function posicion(coordenada) {
                const punto = proyectar(coordenada);
                return [punto[0] * escala - izquierda, punto[1] * escala - arriba];
            }

            contexto.strokeStyle = '#1256a0';
            contexto.lineWidth = 5;
            contexto.lineJoin = 'round';
            contexto.lineCap = 'round';
            contexto.beginPath();

            recorrido.forEach(function (coordenada, indice) {
                const punto = posicion(coordenada);
                if (indice === 0) { contexto.moveTo(punto[0], punto[1]); }
                else { contexto.lineTo(punto[0], punto[1]); }
            });
            contexto.stroke();

            const grupos = [];

            ubicaciones.forEach(function (coordenada, indice) {
                const punto = posicion(coordenada);
                const cercano = grupos.find(grupo => Math.hypot(grupo.x - punto[0], grupo.y - punto[1]) < 24);
                const etiqueta = indice === 0 ? 'I' : String(indice);

                if (cercano) {
                    cercano.etiquetas.push(etiqueta);
                } else {
                    grupos.push({x: punto[0], y: punto[1], etiquetas: [etiqueta]});
                }
            });

            grupos.forEach(function (grupo) {
                const texto = grupo.etiquetas.join(', ');
                const ancho = Math.max(28, texto.length * 9 + 12);
                contexto.fillStyle = grupo.etiquetas.includes('I') ? '#176c3d' : '#1256a0';
                contexto.fillRect(grupo.x - ancho / 2, grupo.y - 14, ancho, 28);
                contexto.fillStyle = '#fff';
                contexto.font = 'bold 14px Arial';
                contexto.textAlign = 'center';
                contexto.textBaseline = 'middle';
                contexto.fillText(texto, grupo.x, grupo.y);
            });

            document.getElementById('estado-mapa').textContent = cargadas.every(Boolean)
                ? 'I indica el inicio. Los números corresponden a las visitas. Las paradas cercanas comparten marcador.'
                : 'Algunas imágenes del mapa no cargaron. Se muestran el trazado disponible y las paradas.';
        }
    </script>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

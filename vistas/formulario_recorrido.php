<?php

declare(strict_types=1);

if (!defined('DIRI_ENTRADA')) {
    http_response_code(404);
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preparar recorrido | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=3">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
    <link rel="stylesheet" href="publico/recorrido.css?v=1">
</head>
<body class="pagina-formulario vista-formulario-recorrido">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1>Prepara tu recorrido a pie</h1>
        <a href="index.php?pagina=tramites">Consultar trámites</a>
    </header>
    <main id="contenido">
        <nav class="navegacion-pantalla" aria-label="Navegación de pantalla">
            <a class="boton secundario" href="index.php?pagina=tramites"><span aria-hidden="true">←</span> Volver a trámites</a>
            <a class="boton secundario" href="index.php">Inicio</a>
        </nav>
        <ol class="pasos-progreso" aria-label="Preparación del recorrido"><li class="<?= $contexto === null ? 'actual' : '' ?>">Elige tu trámite</li><li class="<?= $contexto !== null ? 'actual' : '' ?>">Tu ubicación y documentos</li><li>Tu recorrido</li></ol>
        <?php if ($errores !== []): ?>
            <section id="errores-recorrido" role="alert" tabindex="-1">
            <h2>No se pudo calcular el recorrido</h2>
            <p>Revisa lo siguiente. Los datos que ingresaste se conservan.</p>
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
            </section>
        <?php endif; ?>

        <form action="index.php" method="get">
            <input type="hidden" name="pagina" value="preparar_recorrido">
            <label for="tramite">Trámite</label>
            <select id="tramite" name="tramite_id" required>
                <option value="">Seleccionar</option>
                <?php foreach ($tramites as $tramite): ?>
                    <option value="<?= (int) $tramite['id'] ?>" <?= (string) $datos['tramite_id'] === (string) $tramite['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tramite['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Continuar</button>
        </form>

        <?php if ($tramites === []): ?>
            <p role="status">El catálogo todavía necesita trámites activos.</p>
        <?php endif; ?>

        <?php if ($contexto !== null): ?>
            <section class="resumen-tramite" aria-labelledby="titulo-tramite">
            <h2 id="titulo-tramite"><?= htmlspecialchars($contexto['tramite']['nombre'], ENT_QUOTES, 'UTF-8') ?></h2>
            <details><summary>Conoce el alcance y las condiciones de este trámite</summary>
            <p><?= htmlspecialchars($contexto['tramite']['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($contexto['tramite']['fuente_url'] !== null): ?>
                <p><a href="<?= htmlspecialchars($contexto['tramite']['fuente_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Requisitos oficiales del trámite</a></p>
            <?php endif; ?>
            </details>
            </section>
            <form action="index.php?pagina=calcular_recorrido#errores-recorrido" method="post" id="perfil-recorrido">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="tramite_id" value="<?= (int) $contexto['tramite']['id'] ?>">

                <fieldset>
                    <legend>Punto de partida</legend>
                    <button type="button" id="ubicacion">Usar mi ubicación</button>
                    <p id="estado-ubicacion" role="status">Pulsa «Usar mi ubicación» y permite el acceso para establecer tu punto de partida.</p>
                    <input type="hidden" id="latitud" name="latitud" value="<?= htmlspecialchars($datos['latitud'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" id="longitud" name="longitud" value="<?= htmlspecialchars($datos['longitud'], ENT_QUOTES, 'UTF-8') ?>">
                    <noscript><p>Activa JavaScript en tu navegador para obtener tu ubicación y preparar el recorrido.</p></noscript>
                    <p>El recorrido se calculará desde la fecha y hora actuales de La Paz, respetando los horarios de atención.</p>
                </fieldset>

                <fieldset>
                    <legend>Documentos que ya tienes disponibles</legend>
                    <p>Marca únicamente los documentos vigentes que ya tienes. DIRI incluirá en el recorrido la obtención de los que te falten. Completa la emisión cuando exista una vigencia o una espera configurada.</p>
                    <div class="documentos-lista">
                    <?php foreach ($contexto['documentos'] as $documentoId => $documento): ?>
                        <?php $declaracion = $datos['documentos'][$documentoId] ?? null; ?>
                        <fieldset class="documento-tarjeta">
                            <legend><?= htmlspecialchars($documento['nombre'], ENT_QUOTES, 'UTF-8') ?></legend>
                            <?php if ((int) $documento['obligatorio'] === 0): ?>
                                <p class="documento-estado">Documento auxiliar</p>
                            <?php endif; ?>
                            <?php if ($documento['se_obtiene_en_recorrido']): ?>
                                <p class="documento-estado">DIRI incluye su obtención si hace falta</p>
                            <?php endif; ?>
                            <?php if (!$documento['se_obtiene_en_recorrido'] && (int) $documento['obligatorio'] === 1): ?>
                                <p>Debes preparar este documento antes de iniciar el recorrido.</p>
                            <?php endif; ?>
                            <p><label>
                                <input type="checkbox" name="documentos[<?= $documentoId ?>][tengo]" value="1" <?= $declaracion !== null ? 'checked' : '' ?>>
                                Ya lo tengo disponible y vigente
                            </label></p>
                            <?php if ($documento['vigencia_dias'] !== null): ?>
                                <p>Vigencia registrada <?= (int) $documento['vigencia_dias'] ?> días desde la emisión.</p>
                            <?php endif; ?>
                            <?php if ($documento['observaciones'] !== null): ?>
                                <details><summary>Qué debes saber</summary><p><?= htmlspecialchars($documento['observaciones'], ENT_QUOTES, 'UTF-8') ?></p></details>
                            <?php endif; ?>
                            <div class="fechas-documento">
                            <p>
                                <label for="emision-<?= $documentoId ?>">Fecha de emisión</label>
                                <input type="date" id="emision-<?= $documentoId ?>" name="documentos[<?= $documentoId ?>][emision]" value="<?= htmlspecialchars($declaracion['emision'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </p>
                            <p>
                                <label for="vence-<?= $documentoId ?>">Fecha de vencimiento (si la conoces)</label>
                                <input type="date" id="vence-<?= $documentoId ?>" name="documentos[<?= $documentoId ?>][vence]" value="<?= htmlspecialchars($declaracion['vence'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </p>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="acciones-calculo">
                <p>Se prioriza la menor distancia entre las alternativas calculadas y se usa el tiempo estimado como desempate.</p>
                <p>Al calcular, tu punto de partida y los lugares de atención se envían al servicio peatonal de FOSSGIS. Tus selecciones se conservan en esta sesión.</p>
                <button type="submit" id="calcular">Calcular recorrido <span aria-hidden="true">→</span></button>
                <p id="estado-calculo" role="status"></p>
                </div>
            </form>
            <p><a href="https://routing.openstreetmap.de/about.html" target="_blank" rel="noopener noreferrer">Servicio peatonal y privacidad</a></p>
        <?php endif; ?>
    </main>
    <script>
        const botonUbicacion = document.getElementById('ubicacion');
        const formulario = document.getElementById('perfil-recorrido');
        const erroresRecorrido = document.getElementById('errores-recorrido');
        const ubicacionLista = function () {
            const latitud = document.getElementById('latitud');
            const longitud = document.getElementById('longitud');
            const formato = /^-?[0-9]{1,3}(?:\.[0-9]{1,7})?$/;
            return latitud && longitud && formato.test(latitud.value) && formato.test(longitud.value)
                && Math.abs(Number(latitud.value)) <= 90 && Math.abs(Number(longitud.value)) <= 180;
        };

        if (ubicacionLista()) {
            document.getElementById('estado-ubicacion').textContent = 'Tu punto de partida está disponible en esta sesión. Pulsa «Usar mi ubicación» para actualizarlo.';
        }

        if (erroresRecorrido) {
            erroresRecorrido.focus();
            erroresRecorrido.scrollIntoView({block: 'start'});
        }

        if (formulario) {
            formulario.addEventListener('submit', function (evento) {
                if (!ubicacionLista()) {
                    evento.preventDefault();
                    document.getElementById('estado-ubicacion').textContent = 'Primero pulsa «Usar mi ubicación» y permite el acceso para calcular tu recorrido.';
                    botonUbicacion.focus();
                    botonUbicacion.scrollIntoView({block: 'center'});
                    return;
                }

                document.getElementById('calcular').disabled = true;
                document.getElementById('estado-calculo').textContent = 'Calculando las rutas a pie y comparando establecimientos…';
            });
        }

        if (botonUbicacion) {
            botonUbicacion.addEventListener('click', function () {
                const estado = document.getElementById('estado-ubicacion');

                if (!navigator.geolocation) {
                    estado.textContent = 'Este navegador no permite obtener tu ubicación. Abre DIRI en un navegador con acceso a la ubicación.';
                    return;
                }

                botonUbicacion.disabled = true;
                document.getElementById('calcular').disabled = true;
                document.getElementById('latitud').value = '';
                document.getElementById('longitud').value = '';
                estado.textContent = 'Obteniendo tu ubicación…';

                navigator.geolocation.getCurrentPosition(function (posicion) {
                    document.getElementById('latitud').value = posicion.coords.latitude.toFixed(7);
                    document.getElementById('longitud').value = posicion.coords.longitude.toFixed(7);
                    estado.textContent = 'Ubicación obtenida. Precisión aproximada de ' + Math.round(posicion.coords.accuracy) + ' metros.';
                    botonUbicacion.disabled = false;
                    document.getElementById('calcular').disabled = false;
                }, function (error) {
                    estado.textContent = error.code === 1
                        ? 'El permiso de ubicación está bloqueado. Habilítalo en los permisos de este sitio y vuelve a pulsar «Usar mi ubicación».'
                        : 'No se pudo obtener tu ubicación. Comprueba que la ubicación del dispositivo esté activada y vuelve a intentarlo.';
                    botonUbicacion.disabled = false;
                    document.getElementById('calcular').disabled = false;
                }, {enableHighAccuracy: true, timeout: 15000, maximumAge: 60000});
            });
        }
    </script>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

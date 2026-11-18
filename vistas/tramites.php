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
    <title>Trámites | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=2">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
    <?php if ($esAdministrador): ?>
        <link rel="stylesheet" href="publico/administracion.css?v=1">
    <?php else: ?>
        <link rel="stylesheet" href="publico/tramites.css?v=1">
    <?php endif; ?>
</head>
<body class="pagina-administracion vista-tramites">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1>Trámites</h1>
        <nav aria-label="Navegación principal">
            <?php if ($usuario !== null): ?>
                <a href="index.php?pagina=panel">Mi cuenta</a>
            <?php else: ?>
                <a href="index.php?pagina=login">Iniciar sesión</a>
            <?php endif; ?>
            <?php if ($esAdministrador): ?>
                <a href="index.php?pagina=nuevo_tramite">Crear trámite</a>
            <?php endif; ?>
        </nav>
    </header>

    <main id="contenido">
        <p class="introduccion-pagina">Elige lo que necesitas hacer. DIRI te ayudará a preparar los requisitos y organizar las visitas.</p>
        <?php if ($mensaje !== ''): ?>
            <p role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form action="index.php" method="get" role="search">
            <input type="hidden" name="pagina" value="tramites">
            <label for="buscar">Buscar por nombre o descripción</label>
            <input type="search" id="buscar" name="buscar" maxlength="200" placeholder="Por ejemplo: licencia o cédula de identidad" value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Buscar</button>
            <a href="index.php?pagina=tramites">Ver todos</a>
        </form>

        <?php if ($tramites === []): ?>
            <p role="status"><?= $busqueda === '' ? 'Actualmente no hay trámites disponibles.' : 'No se encontraron trámites para esa búsqueda.' ?></p>
        <?php else: ?>
            <p>Resultados encontrados <?= count($tramites) ?></p>
            <?php if (!$esAdministrador): ?>
                <div class="catalogo">
                    <?php foreach ($tramites as $indice => $tramite): ?>
                        <article class="tramite-tarjeta">
                            <span class="numero-catalogo">TRÁMITE <?= sprintf('%02d', $indice + 1) ?></span>
                            <h2><?= htmlspecialchars($tramite['nombre'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p><?= htmlspecialchars(mb_strimwidth($tramite['descripcion'], 0, 155, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></p>
                            <details><summary>Ver detalles y condiciones</summary><p><?= nl2br(htmlspecialchars($tramite['descripcion'], ENT_QUOTES, 'UTF-8')) ?></p></details>
                            <a class="boton" href="index.php?pagina=preparar_recorrido&amp;tramite_id=<?= (int) $tramite['id'] ?>">Preparar mi recorrido <span aria-hidden="true">→</span></a>
                            <?php if ($tramite['fuente_url'] !== null): ?>
                                <a class="fuente" href="<?= htmlspecialchars($tramite['fuente_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Consultar fuente oficial ↗</a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
            <div class="tabla-contenedor" role="region" aria-label="Listado de registros" tabindex="0"><table>
                <thead>
                    <tr>
                        <th scope="col">Trámite</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Fuente y verificación</th>
                        <?php if ($esAdministrador): ?>
                            <th scope="col">Estado</th>
                            <th scope="col">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tramites as $tramite): ?>
                        <tr>
                            <th scope="row">
                                <?= htmlspecialchars($tramite['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($tramite['estado'] === 'activo'): ?>
                                    <br><a href="index.php?pagina=preparar_recorrido&amp;tramite_id=<?= (int) $tramite['id'] ?>">Preparar recorrido</a>
                                <?php endif; ?>
                            </th>
                            <td><?= nl2br(htmlspecialchars($tramite['descripcion'], ENT_QUOTES, 'UTF-8')) ?></td>
                            <td>
                                <?php if ($tramite['fuente_url'] !== null): ?>
                                    <a href="<?= htmlspecialchars($tramite['fuente_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Consultar fuente</a>
                                <?php else: ?>
                                    Fuente pendiente de registrar.
                                <?php endif; ?>
                                <br>
                                <?php if ($tramite['fecha_verificacion'] !== null): ?>
                                    Verificado el <?= htmlspecialchars($tramite['fecha_verificacion'], ENT_QUOTES, 'UTF-8') ?>.
                                <?php else: ?>
                                    Fecha de verificación pendiente.
                                <?php endif; ?>
                            </td>
                            <?php if ($esAdministrador): ?>
                                <td><?= htmlspecialchars($tramite['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a href="index.php?pagina=editar_tramite&amp;id=<?= (int) $tramite['id'] ?>">Editar</a>
                                    <a href="index.php?pagina=gestiones&amp;tramite_id=<?= (int) $tramite['id'] ?>">Gestiones</a>
                                    <?php if ($tramite['estado'] === 'activo'): ?>
                                        <form action="index.php?pagina=desactivar_tramite" method="post">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $tramite['id'] ?>">
                                            <button type="submit">Desactivar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

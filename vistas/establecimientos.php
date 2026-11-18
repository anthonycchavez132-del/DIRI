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
    <title>Establecimientos | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=2">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
    <link rel="stylesheet" href="publico/administracion.css?v=1">
</head>
<body class="pagina-administracion vista-establecimientos">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1>Establecimientos</h1>
        <nav aria-label="Administración de establecimientos">
            <a href="index.php?pagina=panel">Mi cuenta</a>
            <a href="index.php?pagina=nuevo_establecimiento">Crear establecimiento</a>
        </nav>
    </header>

    <main id="contenido">
        <?php if ($mensaje !== ''): ?>
            <p role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form action="index.php" method="get" role="search">
            <input type="hidden" name="pagina" value="establecimientos">
            <label for="buscar">Buscar por nombre, dirección o zona</label>
            <input type="search" id="buscar" name="buscar" maxlength="200" value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Buscar</button>
            <a href="index.php?pagina=establecimientos">Ver todos</a>
        </form>

        <?php if ($establecimientos === []): ?>
            <p role="status"><?= $busqueda === '' ? 'Todavía no hay establecimientos registrados.' : 'No se encontraron establecimientos para esa búsqueda.' ?></p>
        <?php else: ?>
            <div class="tabla-contenedor" role="region" aria-label="Listado de registros" tabindex="0"><table>
                <thead>
                    <tr>
                        <th scope="col">Establecimiento</th>
                        <th scope="col">Dirección y zona</th>
                        <th scope="col">Coordenadas</th>
                        <th scope="col">Teléfono</th>
                        <th scope="col">Fuente y verificación</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($establecimientos as $establecimiento): ?>
                        <tr>
                            <th scope="row"><?= htmlspecialchars($establecimiento['nombre'], ENT_QUOTES, 'UTF-8') ?></th>
                            <td>
                                <?= htmlspecialchars($establecimiento['direccion'], ENT_QUOTES, 'UTF-8') ?><br>
                                <?= htmlspecialchars($establecimiento['zona'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                Latitud <?= htmlspecialchars($establecimiento['latitud'], ENT_QUOTES, 'UTF-8') ?><br>
                                Longitud <?= htmlspecialchars($establecimiento['longitud'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= htmlspecialchars($establecimiento['telefono'] ?? 'Sin registrar', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($establecimiento['fuente_url'] !== null): ?>
                                    <a href="<?= htmlspecialchars($establecimiento['fuente_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Consultar fuente</a>
                                <?php else: ?>
                                    Fuente pendiente de registrar.
                                <?php endif; ?>
                                <br>
                                <?php if ($establecimiento['fecha_verificacion'] !== null): ?>
                                    Verificado el <?= htmlspecialchars($establecimiento['fecha_verificacion'], ENT_QUOTES, 'UTF-8') ?>.
                                <?php else: ?>
                                    Fecha de verificación pendiente.
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($establecimiento['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a href="index.php?pagina=editar_establecimiento&amp;id=<?= (int) $establecimiento['id'] ?>">Editar</a>
                                <a href="index.php?pagina=horarios&amp;establecimiento_id=<?= (int) $establecimiento['id'] ?>">Horarios</a>
                                <?php if ($establecimiento['estado'] === 'activo'): ?>
                                    <form action="index.php?pagina=desactivar_establecimiento" method="post">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="id" value="<?= (int) $establecimiento['id'] ?>">
                                        <button type="submit">Desactivar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

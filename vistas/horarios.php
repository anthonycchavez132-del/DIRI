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
    <title>Horarios | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=3">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
    <link rel="stylesheet" href="publico/administracion.css?v=1">
</head>
<body class="pagina-administracion vista-horarios">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <a href="index.php?pagina=panel">Mi cuenta</a>
        <h1>Horarios de atención</h1>
        <a href="index.php?pagina=nuevo_horario<?= $establecimientoId === null ? '' : '&amp;establecimiento_id=' . $establecimientoId ?>">Crear horario</a>
    </header>
    <main id="contenido">
        <nav class="navegacion-pantalla" aria-label="Navegación de pantalla">
            <a class="boton secundario" href="index.php?pagina=panel"><span aria-hidden="true">←</span> Volver al panel</a>
            <a class="boton secundario" href="index.php">Inicio</a>
            <a class="boton siguiente" href="index.php?pagina=nuevo_horario">Crear horario <span aria-hidden="true">→</span></a>
        </nav>
        <?php if ($mensaje !== ''): ?>
            <p role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form action="index.php" method="get">
            <input type="hidden" name="pagina" value="horarios">
            <label for="establecimiento_id">Establecimiento</label>
            <select name="establecimiento_id" id="establecimiento_id">
                <option value="">Todos</option>
                <?php foreach ($establecimientos as $establecimiento): ?>
                    <option value="<?= (int) $establecimiento['id'] ?>" <?= $establecimientoId === (int) $establecimiento['id'] ? 'selected' : '' ?>><?= htmlspecialchars($establecimiento['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrar</button>
        </form>

        <?php if ($horarios === []): ?>
            <p role="status">Todavía no hay horarios para esta selección.</p>
        <?php else: ?>
            <div class="tabla-contenedor" role="region" aria-label="Listado de registros" tabindex="0"><table>
                <thead>
                    <tr>
                        <th scope="col">Establecimiento</th>
                        <th scope="col">Día</th>
                        <th scope="col">Apertura</th>
                        <th scope="col">Cierre</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($horarios as $horario): ?>
                        <tr>
                            <th scope="row"><?= htmlspecialchars($horario['establecimiento'], ENT_QUOTES, 'UTF-8') ?></th>
                            <td><?= htmlspecialchars($dias[(int) $horario['dia_semana']], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($horario['hora_apertura'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($horario['hora_cierre'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($horario['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a href="index.php?pagina=editar_horario&amp;id=<?= (int) $horario['id'] ?>">Editar</a>
                                <?php if ($horario['estado'] === 'activo'): ?>
                                    <form action="index.php?pagina=desactivar_horario" method="post">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="id" value="<?= (int) $horario['id'] ?>">
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

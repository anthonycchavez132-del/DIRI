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
    <title>Gestiones | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=2">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
    <link rel="stylesheet" href="publico/administracion.css?v=1">
</head>
<body class="pagina-administracion vista-gestiones">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <a href="index.php?pagina=panel">Mi cuenta</a>
        <h1>Gestiones de los trámites</h1>
        <a href="index.php?pagina=nueva_gestion<?= $tramiteId === null ? '' : '&amp;tramite_id=' . $tramiteId ?>">Crear gestión</a>
    </header>
    <main id="contenido">
        <?php if ($mensaje !== ''): ?>
            <p role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form action="index.php" method="get">
            <input type="hidden" name="pagina" value="gestiones">
            <label for="tramite_id">Trámite</label>
            <select id="tramite_id" name="tramite_id">
                <option value="">Todos</option>
                <?php foreach ($tramites as $tramite): ?>
                    <option value="<?= (int) $tramite['id'] ?>" <?= $tramiteId === (int) $tramite['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tramite['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrar</button>
        </form>

        <?php if ($gestiones === []): ?>
            <p role="status">Todavía no hay gestiones para esta selección.</p>
        <?php else: ?>
            <div class="tabla-contenedor" role="region" aria-label="Listado de registros" tabindex="0"><table>
                <thead>
                    <tr>
                        <th scope="col">Trámite</th>
                        <th scope="col">Orden</th>
                        <th scope="col">Gestión</th>
                        <th scope="col">Documento que produce</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gestiones as $gestion): ?>
                        <tr>
                            <td><?= htmlspecialchars($gestion['tramite'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $gestion['orden'] ?></td>
                            <th scope="row"><?= htmlspecialchars($gestion['nombre'], ENT_QUOTES, 'UTF-8') ?></th>
                            <td><?= htmlspecialchars($gestion['documento_resultado'] ?? 'Sin documento de resultado', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($gestion['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a href="index.php?pagina=editar_gestion&amp;id=<?= (int) $gestion['id'] ?>">Editar</a>
                                <?php if ($gestion['estado'] === 'activo'): ?>
                                    <form action="index.php?pagina=desactivar_gestion" method="post">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="id" value="<?= (int) $gestion['id'] ?>">
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

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
    <title>Documentos | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=2">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
    <link rel="stylesheet" href="publico/administracion.css?v=1">
</head>
<body class="pagina-administracion vista-documentos">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1>Documentos</h1>
        <nav aria-label="Administración de documentos">
            <a href="index.php?pagina=panel">Mi cuenta</a>
            <a href="index.php?pagina=nuevo_documento">Crear documento</a>
        </nav>
    </header>

    <main id="contenido">
        <?php if ($mensaje !== ''): ?>
            <p role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form action="index.php" method="get" role="search">
            <input type="hidden" name="pagina" value="documentos">
            <label for="buscar">Buscar por nombre o descripción</label>
            <input type="search" id="buscar" name="buscar" maxlength="200" value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Buscar</button>
            <a href="index.php?pagina=documentos">Ver todos</a>
        </form>

        <?php if ($documentos === []): ?>
            <p role="status"><?= $busqueda === '' ? 'Todavía no hay documentos registrados.' : 'No se encontraron documentos para esa búsqueda.' ?></p>
        <?php else: ?>
            <div class="tabla-contenedor" role="region" aria-label="Listado de registros" tabindex="0"><table>
                <thead>
                    <tr>
                        <th scope="col">Documento</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $documento): ?>
                        <tr>
                            <th scope="row"><?= htmlspecialchars($documento['nombre'], ENT_QUOTES, 'UTF-8') ?></th>
                            <td><?= nl2br(htmlspecialchars($documento['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td>
                            <td><?= htmlspecialchars($documento['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a href="index.php?pagina=editar_documento&amp;id=<?= (int) $documento['id'] ?>">Editar</a>
                                <?php if ($documento['estado'] === 'activo'): ?>
                                    <form action="index.php?pagina=desactivar_documento" method="post">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="id" value="<?= (int) $documento['id'] ?>">
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

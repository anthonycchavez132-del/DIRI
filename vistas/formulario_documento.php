<?php

declare(strict_types=1);

if (!defined('DIRI_ENTRADA')) {
    http_response_code(404);
    exit;
}

$titulo = $id === null ? 'Crear documento' : 'Editar documento';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?> | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=3">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
</head>
<body class="pagina-formulario vista-formulario-documento">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="index.php?pagina=documentos">Volver a documentos</a>
    </header>

    <main id="contenido">
        <nav class="navegacion-pantalla" aria-label="Navegación de pantalla">
            <a class="boton secundario" href="index.php?pagina=documentos"><span aria-hidden="true">←</span> Volver a documentos</a>
            <a class="boton secundario" href="index.php">Inicio</a>
        </nav>
        <?php if ($errores !== []): ?>
            <ul role="alert">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="index.php?pagina=guardar_documento" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($id !== null): ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">
            <?php endif; ?>

            <p>
                <label for="nombre">Nombre del documento</label><br>
                <input type="text" id="nombre" name="nombre" required maxlength="200" value="<?= htmlspecialchars($datos['nombre'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="descripcion">Descripción (opcional)</label><br>
                <textarea id="descripcion" name="descripcion" maxlength="5000" rows="5" cols="50"><?= htmlspecialchars($datos['descripcion'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </p>

            <p>
                <label for="estado">Estado</label><br>
                <select id="estado" name="estado" required>
                    <option value="activo" <?= $datos['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= $datos['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </p>

            <button type="submit">Guardar documento <span aria-hidden="true">→</span></button>
        </form>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

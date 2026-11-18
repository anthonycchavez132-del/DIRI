<?php

declare(strict_types=1);

if (!defined('DIRI_ENTRADA')) {
    http_response_code(404);
    exit;
}

$titulo = $id === null ? 'Crear establecimiento' : 'Editar establecimiento';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?> | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=2">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
</head>
<body class="pagina-formulario vista-formulario-establecimiento">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="index.php?pagina=establecimientos">Volver a establecimientos</a>
    </header>

    <main id="contenido">
        <?php if ($errores !== []): ?>
            <ul role="alert">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="index.php?pagina=guardar_establecimiento" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($id !== null): ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">
            <?php endif; ?>

            <p>
                <label for="nombre">Nombre del establecimiento</label><br>
                <input type="text" id="nombre" name="nombre" required maxlength="200" value="<?= htmlspecialchars($datos['nombre'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="direccion">Dirección</label><br>
                <input type="text" id="direccion" name="direccion" required maxlength="500" value="<?= htmlspecialchars($datos['direccion'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="zona">Zona</label><br>
                <input type="text" id="zona" name="zona" required maxlength="100" value="<?= htmlspecialchars($datos['zona'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="latitud">Latitud</label><br>
                <input type="number" id="latitud" name="latitud" required min="-90" max="90" step="0.0000001" value="<?= htmlspecialchars($datos['latitud'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="longitud">Longitud</label><br>
                <input type="number" id="longitud" name="longitud" required min="-180" max="180" step="0.0000001" value="<?= htmlspecialchars($datos['longitud'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="telefono">Teléfono (opcional)</label><br>
                <input type="tel" id="telefono" name="telefono" maxlength="30" value="<?= htmlspecialchars($datos['telefono'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="fuente_url">Enlace de la fuente consultada (opcional)</label><br>
                <input type="url" id="fuente_url" name="fuente_url" maxlength="2048" value="<?= htmlspecialchars($datos['fuente_url'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="fecha_verificacion">Fecha de consulta y verificación (opcional)</label><br>
                <input type="date" id="fecha_verificacion" name="fecha_verificacion" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($datos['fecha_verificacion'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="estado">Estado</label><br>
                <select id="estado" name="estado" required>
                    <option value="activo" <?= $datos['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= $datos['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </p>

            <button type="submit">Guardar establecimiento</button>
        </form>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

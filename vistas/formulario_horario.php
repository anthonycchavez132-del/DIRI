<?php

declare(strict_types=1);

if (!defined('DIRI_ENTRADA')) {
    http_response_code(404);
    exit;
}

$titulo = $id === null ? 'Crear horario' : 'Editar horario';

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
<body class="pagina-formulario vista-formulario-horario">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="index.php?pagina=horarios">Volver a horarios</a>
    </header>
    <main id="contenido">
        <nav class="navegacion-pantalla" aria-label="Navegación de pantalla">
            <a class="boton secundario" href="index.php?pagina=horarios"><span aria-hidden="true">←</span> Volver a horarios</a>
            <a class="boton secundario" href="index.php">Inicio</a>
        </nav>
        <?php if ($errores !== []): ?>
            <ul role="alert">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form action="index.php?pagina=guardar_horario" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($id !== null): ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <input type="hidden" name="establecimiento_id" value="<?= (int) $datos['establecimiento_id'] ?>">
            <?php endif; ?>
            <p>
                <label for="establecimiento_id">Establecimiento</label><br>
                <select id="establecimiento_id" name="establecimiento_id" required <?= $id === null ? '' : 'disabled' ?>>
                    <option value="">Seleccionar</option>
                    <?php foreach ($establecimientos as $establecimiento): ?>
                        <option value="<?= (int) $establecimiento['id'] ?>" <?= (string) $datos['establecimiento_id'] === (string) $establecimiento['id'] ? 'selected' : '' ?>><?= htmlspecialchars($establecimiento['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="dia_semana">Día de atención</label><br>
                <select id="dia_semana" name="dia_semana" required>
                    <option value="">Seleccionar</option>
                    <?php foreach ($dias as $numero => $nombre): ?>
                        <option value="<?= $numero ?>" <?= (string) $datos['dia_semana'] === (string) $numero ? 'selected' : '' ?>><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="hora_apertura">Hora de apertura</label><br>
                <input type="time" id="hora_apertura" name="hora_apertura" required step="1" value="<?= htmlspecialchars($datos['hora_apertura'], ENT_QUOTES, 'UTF-8') ?>">
            </p>
            <p>
                <label for="hora_cierre">Hora de cierre</label><br>
                <input type="time" id="hora_cierre" name="hora_cierre" required step="1" value="<?= htmlspecialchars($datos['hora_cierre'], ENT_QUOTES, 'UTF-8') ?>">
            </p>
            <p>
                <label for="estado">Estado</label>
                <select id="estado" name="estado" required>
                    <option value="activo" <?= $datos['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= $datos['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </p>
            <button type="submit">Guardar horario <span aria-hidden="true">→</span></button>
        </form>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

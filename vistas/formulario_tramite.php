<?php

declare(strict_types=1);

if (!defined('DIRI_ENTRADA')) {
    http_response_code(404);
    exit;
}

$titulo = $id === null ? 'Crear trámite' : 'Editar trámite';

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
<body class="pagina-formulario vista-formulario-tramite">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="index.php?pagina=tramites">Volver a trámites</a>
    </header>

    <main id="contenido">
        <nav class="navegacion-pantalla" aria-label="Navegación de pantalla">
            <a class="boton secundario" href="index.php?pagina=tramites"><span aria-hidden="true">←</span> Volver a trámites</a>
            <a class="boton secundario" href="index.php">Inicio</a>
        </nav>
        <?php if ($errores !== []): ?>
            <ul role="alert">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="index.php?pagina=guardar_tramite" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="requisitos_presentes" value="1">
            <?php if ($id !== null): ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">
            <?php endif; ?>

            <p>
                <label for="nombre">Nombre del trámite</label><br>
                <input type="text" id="nombre" name="nombre" required maxlength="200" value="<?= htmlspecialchars($datos['nombre'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="descripcion">Descripción</label><br>
                <textarea id="descripcion" name="descripcion" required maxlength="5000" rows="6" cols="50"><?= htmlspecialchars($datos['descripcion'], ENT_QUOTES, 'UTF-8') ?></textarea>
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

            <fieldset>
                <legend>Documentos del trámite</legend>
                <p>Selecciona los documentos asociados. Registra su vigencia únicamente cuando esté confirmada por la fuente.</p>
                <?php foreach ($documentos as $documento): ?>
                    <?php
                    $documentoId = (int) $documento['id'];
                    $requisito = $requisitos[$documentoId] ?? null;
                    ?>
                    <fieldset>
                        <legend><?= htmlspecialchars($documento['nombre'] . ' (' . $documento['estado'] . ')', ENT_QUOTES, 'UTF-8') ?></legend>
                        <p><label>
                            <input type="checkbox" name="requisitos[<?= $documentoId ?>][seleccionado]" value="1" <?= $requisito !== null ? 'checked' : '' ?>>
                            Incluir en este trámite
                        </label></p>
                        <p><label>
                            <input type="checkbox" name="requisitos[<?= $documentoId ?>][obligatorio]" value="1" <?= $requisito === null || (int) $requisito['obligatorio'] === 1 ? 'checked' : '' ?>>
                            Obligatorio
                        </label></p>
                        <p>
                            <label for="vigencia-<?= $documentoId ?>">Vigencia en días (opcional)</label>
                            <input type="number" id="vigencia-<?= $documentoId ?>" name="requisitos[<?= $documentoId ?>][vigencia_dias]" min="1" max="65535" step="1" value="<?= htmlspecialchars((string) ($requisito['vigencia_dias'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </p>
                        <p>
                            <label for="observaciones-<?= $documentoId ?>">Observaciones (opcional)</label><br>
                            <textarea id="observaciones-<?= $documentoId ?>" name="requisitos[<?= $documentoId ?>][observaciones]" maxlength="5000" rows="2" cols="50"><?= htmlspecialchars($requisito['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </p>
                    </fieldset>
                <?php endforeach; ?>
            </fieldset>

            <button type="submit">Guardar trámite <span aria-hidden="true">→</span></button>
        </form>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

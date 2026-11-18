<?php

declare(strict_types=1);

if (!defined('DIRI_ENTRADA')) {
    http_response_code(404);
    exit;
}

$titulo = $id === null ? 'Crear gestión' : 'Editar gestión';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?> | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=2">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
    <link rel="stylesheet" href="publico/administracion.css?v=1">
</head>
<body class="pagina-formulario vista-formulario-gestion">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="index.php?pagina=gestiones">Volver a gestiones</a>
    </header>
    <main id="contenido">
        <?php if ($errores !== []): ?>
            <ul role="alert">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($id === null): ?>
            <form action="index.php" method="get">
                <input type="hidden" name="pagina" value="nueva_gestion">
                <label for="tramite">Selecciona el trámite</label>
                <select name="tramite_id" id="tramite" required>
                    <option value="">Seleccionar</option>
                    <?php foreach ($tramites as $tramite): ?>
                        <option value="<?= (int) $tramite['id'] ?>" <?= $tramiteId === (int) $tramite['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tramite['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Continuar</button>
            </form>
        <?php endif; ?>

        <?php if ($tramiteId !== null): ?>
            <form action="index.php?pagina=guardar_gestion" method="post">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="tramite_id" value="<?= $tramiteId ?>">
                <?php if ($id !== null): ?>
                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                <?php endif; ?>

                <?php foreach ($tramites as $tramite): ?>
                    <?php if ((int) $tramite['id'] === $tramiteId): ?>
                        <p>Trámite <?= htmlspecialchars($tramite['nombre'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>

                <p>
                    <label for="nombre">Nombre de la gestión</label><br>
                    <input id="nombre" name="nombre" required maxlength="200" value="<?= htmlspecialchars($datos['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                </p>
                <p>
                    <label for="descripcion">Descripción</label><br>
                    <textarea id="descripcion" name="descripcion" required maxlength="5000" rows="5" cols="50"><?= htmlspecialchars($datos['descripcion'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </p>
                <p>
                    <label for="orden">Orden de presentación</label><br>
                    <input type="number" id="orden" name="orden" min="1" max="65535" step="1" required value="<?= htmlspecialchars((string) $datos['orden'], ENT_QUOTES, 'UTF-8') ?>">
                </p>
                <p>
                    <label for="documento">Documento que produce esta gestión</label><br>
                    <select id="documento" name="documento_resultado_id">
                        <option value="">Sin documento de resultado</option>
                        <?php foreach ($documentos as $documento): ?>
                            <option value="<?= (int) $documento['id'] ?>" <?= (string) $datos['documento_resultado_id'] === (string) $documento['id'] ? 'selected' : '' ?>><?= htmlspecialchars($documento['nombre'] . ' (' . $documento['estado'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <fieldset>
                    <legend>Gestiones que deben completarse antes</legend>
                    <p>La espera se cuenta en días calendario desde la gestión previa. Usa 0 cuando se puede continuar el mismo día.</p>
                    <?php foreach ($gestionesPrevias as $previa): ?>
                        <?php if ((int) $previa['id'] !== $id): ?>
                            <p>
                                <label>
                                    <input type="checkbox" name="dependencias[]" value="<?= (int) $previa['id'] ?>" <?= in_array((int) $previa['id'], $datos['dependencias'], true) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($previa['nombre'] . ' (' . $previa['estado'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                </label>
                                <label for="espera-<?= (int) $previa['id'] ?>">Días de espera</label>
                                <input type="number" id="espera-<?= (int) $previa['id'] ?>" name="esperas[<?= (int) $previa['id'] ?>]" min="0" max="65535" step="1" value="<?= (int) ($datos['esperas'][(int) $previa['id']] ?? 0) ?>">
                            </p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset>
                    <legend>Establecimientos donde se realiza</legend>
                    <p>La duración de atención se expresa en minutos. Déjala vacía cuando sea desconocida.</p>
                    <?php foreach ($establecimientos as $establecimiento): ?>
                        <?php $establecimientoId = (int) $establecimiento['id']; ?>
                        <p>
                            <label>
                                <input type="checkbox" name="establecimientos[<?= $establecimientoId ?>][seleccionado]" value="1" <?= array_key_exists($establecimientoId, $datos['establecimientos']) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($establecimiento['nombre'] . ' (' . $establecimiento['estado'] . ')', ENT_QUOTES, 'UTF-8') ?>
                            </label><br>
                            <label for="duracion-<?= $establecimientoId ?>">Duración estimada de atención</label>
                            <input type="number" id="duracion-<?= $establecimientoId ?>" name="establecimientos[<?= $establecimientoId ?>][duracion]" min="1" max="65535" step="1" value="<?= htmlspecialchars((string) ($datos['establecimientos'][$establecimientoId] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </p>
                    <?php endforeach; ?>
                </fieldset>

                <p>
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" required>
                        <option value="activo" <?= $datos['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= $datos['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </p>
                <button type="submit">Guardar gestión</button>
            </form>
        <?php else: ?>
            <p>Selecciona un trámite para configurar sus gestiones.</p>
            <a href="index.php?pagina=nuevo_tramite">Crear trámite</a>
        <?php endif; ?>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

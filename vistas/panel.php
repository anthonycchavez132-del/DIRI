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
    <title>Mi cuenta | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=2">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
    <link rel="stylesheet" href="publico/administracion.css?v=1">
</head>
<body class="pagina-panel vista-panel">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1>Mi cuenta</h1>
    </header>

    <main id="contenido">
        <span class="etiqueta"><?= $usuario['rol'] === 'administrador' ? 'Administración de DIRI' : 'Tu espacio en DIRI' ?></span>
        <h2>Hola, <?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>.</h2>
        <p class="introduccion-pagina"><?= $usuario['rol'] === 'administrador' ? 'Mantén los trámites, documentos y lugares de atención al día.' : 'Organiza tu próxima visita y prepara los documentos que necesitas.' ?></p>

        <nav class="opciones-cuenta" aria-label="Opciones de la cuenta">
            <a href="index.php?pagina=tramites">Consultar trámites</a>
            <a href="index.php?pagina=preparar_recorrido">Preparar recorrido</a>
            <?php if ($usuario['rol'] === 'administrador'): ?>
                <a href="index.php?pagina=usuarios">Administrar usuarios</a>
                <a href="index.php?pagina=nuevo_tramite">Crear trámite</a>
                <a href="index.php?pagina=documentos">Administrar documentos</a>
                <a href="index.php?pagina=establecimientos">Administrar establecimientos</a>
                <a href="index.php?pagina=gestiones">Administrar gestiones</a>
                <a href="index.php?pagina=horarios">Administrar horarios</a>
            <?php endif; ?>
        </nav>

        <dl>
            <dt>Nombre o nombres</dt>
            <dd><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Apellido paterno</dt>
            <dd><?= htmlspecialchars($usuario['apellido_paterno'] ?? 'Sin registrar', ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Apellido materno</dt>
            <dd><?= htmlspecialchars($usuario['apellido_materno'] ?? 'Sin registrar', ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Correo electrónico</dt>
            <dd><?= htmlspecialchars($usuario['correo'], ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Rol</dt>
            <dd><?= htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8') ?></dd>
        </dl>

        <form action="index.php?pagina=salir" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Cerrar sesión</button>
        </form>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

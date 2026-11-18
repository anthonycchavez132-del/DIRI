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
    <title>Iniciar sesión | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=2">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
</head>
<body class="pagina-acceso vista-login">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1>Iniciar sesión</h1>
    </header>

    <main id="contenido">
        <p class="introduccion-pagina">Qué bueno verte de nuevo. Ingresa a tu cuenta de DIRI.</p>
        <?php if ($mensaje !== ''): ?>
            <p role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($errores !== []): ?>
            <ul role="alert">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="index.php?pagina=login" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">

            <p>
                <label for="correo">Correo electrónico</label><br>
                <input type="email" id="correo" name="correo" required maxlength="254" autocomplete="username" value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="contrasena">Contraseña</label><br>
                <input type="password" id="contrasena" name="contrasena" required maxlength="72" autocomplete="current-password">
            </p>

            <button type="submit">Ingresar</button>
        </form>

        <p>¿Es tu primera vez? <a href="index.php?pagina=registro">Crea tu cuenta</a></p>
        <p><a href="index.php?pagina=tramites">Continuar explorando trámites</a></p>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

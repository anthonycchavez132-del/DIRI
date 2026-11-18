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
    <title>Cambiar contraseña | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=3">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
</head>
<body class="pagina-acceso vista-registro">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1>Cambiar contraseña</h1>
    </header>

    <main id="contenido">
        <p class="introduccion-pagina">Escribe tu contraseña actual y elige una nueva.</p>
        <?php if ($errores !== []): ?>
            <ul role="alert">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="index.php?pagina=cambiar_contrasena" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">

            <p>
                <label for="contrasena_actual">Contraseña actual</label><br>
                <input type="password" id="contrasena_actual" name="contrasena_actual" required maxlength="72" autocomplete="current-password">
            </p>

            <p>
                <label for="contrasena_nueva">Contraseña nueva</label><br>
                <input type="password" id="contrasena_nueva" name="contrasena_nueva" required minlength="8" maxlength="72" autocomplete="new-password" aria-describedby="requisito-contrasena">
                <small id="requisito-contrasena">Usa al menos 8 caracteres, con una letra y un número.</small>
            </p>

            <p>
                <label for="confirmacion">Repite la contraseña nueva</label><br>
                <input type="password" id="confirmacion" name="confirmacion" required minlength="8" maxlength="72" autocomplete="new-password">
            </p>

            <button type="submit">Guardar contraseña</button>
        </form>

        <p><a href="index.php?pagina=panel">Volver a mi cuenta</a></p>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

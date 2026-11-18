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
    <title>Crear cuenta | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=3">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
</head>
<body class="pagina-acceso vista-registro">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1>Crear cuenta</h1>
    </header>

    <main id="contenido">
        <nav class="navegacion-pantalla" aria-label="Navegación de pantalla">
            <a class="boton secundario" href="index.php?pagina=login"><span aria-hidden="true">←</span> Volver a iniciar sesión</a>
            <a class="boton secundario" href="index.php">Inicio</a>
        </nav>
        <p class="introduccion-pagina">Crea tu cuenta y comienza a organizar tus trámites con DIRI.</p>
        <?php if ($errores !== []): ?>
            <ul role="alert">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="index.php?pagina=registro" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">

            <p>
                <label for="nombre">Nombre o nombres</label><br>
                <input type="text" id="nombre" name="nombre" required maxlength="150" autocomplete="given-name" value="<?= htmlspecialchars($datos['nombre'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="apellido_paterno">Apellido paterno</label><br>
                <input type="text" id="apellido_paterno" name="apellido_paterno" required maxlength="100" value="<?= htmlspecialchars($datos['apellido_paterno'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="apellido_materno">Apellido materno (opcional)</label><br>
                <input type="text" id="apellido_materno" name="apellido_materno" maxlength="100" value="<?= htmlspecialchars($datos['apellido_materno'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="correo">Correo electrónico</label><br>
                <input type="email" id="correo" name="correo" required maxlength="254" autocomplete="email" value="<?= htmlspecialchars($datos['correo'], ENT_QUOTES, 'UTF-8') ?>">
            </p>

            <p>
                <label for="contrasena">Contraseña</label><br>
                <input type="password" id="contrasena" name="contrasena" required minlength="8" maxlength="72" autocomplete="new-password" aria-describedby="requisito-contrasena">
                <small id="requisito-contrasena">Usa al menos 8 caracteres, con una letra y un número.</small>
            </p>

            <p>
                <label for="confirmacion">Repite la contraseña</label><br>
                <input type="password" id="confirmacion" name="confirmacion" required minlength="8" maxlength="72" autocomplete="new-password">
            </p>

            <button type="submit">Crear cuenta <span aria-hidden="true">→</span></button>
        </form>

        <p><a href="index.php?pagina=login">Ya tengo una cuenta</a></p>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

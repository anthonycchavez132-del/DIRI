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
    <title>Usuarios | DIRI</title>
    <link rel="stylesheet" href="publico/estilos.css?v=2">
    <link rel="stylesheet" href="publico/formularios.css?v=1">
    <link rel="stylesheet" href="publico/administracion.css?v=2">
</head>
<body class="pagina-administracion vista-usuarios">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <h1>Administrar usuarios</h1>
        <a href="index.php?pagina=panel">Mi cuenta</a>
    </header>
    <main id="contenido">
        <p class="introduccion-pagina">La eliminación desactiva la cuenta y conserva su historial. Un usuario inactivo no puede iniciar sesión ni utilizar una sesión anterior para acceder a su cuenta.</p>
        <?php if ($mensaje !== ''): ?>
            <p role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($seleccionado !== null): ?>
            <section class="confirmacion-usuario" aria-labelledby="titulo-confirmacion">
                <h2 id="titulo-confirmacion">Confirmar eliminación de usuario</h2>
                <p>Vas a desactivar a <strong><?= htmlspecialchars(trim($seleccionado['nombre'] . ' ' . $seleccionado['apellido_paterno'] . ' ' . ($seleccionado['apellido_materno'] ?? '')), ENT_QUOTES, 'UTF-8') ?></strong>, con correo <strong><?= htmlspecialchars($seleccionado['correo'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>
                <p>Su acceso quedará bloqueado. Sus registros y auditorías se conservarán.</p>
                <form action="index.php?pagina=eliminar_usuario" method="post">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $seleccionado['id'] ?>">
                    <p><label><input type="checkbox" name="confirmar" value="1" required> Confirmo que quiero desactivar esta cuenta.</label></p>
                    <div class="acciones-usuario"><button type="submit">Confirmar eliminación</button><a class="boton secundario" href="index.php?pagina=usuarios">Cancelar</a></div>
                </form>
            </section>
        <?php endif; ?>

        <form action="index.php" method="get" role="search">
            <input type="hidden" name="pagina" value="usuarios">
            <label for="buscar">Buscar por nombre, apellido o correo</label>
            <input type="search" id="buscar" name="buscar" maxlength="200" value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Buscar</button>
            <a href="index.php?pagina=usuarios">Ver todos</a>
        </form>

        <?php if ($usuarios === []): ?>
            <p role="status">No se encontraron usuarios para esta búsqueda.</p>
        <?php else: ?>
            <p>Resultados encontrados <?= count($usuarios) ?></p>
            <div class="tabla-contenedor" role="region" aria-label="Listado de usuarios" tabindex="0">
                <table>
                    <thead><tr><th scope="col">Nombre completo</th><th scope="col">Correo</th><th scope="col">Rol</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($usuarios as $cuenta): ?>
                            <tr>
                                <th scope="row"><?= htmlspecialchars(trim($cuenta['nombre'] . ' ' . $cuenta['apellido_paterno'] . ' ' . ($cuenta['apellido_materno'] ?? '')), ENT_QUOTES, 'UTF-8') ?></th>
                                <td><?= htmlspecialchars($cuenta['correo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $cuenta['rol'] === 'administrador' ? 'Administrador' : 'Usuario' ?></td>
                                <td><?= $cuenta['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></td>
                                <td>
                                    <?php if ((int) $cuenta['id'] === $usuarioActualId): ?>
                                        <span class="insignia">Tu cuenta</span>
                                    <?php elseif ($cuenta['estado'] !== 'activo'): ?>
                                        Cuenta desactivada
                                    <?php elseif ($cuenta['rol'] === 'administrador' && $administradoresActivos <= 1): ?>
                                        Administrador protegido
                                    <?php else: ?>
                                        <a href="index.php?pagina=usuarios&amp;confirmar=<?= (int) $cuenta['id'] ?>">Eliminar usuario</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>

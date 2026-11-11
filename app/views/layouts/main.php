<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DIRI, tu guía para organizar trámites en Bolivia. Plataforma en desarrollo.">
    <title><?= e($title ?? 'DIRI') ?> · DIRI</title>
    <link rel="stylesheet" href="<?= e(url('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/modules.css')) ?>">
    <script src="<?= e(url('js/app.js')) ?>" defer></script>
</head>
<body>
    <?php $authenticatedUser = App\Services\AuthService::user(); ?>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <header class="site-header">
        <div class="container header-content">
            <a class="brand" href="<?= e(url()) ?>" aria-label="DIRI, inicio"><span class="brand-mark" aria-hidden="true">D</span> DIRI</a>
            <nav class="account-nav" aria-label="Cuenta">
                <a href="<?= e(url('buscar')) ?>">Buscar</a>
                <a href="<?= e(url('guias')) ?>">Rutas sencillas · Beta</a>
                <?php if ($authenticatedUser !== null): ?>
                    <?php if ($authenticatedUser['rol'] === 'administrador'): ?>
                        <a href="<?= e(url('admin')) ?>" <?= ($activePage ?? '') === 'admin-tramites' ? 'aria-current="page"' : '' ?>>Administración</a>
                    <?php endif; ?>
                    <a href="<?= e(url('dashboard')) ?>" <?= ($activePage ?? '') === 'dashboard' ? 'aria-current="page"' : '' ?>>Mi espacio</a>
                    <a href="<?= e(url('perfil')) ?>" <?= ($activePage ?? '') === 'perfil' ? 'aria-current="page"' : '' ?>>Mi perfil</a>
                    <a href="<?= e(url('reportar')) ?>">Reportar atención</a>
                    <form action="<?= e(url('logout')) ?>" method="post">
                        <?= csrf_field() ?>
                        <button class="nav-button" type="submit">Cerrar sesión</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('login')) ?>">Iniciar sesión</a>
                    <a class="nav-button" href="<?= e(url('registro')) ?>">Crear cuenta</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main id="contenido">
        <?php require $viewFile; ?>
    </main>
    <footer class="site-footer container">
        <span>DIRI · Tu próximo paso, más claro.</span>
        <span>Hecho para Bolivia · Plataforma en desarrollo</span>
    </footer>
</body>
</html>

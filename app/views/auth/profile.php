<section class="profile-section container">
    <span class="eyebrow">MI CUENTA</span>
    <h1>Hola, <?= e($user['nombre']) ?>.</h1>
    <p class="hero-description">Tu sesión está activa. Desde aquí puedes consultar los datos de tu cuenta.</p>
    <div class="profile-card">
        <h2>Mi perfil</h2>
        <dl class="profile-data">
            <div><dt>Nombre completo</dt><dd><?= e($user['nombre'] . ' ' . $user['apellido']) ?></dd></div>
            <div><dt>Correo electrónico</dt><dd><?= e($user['email']) ?></dd></div>
            <div><dt>Mi plan</dt><dd><?= e(ucfirst($user['plan'])) ?></dd></div>
            <div><dt>Rol</dt><dd><?= e(ucfirst($user['rol'])) ?></dd></div>
        </dl>
        <a class="text-link" href="<?= e(url('dashboard')) ?>">Ir a mi espacio →</a>
        <p>Free: consultas públicas y reportes ciudadanos. Premium: checklist, seguimiento, favoritos, historial, rutas y comparación de sucursales. Los planes los asigna administración; todavía no se realizan cobros.</p>
    </div>
</section>

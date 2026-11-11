<section class="auth-section container">
    <div class="auth-intro">
        <span class="eyebrow">BIENVENIDO A DIRI</span>
        <h1>Tu próximo paso<br>comienza aquí.</h1>
        <p>Accede a tu espacio para consultar tu actividad y los datos de tu cuenta. Seguimos preparando las herramientas para acompañar tus trámites.</p>
        <a class="text-link" href="<?= e(url('registro')) ?>">¿Aún no tienes cuenta? Regístrate →</a>
    </div>
    <div class="auth-card">
        <h2>Iniciar sesión</h2>
        <p class="form-description">Qué bueno tenerte por aquí.</p>
        <?php if (!empty($notice)): ?>
            <div class="form-alert" role="status"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php require BASE_PATH . '/app/views/auth/errors.php'; ?>
        <form action="<?= e(url('login')) ?>" method="post" class="auth-form">
            <?= csrf_field() ?>
            <div class="form-field">
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email" autocomplete="username" maxlength="254" value="<?= e($old['email'] ?? '') ?>" required>
            </div>
            <div class="form-field">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" maxlength="72" required>
            </div>
            <button class="button form-submit" type="submit">Entrar a mi cuenta <span aria-hidden="true">→</span></button>
        </form>
    </div>
</section>

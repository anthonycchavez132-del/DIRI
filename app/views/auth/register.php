<section class="auth-section container">
    <div class="auth-intro">
        <span class="eyebrow">TU CUENTA DIRI</span>
        <h1>Comienza<br>a organizarte.</h1>
        <p>Crea tu cuenta gratuita. Las herramientas para organizar tus trámites se incorporarán en las próximas fases.</p>
        <a class="text-link" href="<?= e(url('login')) ?>">¿Ya tienes cuenta? Inicia sesión →</a>
    </div>
    <div class="auth-card">
        <h2>Crear cuenta</h2>
        <p class="form-description">Completa tus datos para comenzar.</p>
        <?php require BASE_PATH . '/app/views/auth/errors.php'; ?>
        <form action="<?= e(url('registro')) ?>" method="post" class="auth-form">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-field">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" type="text" autocomplete="given-name" maxlength="100" value="<?= e($old['nombre'] ?? '') ?>" required <?= isset($errors['nombre']) ? 'aria-invalid="true"' : '' ?>>
                </div>
                <div class="form-field">
                    <label for="apellido">Apellido</label>
                    <input id="apellido" name="apellido" type="text" autocomplete="family-name" maxlength="100" value="<?= e($old['apellido'] ?? '') ?>" required <?= isset($errors['apellido']) ? 'aria-invalid="true"' : '' ?>>
                </div>
            </div>
            <div class="form-field">
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="254" value="<?= e($old['email'] ?? '') ?>" required <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
            </div>
            <div class="form-field">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" aria-describedby="password-help" required <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                <span class="field-help" id="password-help">Entre 8 y 72 caracteres. Si incluyes tildes o emojis, el máximo puede ser menor.</span>
            </div>
            <div class="form-field">
                <label for="password_confirmation">Repite tu contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required <?= isset($errors['password_confirmation']) ? 'aria-invalid="true"' : '' ?>>
            </div>
            <button class="button form-submit" type="submit">Crear mi cuenta <span aria-hidden="true">→</span></button>
        </form>
        <p class="small-note">Tu cuenta comienza con el plan Free. No solicitamos pagos.</p>
    </div>
</section>

<?php if ($errors !== []): ?>
    <div class="form-alert form-alert-error" role="alert">
        <strong>Revisa los datos para continuar.</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

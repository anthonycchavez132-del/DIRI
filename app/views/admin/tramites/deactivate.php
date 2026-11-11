<section class="admin-section container">
    <a class="text-link" href="<?= e(url('admin/tramites')) ?>">← Volver a trámites</a>
    <div class="admin-heading"><div><span class="eyebrow">GESTIÓN DE CONTENIDO</span><h1>Desactivar trámite</h1></div></div>
    <div class="admin-form-card">
        <h2><?= e($tramite['nombre']) ?></h2>
        <?php if ($tramite['estado'] === 'inactivo'): ?>
            <p>Este trámite ya está inactivo.</p>
        <?php else: ?>
            <p>Dejará de mostrarse en las listas de los usuarios. Su información, favoritos y seguimientos se conservarán. Puedes editarlo después para cambiar su estado.</p>
            <form action="<?= e(url('admin/tramites/desactivar')) ?>" method="post" class="form-actions">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= e((string) $tramite['id']) ?>"><input type="hidden" name="version" value="<?= e($version) ?>">
                <button type="submit" class="button danger-button">Desactivar trámite</button><a class="text-link" href="<?= e(url('admin/tramites')) ?>">Cancelar</a>
            </form>
        <?php endif; ?>
    </div>
</section>

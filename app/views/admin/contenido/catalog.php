<section class="admin-section container">
    <a class="text-link" href="<?= e(url('admin/tramites')) ?>">← Volver a trámites</a>
    <div class="admin-heading">
        <div><span class="eyebrow">CATÁLOGO COMPARTIDO</span><h1>Documentos reutilizables</h1><p>Define cada documento una vez y asócialo a los trámites que lo necesiten.</p></div>
        <a class="button" href="<?= e(url('admin/documentos/crear')) ?>">Crear documento +</a>
    </div>
    <?php if ($notice !== null): ?><div class="form-alert" role="status"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($documents === []): ?>
        <div class="admin-empty"><h2>Aún no hay documentos</h2><p>Crea un documento cuando dispongas de información para identificarlo. Las condiciones se definen por trámite.</p></div>
    <?php else: ?>
        <div class="content-cards">
            <?php foreach ($documents as $item): ?>
                <article class="content-card">
                    <h2><?= e($item['nombre']) ?></h2>
                    <p class="plain-description"><?= e($item['descripcion'] ?? 'Sin descripción') ?></p>
                    <p class="field-help">Utilizado en <?= e((string) $item['usos']) ?> trámites.</p>
                    <dl class="content-facts">
                        <div><dt>Fuente del documento</dt><dd><?= e($sources[$item['fuente_id']]['nombre'] ?? 'Pendiente de asociar') ?></dd></div>
                        <div><dt>Tipo de fuente</dt><dd><?= e($sources[$item['fuente_id']]['tipo'] ?? 'Sin definir') ?></dd></div>
                        <div><dt>Revisado el</dt><dd><?= e(verification_date($item['fecha_verificacion'])) ?></dd></div>
                    </dl>
                    <a class="text-link" href="<?= e(url('admin/documentos/editar?id=' . $item['id'])) ?>">Editar documento compartido</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

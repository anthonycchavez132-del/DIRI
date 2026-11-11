<?php
use App\Services\TramiteValidator;
use App\Services\ContenidoValidator;
?>
<section class="admin-section container">
    <a class="text-link" href="<?= e(url('admin/tramites/ver?id=' . $tramite['id'])) ?>">← Volver al trámite</a>
    <div class="admin-heading">
        <div><span class="eyebrow">REQUISITOS Y DOCUMENTOS</span><h1><?= e($tramite['nombre']) ?></h1><p>Las modificaciones requieren revisar el trámite antes de volver a publicarlo.</p></div>
        <a class="nav-button" href="<?= e(url('admin/documentos')) ?>">Catálogo de documentos</a>
    </div>
    <?php if ($notice !== null): ?><div class="form-alert" role="status"><?= e($notice) ?></div><?php endif; ?>
    <div class="content-section-heading"><h2>Requisitos</h2><a class="button" href="<?= e(url('admin/requisitos/crear?tramite_id=' . $tramite['id'])) ?>">Agregar requisito +</a></div>
    <?php if ($requirements === []): ?>
        <div class="admin-empty"><h3>Aún no hay requisitos registrados</h3><p>No se presupone ningún requisito. Agrega información respaldada o márcala como pendiente.</p></div>
    <?php else: ?>
        <div class="content-cards">
            <?php foreach ($requirements as $item): ?>
                <article class="content-card">
                    <span class="status-label"><?= e(TramiteValidator::VERIFICATIONS[$item['estado_verificacion']]) ?></span>
                    <h3><?= e($item['nombre']) ?></h3>
                    <p class="plain-description"><?= e($item['descripcion'] ?? 'Sin descripción') ?></p>
                    <dl class="content-facts">
                        <div><dt>Condición</dt><dd><?= $item['obligatorio'] ? 'Obligatorio' : 'Opcional' ?></dd></div>
                        <div><dt>Fuente</dt><dd><?= e($sources[$item['fuente_id']]['nombre'] ?? 'Pendiente de asociar') ?></dd></div>
                        <div><dt>Revisado el</dt><dd><?= e(verification_date($item['fecha_verificacion'])) ?></dd></div>
                    </dl>
                    <?php if ($item['observaciones'] !== null): ?><p class="plain-description"><?= e($item['observaciones']) ?></p><?php endif; ?>
                    <a class="text-link" href="<?= e(url('admin/requisitos/editar?tramite_id=' . $tramite['id'] . '&id=' . $item['id'])) ?>">Editar requisito</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="content-section-heading"><h2>Documentos del trámite</h2><a class="button" href="<?= e(url('admin/tramites/documentos/asociar?tramite_id=' . $tramite['id'])) ?>">Asociar documento +</a></div>
    <?php if ($documents === []): ?>
        <div class="admin-empty"><h3>No hay documentos asociados</h3><p>Selecciona documentos del catálogo y define sus condiciones para este trámite.</p></div>
    <?php else: ?>
        <div class="content-cards">
            <?php foreach ($documents as $item): ?>
                <article class="content-card">
                    <h3><?= e($item['nombre']) ?></h3>
                    <p class="plain-description"><?= e($item['descripcion'] ?? 'Sin descripción') ?></p>
                    <dl class="content-facts">
                        <div><dt>Condición</dt><dd><?= $item['obligatorio'] ? 'Obligatorio' : 'Opcional' ?></dd></div>
                        <div><dt>Cantidad</dt><dd><?= e($item['cantidad'] === null ? 'Sin definir' : (string) $item['cantidad']) ?></dd></div>
                        <div><dt>Presentación</dt><dd><?= e(ContenidoValidator::PRESENTATIONS[$item['presentacion']] ?? 'Sin definir') ?></dd></div>
                        <div><dt>Fuente de condiciones</dt><dd><?= e($sources[$item['fuente_id']]['nombre'] ?? 'Pendiente de asociar') ?></dd></div>
                        <div><dt>Fuente del documento</dt><dd><?= e($sources[$item['documento_fuente_id']]['nombre'] ?? 'Pendiente de asociar') ?></dd></div>
                        <div><dt>Revisión del documento</dt><dd><?= e(verification_date($item['documento_fecha_verificacion'])) ?></dd></div>
                        <div><dt>Revisado el</dt><dd><?= e(verification_date($item['fecha_verificacion'])) ?></dd></div>
                    </dl>
                    <?php if ($item['observaciones'] !== null): ?><p class="plain-description"><?= e($item['observaciones']) ?></p><?php endif; ?>
                    <a class="text-link" href="<?= e(url('admin/tramites/documentos/editar?tramite_id=' . $tramite['id'] . '&documento_id=' . $item['documento_id'])) ?>">Editar condiciones de este trámite</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

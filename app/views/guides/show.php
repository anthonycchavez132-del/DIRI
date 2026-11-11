<?php $sourceIndex = array_column($item['fuentes'], null, 'id'); ?>
<section class="admin-section container">
    <a href="<?= e(url('guias')) ?>">← Rutas sencillas</a>
    <p class="eyebrow">TU RUTA SENCILLA · BETA · GRATIS</p>
    <h1><?= e($item['nombre']) ?></h1>
    <p class="small-note">Estado de la ficha: <?= e(\App\Services\TramiteValidator::VERIFICATIONS[$item['estado_verificacion']]) ?>. Consulta sus límites y fuentes antes de iniciar la gestión.</p>
    <?php if ($guide['kind'] === 'orientacion'): ?><p class="pill">Orientación: elige el trámite que corresponde</p><?php endif; ?>
    <?php if ($guide['kind'] === 'preparacion'): ?><p class="pill">Preparación: revisa las condiciones de tu caso</p><?php endif; ?>
    <p><?= e($guide['intro']) ?></p>
    <p>Guía de organización de DIRI basada en las fuentes de la ficha. Confirma la atención y las condiciones de tu caso antes de acudir.</p>
    <p><a class="button button-secondary" href="<?= e(url('tramite?slug=' . $item['slug'])) ?>">Ver requisitos, documentos y costos</a></p>
    <?php if (in_array($item['slug'], ['licencia-de-conducir', 'renovacion-de-licencia'], true)): ?><section class="dashboard-panel guide-callout"><h2>¿Ya tienes algunos documentos?</h2><p>Márcalos para que DIRI adapte los pendientes y busque opciones de La Paz según tu zona.</p><a class="button" href="<?= e(url('mi-ruta?modalidad=' . ($item['slug'] === 'licencia-de-conducir' ? 'primera' : 'renovacion'))) ?>">Personalizar mi ruta gratis</a></section><?php endif; ?>
    <?php if ($guide['steps'] !== []): ?>
        <div data-simple-guide data-guide-key="<?= e($guide['fingerprint']) ?>">
            <div class="dashboard-panel guide-progress" data-guide-controls hidden>
                <p data-guide-summary role="status" aria-live="polite"></p>
                <progress data-guide-progress max="<?= count($guide['steps']) ?>" value="0" aria-label="Pasos marcados"></progress>
                <p data-guide-storage>Las marcas se conservan en esta pestaña; no se guardan en tu cuenta. Marca solo los pasos terminados o que no correspondan a tu caso.</p>
                <button class="button button-secondary" type="button" data-guide-reset>Reiniciar marcas</button>
            </div>
            <ol class="route-steps guide-steps">
                <?php foreach ($guide['steps'] as $number => $step): ?>
                    <li class="dashboard-panel" data-guide-step>
                        <h2><?= e($step['title']) ?></h2>
                        <p><?= e($step['description']) ?></p>
                        <p><strong>Dónde:</strong> <?= e($step['place']) ?></p>
                        <?php if ($step['result'] ?? null): ?><p><strong>Al terminar:</strong> <?= e($step['result']) ?></p><?php endif; ?>
                        <?php if ($step['previous'] !== []): ?><p><strong>Antes:</strong> <?= e(implode('; ', $step['previous'])) ?></p><?php endif; ?>
                        <?php if ($step['documents'] !== []): ?><p><strong>Lleva:</strong> <?= e(implode('; ', $step['documents'])) ?></p><?php endif; ?>
                        <?php if ($step['note'] !== ''): ?><p class="small-note"><?= e($step['note']) ?></p><?php endif; ?>
                        <ul class="guide-links"><?php foreach ($step['links'] as $link): ?>
                            <?php if (external_url($link['url']) !== null): ?><li><a href="<?= e($link['url']) ?>" rel="noopener noreferrer"><?= e($link['label']) ?></a></li><?php endif; ?>
                        <?php endforeach; ?></ul>
                        <?php if ($step['related'] !== []): ?>
                            <h3>Continúa según tu caso</h3>
                            <ul class="guide-links"><?php foreach ($step['related'] as $target): ?>
                                <li><a href="<?= e(url('guia?slug=' . $target['slug'])) ?>"><?= e($target['nombre']) ?></a></li>
                            <?php endforeach; ?></ul>
                        <?php endif; ?>
                        <?php $evidenceSourceId = $step['source_id']; $evidenceDate = $step['reviewed_on']; require BASE_PATH . '/app/views/catalog/evidence.php'; ?>
                        <label class="guide-check" data-guide-control hidden><input type="checkbox" data-guide-done value="<?= (int)$number ?>"> Listo / no corresponde a mi caso</label>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    <?php else: ?>
        <div class="empty-state"><h2>Estamos preparando esta ruta</h2><p>Por ahora consulta la ficha y sus fuentes para confirmar la secuencia de tu modalidad.</p></div>
    <?php endif; ?>
    <p><a href="<?= e(url('buscar')) ?>">Buscar otro trámite</a></p>
</section>

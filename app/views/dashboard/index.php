<?php
$statusLabels = [
    'en_preparacion' => 'En preparación',
    'en_curso' => 'En curso',
    'completado' => 'Completado',
    'pausado' => 'Pausado',
];
?>
<section class="dashboard-section container">
    <div class="dashboard-heading">
        <div>
            <span class="eyebrow">MI ESPACIO DIRI</span>
            <h1>Hola, <?= e($user['nombre']) ?>.</h1>
            <p>Un lugar para tener tus próximos pasos más claros.</p>
        </div>
        <div class="plan-card"><span>Mi plan</span><strong><?= e(ucfirst($user['plan'])) ?></strong><a href="<?= e(url('perfil')) ?>">Ver mi perfil →</a></div>
    </div>

    <section class="dashboard-search" aria-labelledby="search-title">
        <h2 id="search-title">¿Qué trámite necesitas realizar?</h2>
        <form class="search-preview" method="get" action="<?= e(url('buscar')) ?>">
            <label class="visually-hidden" for="dashboard-search">Buscar trámite</label>
            <input id="dashboard-search" name="q" maxlength="500" type="search" placeholder="¿Qué trámite necesitas realizar?">
            <button class="button" type="submit">Buscar</button>
        </form>
    </section>

    <?php if(!$hasPremium): ?><div class="form-alert"><p>Tu plan Free incluye buscador, requisitos, documentos, instituciones, pasos, costos y estimaciones públicas. La organización personal y las recomendaciones avanzadas requieren Premium.</p></div><?php else: ?><p><a class="button button-secondary" href="<?= e(url('mi-biblioteca')) ?>">Mi biblioteca e historial</a> · <a href="<?= e(url('rutas')) ?>">Explorar rutas</a></p><?php endif; ?>
    <div class="dashboard-stats" aria-label="Resumen de tu actividad">
        <div class="stat-card"><span>Pendientes</span><strong><?= e((string) $dashboard['counts']['pendientes']) ?></strong><p>Trámites que sigues organizando</p></div>
        <div class="stat-card"><span>Favoritos</span><strong><?= e((string) $dashboard['counts']['favoritos']) ?></strong><p>Trámites guardados en tu cuenta</p></div>
        <div class="stat-card"><span>Completados</span><strong><?= e((string) $dashboard['counts']['completados']) ?></strong><p>Trámites marcados como terminados</p></div>
    </div>

    <div class="dashboard-grid">
        <section class="dashboard-panel" aria-labelledby="recent-title">
            <div class="panel-heading"><span class="feature-number">01 / ACTIVIDAD</span><h2 id="recent-title">Trámites recientes</h2><p>Las últimas actualizaciones de tu seguimiento.</p></div>
            <?php if ($dashboard['recent'] === []): ?>
                <div class="empty-state"><span aria-hidden="true">↗</span><h3>Tu próximo paso empieza aquí</h3><p>Aquí verás los trámites que empieces a organizar.</p></div>
            <?php else: ?>
                <ul class="dashboard-list">
                    <?php foreach ($dashboard['recent'] as $item): ?>
                        <li><strong><a href="<?= e(url('mi-checklist?slug='.$item['slug'])) ?>"><?= e($item['nombre']) ?></a></strong><span class="list-context"><?= e($item['categoria']) ?></span><span class="status-label status-<?= e($item['estado']) ?>"><?= e($statusLabels[$item['estado']] ?? 'Sin estado') ?></span><span class="list-date">Actualizado: <?= e(local_datetime($item['fecha_actualizacion'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <p class="panel-note">Hasta 5 actualizaciones más recientes.</p>
            <?php endif; ?>
        </section>

        <section class="dashboard-panel" aria-labelledby="favorites-title">
            <div class="panel-heading"><span class="feature-number">02 / GUARDADOS</span><h2 id="favorites-title">Trámites favoritos</h2><p>Un lugar para tus consultas importantes.</p></div>
            <?php if ($dashboard['favorites'] === []): ?>
                <div class="empty-state"><span aria-hidden="true">☆</span><h3>Aún no tienes favoritos</h3><p>Los trámites que guardes aparecerán aquí.</p></div>
            <?php else: ?>
                <ul class="dashboard-list">
                    <?php foreach ($dashboard['favorites'] as $item): ?>
                        <li><strong><a href="<?= e(url('mi-checklist?slug='.$item['slug'])) ?>"><?= e($item['nombre']) ?></a></strong><span class="list-context"><?= e($item['categoria']) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <p class="panel-note">Hasta 5 favoritos más recientes.</p>
            <?php endif; ?>
        </section>

        <section class="dashboard-panel" aria-labelledby="progress-title">
            <div class="panel-heading"><span class="feature-number">03 / SEGUIMIENTO</span><h2 id="progress-title">Mi progreso</h2><p>Lo que aún tienes por completar.</p></div>
            <?php if ($dashboard['progress'] === []): ?>
                <div class="empty-state"><span aria-hidden="true">✓</span><h3>No tienes trámites pendientes</h3><p>Cuando tengas un trámite en preparación, podrás ver su estado aquí.</p></div>
            <?php else: ?>
                <ul class="dashboard-list">
                    <?php foreach ($dashboard['progress'] as $item): ?>
                        <li><strong><a href="<?= e(url('mi-checklist?slug='.$item['slug'])) ?>"><?= e($item['nombre']) ?></a></strong><span class="status-label status-<?= e($item['estado']) ?>"><?= e($statusLabels[$item['estado']] ?? 'Sin estado') ?></span><span class="list-date">Actualizado: <?= e(local_datetime($item['fecha_actualizacion'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <p class="panel-note">Hasta 5 trámites pendientes más recientes.</p>
            <?php endif; ?>
        </section>
    </div>
    <p class="dashboard-footnote">Abre un trámite publicado para consultar su información y organizarlo con tu plan Premium. Los recordatorios automáticos se implementarán más adelante.</p>
</section>

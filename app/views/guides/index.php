<section class="admin-section container">
    <span class="eyebrow">RUTAS SENCILLAS · BETA</span>
    <h1>¿Por dónde empiezo?</h1>
    <p>Organiza tus gestiones con pasos claros. Disponible gratis, sin crear una cuenta.</p>
    <p><strong><?= count(array_filter($rows, static fn($row) => $row['count'] > 0)) ?> rutas disponibles</strong>. Algunas te guían en la preparación; otras te ayudan a elegir el trámite específico.</p>
    <div class="content-cards">
        <?php foreach ($rows as $row): ?><?php if ($row['count'] === 0) { continue; } ?>
            <article class="content-card">
                <span class="eyebrow"><?= e($row['categoria']) ?></span>
                <h2><a href="<?= e(url('guia?slug=' . $row['slug'])) ?>"><?= e($row['nombre']) ?></a></h2>
                <?php if ($row['kind'] === 'orientacion'): ?><span class="pill">Elige tu trámite</span><?php endif; ?>
                <?php if ($row['kind'] === 'preparacion'): ?><span class="pill">Prepara tu solicitud</span><?php endif; ?>
                <p><?= (int)$row['count'] ?> pasos para organizarte.</p>
                <a class="button button-secondary" href="<?= e(url('guia?slug=' . $row['slug'])) ?>">Ver ruta sencilla</a>
                <?php if (in_array($row['slug'], ['licencia-de-conducir', 'renovacion-de-licencia'], true)): ?><p><a href="<?= e(url('mi-ruta?modalidad=' . ($row['slug'] === 'licencia-de-conducir' ? 'primera' : 'renovacion'))) ?>">Ya tengo documentos: personalizar gratis</a></p><?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (array_filter($rows, static fn($row) => $row['count'] === 0) !== []): ?>
    <h2>Fichas cuya ruta seguimos preparando</h2>
    <p>Puedes consultar sus requisitos y fuentes mientras revisamos la secuencia.</p>
    <ul><?php foreach ($rows as $row): ?><?php if ($row['count'] !== 0) { continue; } ?>
        <li><a href="<?= e(url('tramite?slug=' . $row['slug'])) ?>"><?= e($row['nombre']) ?></a></li>
    <?php endforeach; ?></ul>
    <?php endif; ?>
</section>

<section class="admin-section container">
    <span class="eyebrow">ADMINISTRACIÓN DIRI</span><h1>Contenido y operaciones</h1>
    <div class="admin-module-search" hidden data-module-filter><label for="module-search">Encuentra una herramienta</label><input type="search" id="module-search" placeholder="Por ejemplo: costos o usuarios" data-module-query><span role="status" aria-live="polite" data-module-count></span></div>
    <div class="content-cards" data-module-list>
        <article class="content-card"><h2>Reportes ciudadanos</h2><p>Revisa observaciones antes de usarlas en estimaciones.</p><a href="<?= e(url('admin/reportes')) ?>">Revisar reportes</a></article>
        <article class="content-card"><h2>Atención actual</h2><a href="<?= e(url('admin/atencion')) ?>">Registrar observación administrativa</a></article>
        <article class="content-card"><h2>Auditoría</h2><a href="<?= e(url('admin/auditoria')) ?>">Consultar cambios</a></article>
        <article class="content-card"><h2>Trámites</h2><p>Información general, requisitos y documentos.</p><a href="<?= e(url('admin/tramites')) ?>">Administrar trámites</a></article>
        <article class="content-card"><h2>Documentos reutilizables</h2><a href="<?= e(url('admin/documentos')) ?>">Abrir catálogo</a></article>
        <?php foreach($resources as $name=>$definition): ?><article class="content-card"><h2><?= e($definition['title']) ?></h2><a href="<?= e(url('admin/gestion?recurso='.$name)) ?>">Abrir gestión</a></article><?php endforeach; ?>
    </div>
</section>

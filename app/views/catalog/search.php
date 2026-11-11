<section class="admin-section container">
    <span class="eyebrow">TU PRÓXIMO PASO</span><h1>Encuentra tu trámite</h1>
    <form method="get" action="<?= e(url('buscar')) ?>" class="search-preview">
        <label class="visually-hidden" for="query">¿Qué trámite necesitas realizar?</label>
        <input id="query" name="q" type="search" maxlength="500" value="<?= e($query) ?>" placeholder="Quiero sacar mi carnet">
        <button class="button" type="submit">Buscar</button>
    </form>
    <?php if($error!==null): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
    <p><?= e((string)$results['total']) ?> trámites encontrados. Solo mostramos contenido publicado.</p>
    <p><a href="<?= e(url('guias')) ?>">¿Por dónde empiezo? Explora las rutas sencillas · Beta, gratis.</a></p>
    <div class="content-cards">
    <?php foreach($results['items'] as $item): ?>
        <article class="content-card"><span class="eyebrow"><?= e($item['categoria']) ?></span><h2><a href="<?= e(url('tramite?slug='.$item['slug'])) ?>"><?= e($item['nombre']) ?></a></h2><p><?= e(mb_strimwidth((string)$item['descripcion'],0,240,'…')) ?></p><span class="pill"><?= e(\App\Services\TramiteValidator::VERIFICATIONS[$item['estado_verificacion']]) ?></span><p><a href="<?= e(url('guia?slug='.$item['slug'])) ?>">Ver ruta sencilla · Beta</a></p></article>
    <?php endforeach; ?>
    </div>
    <?php if($results['items']===[]): ?><div class="empty-state"><h2>No hay coincidencias publicadas</h2><p>Prueba otro nombre o una palabra relacionada. Solo se muestran las fichas y guías publicadas.</p></div><?php endif; ?>
    <nav class="pagination" aria-label="Resultados">
        <?php if($results['page']>1): ?><a href="<?= e(url('buscar?'.http_build_query(['q'=>$query,'pagina'=>$results['page']-1]))) ?>">Anterior</a><?php endif; ?>
        <?php if($results['page']*12<$results['total']): ?><a href="<?= e(url('buscar?'.http_build_query(['q'=>$query,'pagina'=>$results['page']+1]))) ?>">Siguiente</a><?php endif; ?>
    </nav>
</section>

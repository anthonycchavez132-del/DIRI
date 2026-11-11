<section class="hero container">
    <div class="hero-copy">
        <span class="eyebrow"><span class="status-dot" aria-hidden="true"></span> Menos vueltas. Más claridad.</span>
        <h1>Tus trámites,<br><span>paso a paso.</span></h1>
        <p class="hero-subtitle">Tu guía para realizar trámites en Bolivia</p>
        <p class="hero-description">Encuentra qué necesitas, dónde ir y cómo organizar tu próximo trámite.</p>
        <form method="get" action="<?= e(url('buscar')) ?>" class="search-preview">
            <label class="visually-hidden" for="home-query">¿Qué trámite necesitas realizar?</label>
            <input id="home-query" name="q" type="search" maxlength="500" placeholder="¿Qué trámite necesitas realizar?">
            <button class="button" type="submit">Buscar</button>
        </form>
        <p class="small-note">Prueba: «Quiero sacar mi carnet» o «Quiero abrir un negocio».</p>
    </div>
    <aside class="preview" aria-label="Objetivo de DIRI">
        <div class="preview-top"><span class="preview-label">UN CAMINO MÁS CLARO</span><span class="preview-symbol" aria-hidden="true">↗</span></div>
        <h2>Todo comienza con<br>un primer paso.</h2>
        <p>Así queremos ayudarte a organizarte:</p>
        <ol class="journey">
            <li><span class="step-number">01</span><div><strong>Encuentra tu trámite</strong><span>Expresa lo que necesitas hacer.</span></div></li>
            <li><span class="step-number">02</span><div><strong>Prepara lo necesario</strong><span>Consulta documentos y requisitos.</span></div></li>
            <li><span class="step-number">03</span><div><strong>Organiza tu recorrido</strong><span>Conoce los pasos y dónde acudir.</span></div></li>
        </ol>
        <div class="preview-note">Información con fuentes y estado de verificación</div>
    </aside>
</section>
<section class="project-section" id="proyecto">
    <div class="container">
        <div class="section-heading"><div><span class="eyebrow">CATÁLOGO</span><h2>Trámites disponibles</h2></div><p>Consulta las fuentes y la fecha de revisión antes de preparar tu visita.</p></div>
        <div class="content-cards">
        <?php foreach(array_slice($catalog['items'],0,6) as $entry): ?><article class="content-card"><span class="eyebrow"><?= e($entry['categoria']) ?></span><h3><a href="<?= e(url('tramite?slug='.$entry['slug'])) ?>"><?= e($entry['nombre']) ?></a></h3><p><?= e(\App\Services\TramiteValidator::VERIFICATIONS[$entry['estado_verificacion']]) ?></p></article><?php endforeach; ?>
        </div>
        <?php if($catalog['items']===[]): ?><p>El catálogo inicial está pendiente de revisión. Los trámites aparecerán cuando se publiquen con sus fuentes.</p><?php endif; ?>
        <div class="feature-grid">
            <article class="feature-card"><span class="feature-number">01 / ENCUENTRA</span><h3>Expresa lo que necesitas</h3><p>Busca por nombre, tema o palabras habituales.</p></article>
            <article class="feature-card"><span class="feature-number">02 / PREPARA</span><h3>Datos con contexto</h3><p>Consulta documentos, costos y fuentes de cada trámite.</p></article>
            <article class="feature-card"><span class="feature-number">03 / ORGANIZA</span><h3>Tu siguiente paso</h3><p>Revisa el orden de los pasos y las instituciones donde acudir.</p></article>
        </div>
    </div>
</section>

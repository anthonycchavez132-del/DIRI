<?php
use App\Services\LicenseJourneyService;
use App\Services\LicenseProviderService;
$sourceIndex = array_column($context['item']['fuentes'], null, 'id');
?>
<section class="admin-section container">
    <a href="<?= e(url('guias')) ?>">← Rutas sencillas</a>
    <p class="eyebrow">TU RUTA PERSONALIZADA · BETA · GRATIS</p>
    <h1>¿Qué te falta para tu licencia?</h1>
    <p>Indica qué documentos vigentes ya tienes. DIRI ajustará la ruta para evitar repetir su obtención y te mostrará el siguiente paso.</p>
    <nav class="form-actions journey-form" aria-label="Modalidad de licencia">
        <a class="button button-secondary" href="<?= e(url('mi-ruta?modalidad=primera')) ?>" <?= $mode === 'primera' ? 'aria-current="page"' : '' ?>>Primera licencia</a>
        <a class="button button-secondary" href="<?= e(url('mi-ruta?modalidad=renovacion')) ?>" <?= $mode === 'renovacion' ? 'aria-current="page"' : '' ?>>Renovación</a>
    </nav>
    <h2><?= $mode === 'primera' ? 'Primera licencia · adultos' : 'Renovar mi licencia actual' ?></h2>
    <p class="small-note">Tus elecciones se conservan en esta sesión del navegador, separadas por modalidad. No necesitas cuenta ni subir documentos. Las marcas son tu declaración; SEGIP verifica su validez al tramitar.</p>
    <?php if ($notice !== null): ?><p role="status"><?= e($notice) ?></p><?php endif; ?>
    <?php if ($errors !== []): ?><div class="form-alert form-alert-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form class="dashboard-panel auth-form journey-form" method="post" action="<?= e(url('mi-ruta')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="modalidad" value="<?= e($mode) ?>">
        <input type="hidden" name="version" value="<?= e($context['version']) ?>">
        <div class="form-row">
            <div class="form-field"><label for="journey-category">Categoría <?= $mode === 'renovacion' ? 'actual que renovarás (no ascenso)' : 'que solicitarás' ?></label>
                <select id="journey-category" name="categoria"><?php foreach ($mode === 'primera' ? ['P', 'A', 'M', 'T'] : LicenseJourneyService::CATEGORIES as $category): ?><option value="<?= e($category) ?>" <?= $profile['categoria'] === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select>
            </div>
            <?php if ($mode === 'primera'): ?><div class="form-field"><label for="journey-age">Tu rango de edad</label><select id="journey-age" name="edad">
                <?php foreach (['no_se' => 'Selecciona tu rango', 'menor18' => 'Menos de 18 años', '18a20' => '18 a 20 años', '21mas' => '21 años o más'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $profile['edad'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select></div><?php endif; ?>
            <div class="form-field"><label for="journey-zone">Zona donde prefieres realizar la siguiente gestión</label><select id="journey-zone" name="zona"><?php foreach (LicenseProviderService::ZONES as $value => $label): ?><option value="<?= e($value) ?>" <?= $profile['zona'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><small>Opciones del municipio de La Paz; no se calcula tu ubicación.</small></div>
        </div>
        <?php if ($mode === 'renovacion'): ?><div class="form-field"><label for="journey-long">¿Se cumple que no renovaste en cinco años y un día?</label><select id="journey-long" name="renovacion_larga"><?php foreach (['no_se' => 'No estoy seguro', 'si' => 'Sí', 'no' => 'No'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $profile['renovacion_larga'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><small>Esta condición determina si se incluye el certificado de habilidades.</small></div><?php endif; ?>
        <fieldset class="journey-documents"><legend>Marca los documentos que ya tienes vigentes</legend>
            <p>Si un documento está vencido o no sabes si sirve para esta solicitud, déjalo sin marcar. Si conoces su vencimiento, puedes indicarlo; no calculamos una vigencia automática.</p>
            <p><strong>Antecedentes:</strong> <?= $mode === 'primera' ? 'Tránsito, FELCC y FELCN para primera licencia.' : 'Tránsito para renovación.' ?> REJAP no los sustituye.</p>
            <?php foreach (LicenseJourneyService::DOCUMENTS as $key => $label): ?>
                <div class="journey-document">
                    <label class="guide-check"><input type="checkbox" name="tengo[<?= e($key) ?>]" value="1" <?= isset($profile['tengo'][$key]) ? 'checked' : '' ?>> <?= e($label) ?></label>
                    <?php if ($key !== 'pago'): ?><div class="form-field"><label for="expires-<?= e($key) ?>">Vencimiento indicado en <?= e($label) ?> (opcional)</label><input id="expires-<?= e($key) ?>" type="date" name="vencimientos[<?= e($key) ?>]" value="<?= e($profile['vencimientos'][$key] ?? '') ?>"></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </fieldset>
        <div class="form-field"><label for="journey-registered">Si ya tienes certificado médico, ¿está registrado en SEGIP?</label><select id="journey-registered" name="medico_registro"><?php foreach (['no_se' => 'No lo sé / todavía no tengo el certificado', 'si' => 'Sí, confirmé que está registrado', 'pendiente' => 'El registro está pendiente'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $profile['medico_registro'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        <button class="button form-submit" type="submit"><?= $plan === null ? 'Crear mi ruta personalizada' : 'Actualizar mi ruta' ?></button>
    </form>
    <?php if ($plan !== null): ?>
        <section id="tu-ruta" class="journey-result" aria-labelledby="journey-result-title">
            <h2 id="journey-result-title">Tu ruta para categoría <?= e($profile['categoria']) ?></h2>
            <?php foreach ($plan['warnings'] as $warning): ?><p class="form-alert form-alert-error" role="status"><?= e($warning) ?></p><?php endforeach; ?>
            <?php if ($plan['blocked'] !== null): ?><div class="dashboard-panel"><h3>Revisa tu modalidad</h3><p><?= e($plan['blocked']) ?></p><a href="https://www.segip.gob.bo/licencia-para-conducir/" rel="noopener noreferrer">Consultar requisitos de SEGIP</a></div><?php else: ?>
                <section class="dashboard-panel guide-callout" data-next-step="<?= e($plan['next']['key']) ?>"><span class="eyebrow">TU SIGUIENTE PASO</span><h3><?= e($plan['next']['title']) ?></h3><p><?= e($plan['next']['description']) ?></p>
                    <?php if ($plan['ready']): ?><p><strong>Según tus respuestas, tienes preparada la documentación indicada. SEGIP revisará y resolverá la solicitud.</strong></p><?php endif; ?>
                </section>
                <?php if ($plan['covered'] !== []): ?><section class="dashboard-panel"><h3>Ya lo tienes: no repetiremos su obtención</h3><ul><?php foreach ($plan['covered'] as $label): ?><li><?= e($label) ?></li><?php endforeach; ?></ul><p>Conserva y presenta estos documentos cuando corresponda.</p></section><?php endif; ?>
                <?php foreach ($plan['optional'] as $message): ?><p><?= e($message) ?></p><?php endforeach; ?>
                <?php if ($plan['providers'] !== []): ?>
                    <?php $matches = array_filter($plan['providers'], static fn($provider) => $provider['matches_zone']); ?>
                    <section class="dashboard-panel"><h3>Opciones para tu siguiente gestión</h3>
                        <p><?= $profile['zona'] === 'todas' ? 'Opciones de La Paz, en orden alfabético.' : (count($matches) > 0 ? 'Primero aparecen las coincidencias con la zona que elegiste; los empates se ordenan alfabéticamente.' : 'No hay coincidencias con tu zona en este directorio. Puedes revisar otras opciones de La Paz o cambiar la zona.') ?></p>
                        <p class="small-note">Son opciones del directorio oficial consultado el 21/10/2026. Confirma atención, habilitación para tu categoría y costo antes de acudir. No hay datos para compararlas por espera, precio o distancia de traslado.</p>
                        <div class="content-cards"><?php foreach ($plan['providers'] as $provider): ?><article class="content-card">
                            <?php if ($provider['matches_zone']): ?><span class="pill">Coincide con tu zona</span><?php endif; ?>
                            <h4><?= e($provider['name']) ?></h4><p><?= e($provider['address']) ?></p><p><?= e(LicenseProviderService::ZONES[$provider['zone']]) ?> · La Paz</p>
                            <a href="<?= e($provider['source_url']) ?>" rel="noopener noreferrer">Ver directorio oficial de SEGIP</a>
                        </article><?php endforeach; ?></div>
                    </section>
                <?php endif; ?>
                <h3>Gestiones que quedan en tu ruta</h3><ol class="route-steps"><?php foreach ($plan['actions'] as $action): ?><li class="dashboard-panel" data-pending-step="<?= e($action['key']) ?>"><h4><?= e($action['title']) ?></h4><p><?= e($action['description']) ?></p><p><strong>Dónde:</strong> <?= e($action['place']) ?></p><?php foreach ($action['links'] as $link): ?><p><a href="<?= e($link['url']) ?>" rel="noopener noreferrer"><?= e($link['label']) ?></a></p><?php endforeach; ?></li><?php endforeach; ?></ol>
                <p>El orden de preparación es sugerido. Al conseguir un documento, márcalo arriba y actualiza la ruta. La visita final a SEGIP se mantiene pendiente hasta que presentes tu solicitud.</p>
            <?php endif; ?>
        </section>
        <form class="journey-form" method="post" action="<?= e(url('mi-ruta/reiniciar')) ?>"><?= csrf_field() ?><input type="hidden" name="modalidad" value="<?= e($mode) ?>"><button class="button button-secondary" type="submit">Borrar mis elecciones de esta modalidad</button></form>
    <?php endif; ?>
    <p><a href="<?= e(url('tramite?slug=' . $context['item']['slug'])) ?>">Revisar todos los requisitos, tarifas y fuentes de esta modalidad</a></p>
    <?php $evidenceSourceId = $context['item']['fuente_id']; $evidenceDate = $context['item']['fecha_verificacion']; require BASE_PATH . '/app/views/catalog/evidence.php'; ?>
</section>

<?php
use App\Services\ContenidoValidator;
use App\Services\TramiteValidator;
$value = static fn (string $key): string => e((string) ($formData[$key] ?? ''));
$editing = $context['id'] !== null;
$prefix = match ($kind) { 'requisito' => 'admin/requisitos/', 'documento' => 'admin/documentos/', 'asociacion' => 'admin/tramites/documentos/' };
$action = $prefix . ($editing ? 'editar' : ($kind === 'asociacion' ? 'asociar' : 'crear'));
?>
<section class="admin-section container">
    <a class="text-link" href="<?= e(url($backPath)) ?>">← Volver</a>
    <div class="admin-heading"><div><span class="eyebrow">GESTIÓN DE CONTENIDO</span><h1><?= e($title) ?></h1>
        <?php if ($context['parent'] !== null): ?><p>Trámite: <?= e($context['parent']['nombre']) ?></p><?php endif; ?>
    </div></div>
    <?php if ($kind === 'documento' && $usageCount > 0): ?>
        <div class="form-alert" role="note">Este documento se comparte en <?= e((string) $usageCount) ?> trámites. Cambiarlo actualizará su información compartida y dejará esos trámites pendientes de revisión. Sus cantidades y condiciones particulares se conservarán.</div>
    <?php elseif ($kind !== 'documento'): ?>
        <p class="form-description">Guardar cambios dejará este trámite pendiente de revisión. Si estaba publicado, volverá a borrador.</p>
    <?php endif; ?>
    <div class="admin-form-card">
        <?php require BASE_PATH . '/app/views/auth/errors.php'; ?>
        <form method="post" action="<?= e(url($action)) ?>" class="auth-form">
            <?= csrf_field() ?>
            <?php if ($context['parent'] !== null): ?><input type="hidden" name="tramite_id" value="<?= e((string) $context['parent']['id']) ?>"><?php endif; ?>
            <?php if ($editing): ?>
                <input type="hidden" name="version" value="<?= e($version) ?>">
                <?php if ($kind !== 'asociacion'): ?><input type="hidden" name="id" value="<?= e((string) $context['id']) ?>"><?php endif; ?>
            <?php endif; ?>

            <?php if ($kind === 'asociacion'): ?>
                <div class="form-field">
                    <label for="documento_id">Documento del catálogo</label>
                    <?php if ($editing): ?>
                        <input type="hidden" name="documento_id" value="<?= e((string) $context['id']) ?>">
                        <p><?php foreach ($documents as $document): ?><?php if ((int) $document['id'] === $context['id']): ?><?= e($document['nombre']) ?><?php endif; ?><?php endforeach; ?></p>
                    <?php else: ?>
                        <select id="documento_id" name="documento_id" required>
                            <option value="">Selecciona un documento</option>
                            <?php foreach ($documents as $document): ?>
                                <option value="<?= e((string) $document['id']) ?>" <?= (string) ($formData['documento_id'] ?? '') === (string) $document['id'] ? 'selected' : '' ?>><?= e($document['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a class="text-link" href="<?= e(url('admin/documentos/crear')) ?>">Crear un documento en el catálogo</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="form-field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="200" required value="<?= $value('nombre') ?>"></div>
                <div class="form-field"><label for="descripcion">Descripción (opcional)</label><textarea id="descripcion" name="descripcion" rows="5" maxlength="5000"><?= $value('descripcion') ?></textarea></div>
            <?php endif; ?>

            <?php if ($kind !== 'documento'): ?>
                <div class="form-field">
                    <label for="obligatorio">¿Es obligatorio en este trámite?</label>
                    <select id="obligatorio" name="obligatorio" required>
                        <option value="">Selecciona una condición</option>
                        <option value="1" <?= (string) ($formData['obligatorio'] ?? '') === '1' ? 'selected' : '' ?>>Obligatorio</option>
                        <option value="0" <?= (string) ($formData['obligatorio'] ?? '') === '0' ? 'selected' : '' ?>>Opcional</option>
                    </select>
                </div>
                <?php if ($kind === 'asociacion'): ?>
                    <div class="form-row">
                        <div class="form-field"><label for="cantidad">Cantidad (opcional)</label><input type="number" id="cantidad" name="cantidad" min="1" max="65535" step="1" value="<?= $value('cantidad') ?>"></div>
                        <div class="form-field">
                            <label for="presentacion">Presentación (opcional)</label>
                            <select id="presentacion" name="presentacion">
                                <option value="">Sin definir</option>
                                <?php foreach (ContenidoValidator::PRESENTATIONS as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= ($formData['presentacion'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="form-field"><label for="observaciones">Observaciones (opcional)</label><textarea id="observaciones" name="observaciones" maxlength="5000" rows="3"><?= $value('observaciones') ?></textarea></div>
            <?php endif; ?>

            <fieldset class="source-fieldset"><legend><?= $kind === 'asociacion' ? 'Fuente de las condiciones particulares' : 'Fuente de la información' ?></legend>
                <div class="form-field">
                    <label for="fuente_id">Fuente (opcional si está pendiente)</label>
                    <select id="fuente_id" name="fuente_id">
                        <option value="">Pendiente de asociar</option>
                        <?php foreach ($sources as $source): ?>
                            <option value="<?= e((string) $source['id']) ?>" <?= (string) ($formData['fuente_id'] ?? '') === (string) $source['id'] ? 'selected' : '' ?>><?= e($source['nombre'] . ' (' . $source['tipo'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($kind === 'requisito'): ?>
                    <div class="form-field"><label for="estado_verificacion">Clasificación de la información</label>
                        <select id="estado_verificacion" name="estado_verificacion" required>
                            <?php foreach (TramiteValidator::VERIFICATIONS as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= ($formData['estado_verificacion'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="form-field"><label for="fecha_verificacion">Fecha de revisión (opcional)</label><input type="date" id="fecha_verificacion" name="fecha_verificacion" max="<?= e(date('Y-m-d')) ?>" value="<?= $value('fecha_verificacion') ?>"></div>
                <p class="field-help">No se copian automáticamente fuentes ni fechas de otros trámites. Conserva los datos desconocidos sin definir.</p>
            </fieldset>
            <div class="form-actions"><button class="button" type="submit"><?= $editing ? 'Guardar cambios' : 'Agregar' ?></button><a class="text-link" href="<?= e(url($backPath)) ?>">Cancelar</a></div>
        </form>
    </div>
</section>

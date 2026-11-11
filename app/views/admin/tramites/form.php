<?php
use App\Services\TramiteValidator;
$value = static fn (string $key): string => e((string) ($formData[$key] ?? ''));
?>
<section class="admin-section container">
    <a class="text-link" href="<?= e(url('admin/tramites')) ?>">← Volver a trámites</a>
    <div class="admin-heading"><div><span class="eyebrow">GESTIÓN DE CONTENIDO</span><h1><?= e($title) ?></h1><p>Los datos desconocidos pueden quedar pendientes de verificación.</p></div></div>
    <div class="admin-form-card">
        <?php require BASE_PATH . '/app/views/auth/errors.php'; ?>
        <form action="<?= e(url($id === null ? 'admin/tramites/crear' : 'admin/tramites/editar')) ?>" method="post" class="auth-form">
            <?= csrf_field() ?>
            <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= e((string) $id) ?>"><input type="hidden" name="version" value="<?= e($version) ?>"><?php endif; ?>
            <div class="form-field"><label for="nombre">Nombre del trámite</label><input id="nombre" name="nombre" value="<?= $value('nombre') ?>" maxlength="200" required></div>
            <div class="form-field"><label for="slug">Identificador para la dirección (slug)</label><input id="slug" name="slug" value="<?= $value('slug') ?>" maxlength="220" pattern="[a-z0-9]+(-[a-z0-9]+)*" aria-describedby="slug-help"><span class="field-help" id="slug-help">Déjalo vacío para generarlo desde el nombre. Usa letras minúsculas, números y guiones.</span></div>
            <div class="form-field"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" rows="6" maxlength="5000"><?= $value('descripcion') ?></textarea><span class="field-help">Texto informativo con su contexto. No agregues requisitos, costos o procedimientos sin respaldo.</span></div>
            <div class="form-row">
                <div class="form-field"><label for="categoria_id">Categoría</label><select id="categoria_id" name="categoria_id" required><option value="">Selecciona una categoría</option><?php foreach ($categories as $category): ?><option value="<?= e((string) $category['id']) ?>" <?= (string) ($formData['categoria_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['nombre']) ?></option><?php endforeach; ?></select></div>
                <div class="form-field"><label for="estado">Estado</label><select id="estado" name="estado" required><?php foreach (TramiteValidator::STATES as $key => $label): ?><option value="<?= e($key) ?>" <?= ($formData['estado'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-row">
                <div class="form-field"><label for="dificultad">Dificultad (opcional)</label><select id="dificultad" name="dificultad"><option value="">Sin definir</option><?php foreach (TramiteValidator::DIFFICULTIES as $key => $label): ?><option value="<?= e($key) ?>" <?= ($formData['dificultad'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                <div class="form-field"><label for="tiempo_estimado">Tiempo estimado (opcional)</label><input id="tiempo_estimado" name="tiempo_estimado" value="<?= $value('tiempo_estimado') ?>" maxlength="150"><span class="field-help">Déjalo vacío si aún no tienes información respaldada.</span></div>
            </div>
            <fieldset class="source-fieldset"><legend>Fuente y verificación</legend>
                <div class="form-field"><label for="fuente_id">Fuente</label><select id="fuente_id" name="fuente_id"><option value="">Pendiente de asociar</option><?php foreach ($sources as $source): ?><option value="<?= e((string) $source['id']) ?>" <?= (string) ($formData['fuente_id'] ?? '') === (string) $source['id'] ? 'selected' : '' ?>><?= e($source['nombre'] . ' (' . $source['tipo'] . ')') ?></option><?php endforeach; ?></select><span class="field-help">La administración de fuentes se incorporará más adelante.</span></div>
                <div class="form-row">
                    <div class="form-field"><label for="estado_verificacion">Clasificación de la información</label><select id="estado_verificacion" name="estado_verificacion" required><?php foreach (TramiteValidator::VERIFICATIONS as $key => $label): ?><option value="<?= e($key) ?>" <?= ($formData['estado_verificacion'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                    <div class="form-field"><label for="fecha_verificacion">Fecha de verificación (opcional)</label><input type="date" id="fecha_verificacion" name="fecha_verificacion" value="<?= $value('fecha_verificacion') ?>" max="<?= e(date('Y-m-d')) ?>"></div>
                </div>
                <p class="field-help">Publicar requiere descripción y fuente. La información pendiente permanece en borrador; marcarla como verificada requiere una fuente oficial y la fecha de revisión.</p>
            </fieldset>
            <div class="form-actions"><button type="submit" class="button"><?= $id === null ? 'Crear trámite' : 'Guardar cambios' ?></button><a class="text-link" href="<?= e(url('admin/tramites')) ?>">Cancelar</a></div>
        </form>
    </div>
</section>

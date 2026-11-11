<?php use App\Services\TramiteValidator; ?>
<section class="admin-section container">
    <a class="text-link" href="<?= e(url('admin/tramites')) ?>">← Volver a trámites</a>
    <div class="admin-heading"><div><span class="eyebrow">CONSULTA ADMINISTRATIVA</span><h1><?= e($tramite['nombre']) ?></h1><p><?= e(TramiteValidator::VERIFICATIONS[$tramite['estado_verificacion']]) ?></p></div><a class="button" href="<?= e(url('admin/tramites/editar?id=' . $tramite['id'])) ?>">Editar trámite</a></div>
    <div class="admin-form-card">
        <h2>Información general</h2>
        <p><a class="text-link" href="<?= e(url('admin/tramites/contenido?tramite_id=' . $tramite['id'])) ?>">Gestionar requisitos y documentos →</a></p>
        <p class="plain-description"><?= e($tramite['descripcion'] ?? 'Pendiente de verificación') ?></p>
        <dl class="profile-data">
            <div><dt>Identificador</dt><dd><?= e($tramite['slug']) ?></dd></div>
            <div><dt>Categoría</dt><dd><?= e($categories[$tramite['categoria_id']]['nombre'] ?? 'Categoría no disponible') ?></dd></div>
            <div><dt>Estado</dt><dd><?= e(TramiteValidator::STATES[$tramite['estado']]) ?></dd></div>
            <div><dt>Dificultad</dt><dd><?= e(TramiteValidator::DIFFICULTIES[$tramite['dificultad']] ?? 'Sin definir') ?></dd></div>
            <div><dt>Tiempo estimado</dt><dd><?= e($tramite['tiempo_estimado'] ?? 'Pendiente de verificación') ?></dd></div>
            <div><dt>Fuente</dt><dd><?= e($sources[$tramite['fuente_id']]['nombre'] ?? 'Pendiente de asociar') ?></dd></div>
            <div><dt>Tipo de fuente</dt><dd><?= e($sources[$tramite['fuente_id']]['tipo'] ?? 'Sin definir') ?></dd></div>
            <div><dt>Verificación</dt><dd><?= e($tramite['fecha_verificacion'] === null ? 'Pendiente' : (new DateTimeImmutable($tramite['fecha_verificacion']))->format('d/m/Y')) ?></dd></div>
            <div><dt>Actualizado</dt><dd><?= e(local_datetime($tramite['fecha_actualizacion'])) ?></dd></div>
        </dl>
        <?php if ($tramite['estado'] !== 'inactivo'): ?><a class="text-link" href="<?= e(url('admin/tramites/desactivar?id=' . $tramite['id'])) ?>">Desactivar trámite</a><?php endif; ?>
    </div>
</section>

<section class="admin-section container"><a href="<?= e(url('admin')) ?>">← Administración</a><h1>Actualizar atención</h1><p>Este dato se mostrará como «Reporte administrativo», con confianza baja y vigencia de 30 minutos. Una fuente oficial asociada no lo convierte en una lectura de API.</p>
<?php if($saved): ?><p role="status">Estado e histórico guardados y auditados.</p><?php endif; ?>
<?php require BASE_PATH.'/app/views/auth/errors.php'; ?>
<form class="auth-form admin-form-card" method="post" action="<?= e(url('admin/atencion')) ?>"><?= csrf_field() ?>
<label for="branch">Sucursal</label><select id="branch" name="sucursal_id" required><option value="">Selecciona</option><?php foreach($branches as $row): ?><option value="<?= (int)$row['id'] ?>" <?= (string)($formData['sucursal_id']??'')===(string)$row['id']?'selected':'' ?>><?= e($row['nombre']) ?></option><?php endforeach; ?></select>
<label for="source">Fuente</label><select id="source" name="fuente_id" required><option value="">Selecciona</option><?php foreach($sources as $row): ?><option value="<?= (int)$row['id'] ?>" <?= (string)($formData['fuente_id']??'')===(string)$row['id']?'selected':'' ?>><?= e($row['nombre']) ?></option><?php endforeach; ?></select>
<label for="people">Personas esperando (opcional)</label><input type="number" min="0" max="10000" id="people" name="personas_esperando" value="<?= e((string)($formData['personas_esperando']??'')) ?>">
<label for="minutes">Minutos de espera observados (opcional)</label><input type="number" min="0" max="1440" id="minutes" name="tiempo_espera" value="<?= e((string)($formData['tiempo_espera']??'')) ?>">
<label for="demand">Demanda</label><select name="nivel_demanda" id="demand"><?php foreach(['baja','media','alta'] as $value): ?><option <?= ($formData['nivel_demanda']??'media')===$value?'selected':'' ?>><?= e($value) ?></option><?php endforeach; ?></select>
<button class="button">Guardar observación actual</button></form></section>

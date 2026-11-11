<?php
$baseFields=fn()=>csrf_field().'<input type="hidden" name="tramite_id" value="'.(int)$item['id'].'"><input type="hidden" name="slug" value="'.e($item['slug']).'">';
$postUrl=url('mi-checklist/guardar');
?>
<section class="admin-section container"><a href="<?= e(url('tramite?slug='.$item['slug'])) ?>">← Información del trámite</a><h1>Mi checklist: <?= e($item['nombre']) ?></h1>
<p>Estas marcas son tu organización personal. No acreditan que una institución haya aprobado el trámite.</p>
<form method="post" action="<?= e($postUrl) ?>"><?= $baseFields() ?><input type="hidden" name="accion" value="favorito"><input type="hidden" name="valor" value="<?= $state['favorite']?'0':'1' ?>"><button class="button button-secondary"><?= $state['favorite']?'Quitar favorito':'Guardar favorito' ?></button></form>
<?php if($state['progress']===null): ?>
<form method="post" action="<?= e($postUrl) ?>"><?= $baseFields() ?><input type="hidden" name="accion" value="iniciar"><button class="button">Iniciar seguimiento</button></form>
<?php else: ?>
<section class="dashboard-panel"><h2>Documentos</h2>
<?php foreach($item['documentos'] as $row): $complete=(int)($state['documents'][$row['documento_id']]??0)===1; ?><form class="checklist-row" method="post" action="<?= e($postUrl) ?>"><?= $baseFields() ?><input type="hidden" name="accion" value="documento"><input type="hidden" name="elemento_id" value="<?= (int)$row['documento_id'] ?>"><input type="hidden" name="valor" value="<?= $complete?'0':'1' ?>"><span><?= e($row['nombre']) ?> (<?= e(obligation_label($row)) ?>)<?php if($row['observaciones']!==null): ?><small class="checklist-condition"><?= e($row['observaciones']) ?></small><?php endif; ?></span><button class="button button-secondary" aria-pressed="<?= $complete?'true':'false' ?>"><?= $complete?'✓ Preparado · Desmarcar':'Marcar preparado' ?></button></form><?php endforeach; ?>
<?php if($item['documentos']===[]): ?><p>No hay documentos registrados; revisa la información y sus fuentes.</p><?php endif; ?>
</section>
<section class="dashboard-panel"><h2>Pasos</h2>
<?php foreach($item['pasos'] as $row): $complete=(int)($state['steps'][$row['id']]??0)===1; ?><form class="checklist-row" method="post" action="<?= e($postUrl) ?>"><?= $baseFields() ?><input type="hidden" name="accion" value="paso"><input type="hidden" name="elemento_id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="valor" value="<?= $complete?'0':'1' ?>"><span><?= e($row['titulo']) ?></span><button class="button button-secondary" aria-pressed="<?= $complete?'true':'false' ?>"><?= $complete?'✓ Hecho · Desmarcar':'Marcar hecho' ?></button></form><?php endforeach; ?>
</section>
<form class="auth-form" method="post" action="<?= e($postUrl) ?>"><?= $baseFields() ?><input type="hidden" name="accion" value="estado"><label for="progress-state">Estado personal</label><select name="valor" id="progress-state"><?php foreach(['en_preparacion'=>'En preparación','en_curso'=>'En curso','pausado'=>'Pausado','completado'=>'Completado'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= $state['progress']['estado']===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select><button class="button">Actualizar estado</button></form>
<?php endif; ?></section>

<section class="admin-section container">
    <a class="text-link" href="<?= e(url('admin')) ?>">← Administración</a>
    <div class="admin-heading"><div><span class="eyebrow">GESTIÓN</span><h1><?= e($title) ?></h1><p>Registra solo información respaldada; deja los datos desconocidos sin definir.</p></div><?php if($resource->definition['create']??true): ?><a class="button" href="<?= e(url('admin/gestion?recurso='.$resource->table.'&nuevo=1')) ?>">Agregar +</a><?php endif; ?></div>
    <?php if($notice!==null): ?><div class="form-alert" role="status"><?= e($notice) ?></div><?php endif; ?>
    <?php if($resource->table==='integraciones_api'): ?><p class="form-alert">Preparación para proveedores autorizados. Crear o activar un registro no conecta con ninguna institución: se necesita documentación, autorización y un adaptador configurado localmente. Guarda solo la referencia de la credencial, nunca el token.</p><?php endif; ?>
    <?php if($showForm): ?>
    <div class="admin-form-card">
        <?php require BASE_PATH.'/app/views/auth/errors.php'; ?>
        <form method="post" action="<?= e(url('admin/gestion/guardar')) ?>" class="auth-form">
            <?= csrf_field() ?><input type="hidden" name="recurso" value="<?= e($resource->table) ?>"><input type="hidden" name="key" value="<?= e($key) ?>"><input type="hidden" name="version" value="<?= e($version) ?>">
            <?php foreach($resource->definition['fields'] as $field=>$meta): $value=(string)($formData[$field]??''); ?>
            <div class="form-field"><label for="<?= e($field) ?>"><?= e(ucfirst(str_replace('_',' ',$field))) ?><?= ($meta['required']??false)?'':' (opcional)' ?></label>
                <?php if(in_array($meta['type'],['enum','ref'],true)): ?>
                    <select id="<?= e($field) ?>" name="<?= e($field) ?>" <?= ($meta['required']??false)?'required':'' ?>>
                        <option value="">Selecciona un valor</option>
                        <?php $choices=$meta['type']==='enum'?$meta['values']:array_column($options[$field],'label','id'); foreach($choices as $choice=>$label): ?>
                        <option value="<?= e((string)$choice) ?>" <?= (string)$choice===$value?'selected':'' ?>><?= e((string)$label) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif($meta['type']==='textarea'): ?><textarea id="<?= e($field) ?>" name="<?= e($field) ?>" maxlength="<?= e((string)($meta['max']??5000)) ?>" rows="4"><?= e($value) ?></textarea>
                <?php else: ?><input id="<?= e($field) ?>" name="<?= e($field) ?>" value="<?= e($value) ?>" type="<?= e(in_array($meta['type'],['date','time','url'],true)?$meta['type']:'text') ?>" <?= ($meta['required']??false)?'required':'' ?> <?= isset($meta['max'])&&$meta['type']==='text'?'maxlength="'.(int)$meta['max'].'"':'' ?>><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="form-actions"><button class="button" type="submit">Guardar</button><a href="<?= e(url('admin/gestion?recurso='.$resource->table)) ?>">Cancelar</a></div>
        </form>
        <?php if($key!==''&&$resource->canRemove()): ?>
        <form method="post" action="<?= e(url('admin/gestion/quitar')) ?>" class="auth-form relation-removal">
            <?= csrf_field() ?><input type="hidden" name="recurso" value="<?= e($resource->table) ?>"><input type="hidden" name="key" value="<?= e($key) ?>"><input type="hidden" name="version" value="<?= e($version) ?>">
            <label><input type="checkbox" name="confirmar" value="1" required> Confirmo que deseo quitar esta relación. Será necesario revisar el contenido antes de publicarlo.</label>
            <button class="button danger-button" type="submit">Quitar esta relación</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <p><?= e((string)$total) ?> registros</p>
    <div class="content-cards">
    <?php foreach($rows as $row): ?><article class="content-card">
        <h2><?= e((string)($row['nombre']??$row['titulo']??$row['concepto']??'Registro')) ?></h2><dl class="content-facts">
        <?php foreach($resource->definition['fields'] as $field=>$meta): $raw=$row[$field]; $display=$raw===null?'Sin definir':(string)$raw;
            if($raw!==null&&$meta['type']==='enum'){$display=$meta['values'][(string)$raw]??(string)$raw;}
            if($raw!==null&&$meta['type']==='ref'){$display=array_column($options[$field],'label','id')[$raw]??(string)$raw;}
        ?><div><dt><?= e(ucfirst(str_replace('_',' ',$field))) ?></dt><dd><?= e((string)$display) ?></dd></div><?php endforeach; ?>
        </dl><a href="<?= e(url('admin/gestion?'.http_build_query(['recurso'=>$resource->table,'key'=>$resource->key($row)]))) ?>">Editar</a>
    </article><?php endforeach; ?>
    </div>
    <?php if($rows===[]): ?><p>No hay registros en esta página.</p><?php endif; ?>
    <nav class="pagination" aria-label="Paginación">
        <?php if($page>1): ?><a href="<?= e(url('admin/gestion?'.http_build_query(['recurso'=>$resource->table,'pagina'=>$page-1]))) ?>">Anterior</a><?php endif; ?>
        <span>Página <?= e((string)$page) ?></span>
        <?php if($page*30<$total): ?><a href="<?= e(url('admin/gestion?'.http_build_query(['recurso'=>$resource->table,'pagina'=>$page+1]))) ?>">Siguiente</a><?php endif; ?>
    </nav>
</section>

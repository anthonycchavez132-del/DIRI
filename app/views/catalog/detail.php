<?php $verification=\App\Services\TramiteValidator::VERIFICATIONS[$item['estado_verificacion']];$sourceIndex=array_column($item['fuentes'],null,'id'); ?>
<section class="admin-section container">
    <a class="text-link" href="<?= e(url('buscar')) ?>">← Buscar trámites</a>
    <div class="admin-heading"><div><span class="eyebrow"><?= e($item['categoria']) ?></span><h1><?= e($item['nombre']) ?></h1><span class="pill"><?= e($verification) ?></span></div></div>
    <p class="preserve-lines"><?= e((string)$item['descripcion']) ?></p>
    <?php if(in_array($item['slug'],['licencia-de-conducir','renovacion-de-licencia'],true)): ?><p><a class="button" href="<?= e(url('mi-ruta?modalidad='.($item['slug']==='licencia-de-conducir'?'primera':'renovacion'))) ?>">Ya tengo documentos: personalizar mi ruta</a></p><?php endif; ?>
    <?php if($guide['steps']!==[]): ?><section class="dashboard-panel guide-callout"><span class="eyebrow">RUTA SENCILLA · BETA · GRATIS</span><h2>Empieza por aquí</h2><p><?= e((string)count($guide['steps'])) ?> pasos para organizarte. Primero: <strong><?= e($guide['steps'][0]['title']) ?></strong>.</p><a class="button" href="<?= e(url('guia?slug='.$item['slug'])) ?>">Ver mi ruta paso a paso</a><p class="small-note">Disponible sin cuenta. Puedes marcar tus avances en esta pestaña.</p></section><?php endif; ?>
    <p><a class="button button-secondary" href="<?= e(url('mi-checklist?slug='.$item['slug'])) ?>">Mi checklist y favoritos · Premium</a></p>
    <p><a href="<?= e(url('comparar-sucursales?slug='.$item['slug'])) ?>">Comparar sucursales · Premium</a> · <a href="<?= e(url('rutas')) ?>">Explorar rutas · Premium</a></p>
    <p>Tiempo del trámite: <?= e($item['tiempo_estimado']??'Sin información suficiente') ?> · Dificultad: <?= e($item['dificultad']??'Sin definir') ?></p>
    <?php if($item['fecha_verificacion']!==null): ?><p>Información revisada el <?= e(verification_date($item['fecha_verificacion'])) ?>.</p><?php endif; ?>
    <?php $evidenceSourceId=$item['fuente_id'];$evidenceDate=$item['fecha_verificacion'];require BASE_PATH.'/app/views/catalog/evidence.php'; ?>
    <p class="small-note">La fecha indica cuándo se consultó la fuente. Revisa el alcance de esta ficha y las condiciones de cada documento, tarifa y modalidad.</p>
    <div class="dashboard-grid">
        <section class="dashboard-panel"><h2>Requisitos</h2>
            <?php foreach($item['requisitos'] as $row): ?><article class="content-card"><h3><?= e($row['nombre']) ?></h3><p><?= e($row['descripcion']??'') ?></p><p><?= e(obligation_label($row)) ?> · <?= e(\App\Services\TramiteValidator::VERIFICATIONS[$row['estado_verificacion']]) ?></p><p><?= e($row['observaciones']??'') ?></p><?php $evidenceSourceId=$row['fuente_id'];$evidenceDate=$row['fecha_verificacion'];require BASE_PATH.'/app/views/catalog/evidence.php'; ?></article><?php endforeach; ?>
            <?php if($item['requisitos']===[]): ?><p>Esta ficha no enumera requisitos. Consulta su alcance y el procedimiento específico en las fuentes.</p><?php endif; ?>
        </section>
        <section class="dashboard-panel"><h2>Documentos que debes preparar</h2>
            <?php foreach($item['documentos'] as $row): ?><article class="content-card"><h3><?= e($row['nombre']) ?></h3><p><?= e(obligation_label($row)) ?> · Cantidad: <?= e($row['cantidad']===null?'Sin definir':(string)$row['cantidad']) ?></p><p><?= e($row['presentacion']??'Presentación sin definir') ?></p><p><?= e($row['observaciones']??'') ?></p><?php $evidenceSourceId=$row['fuente_id'];$evidenceDate=$row['fecha_verificacion'];require BASE_PATH.'/app/views/catalog/evidence.php'; ?></article><?php endforeach; ?>
            <?php if($item['documentos']===[]): ?><p>Esta ficha no enumera documentos adicionales. Lee su alcance y la fuente correspondiente a tu caso.</p><?php endif; ?>
        </section>
    </div>
    <section class="dashboard-panel"><h2>Pasos en orden</h2><ol class="route-steps">
        <?php foreach($item['pasos'] as $row): ?><li class="content-card"><h3><?= e($row['titulo']) ?></h3><p><?= e($row['descripcion']??'') ?></p><p>Dónde: <?= e($row['institucion']??'Pendiente de verificación') ?> · Tiempo: <?= e($row['tiempo_estimado']??'Sin información suficiente') ?></p>
            <?php if($row['previos']!==[]): ?><p>Antes: <?= e(implode('; ',array_column($row['previos'],'titulo'))) ?></p><?php endif; ?>
            <?php if($row['documentos']!==[]): ?><p>Lleva: <?= e(implode('; ',array_column($row['documentos'],'nombre'))) ?></p><?php endif; ?>
            <?php foreach($row['costos'] as $cost): ?><p>Costo referencial: <?= e($cost['concepto']) ?> — <?= e($cost['monto']===null?'Sin monto confirmado':$cost['monto'].' '.$cost['moneda']) ?></p><?php endforeach; ?>
            <p><?= e($row['observaciones']??'') ?></p>
            <?php $evidenceSourceId=$row['fuente_id'];$evidenceDate=$row['fecha_verificacion'];require BASE_PATH.'/app/views/catalog/evidence.php'; ?>
        </li><?php endforeach; ?></ol>
        <?php if($item['pasos']===[]): ?><?php if($guide['steps']!==[]): ?><p>Consulta el orden sugerido de preparación en <a href="<?= e(url('guia?slug='.$item['slug'])) ?>">tu ruta sencilla · Beta</a>.</p><?php else: ?><p>Esta ficha no establece una secuencia de pasos. Consulta la modalidad específica en la fuente oficial.</p><?php endif; ?><?php endif; ?>
    </section>
    <section class="dashboard-panel"><h2>Costos y pagos</h2>
        <?php foreach($item['costos'] as $row): ?><article class="content-card"><h3><?= e($row['concepto']) ?></h3><p><?= $row['estado_verificacion']==='verificado'?'Costo verificado':'Costo referencial' ?>: <?= e($row['monto']===null?'Sin monto confirmado':$row['monto'].' '.$row['moneda']) ?></p><p>Dónde pagar: <?= e($row['institucion_pago']??'Entidad receptora: consulta la modalidad') ?> · <?= e($row['lugar_pago']??'Lugar sin confirmar') ?></p><p>Medio: <?= e($row['medio_pago']??'Sin confirmar') ?></p><p><?= e($row['observaciones']??'') ?></p><?php $evidenceSourceId=$row['fuente_id'];$evidenceDate=$row['fecha_verificacion'];require BASE_PATH.'/app/views/catalog/evidence.php'; ?></article><?php endforeach; ?>
        <?php if($item['costos']===[]): ?><p>No hay costos registrados. Esto no significa que el trámite sea gratuito.</p><?php endif; ?>
    </section>
    <section class="dashboard-panel"><h2>Dónde acudir</h2>
        <?php foreach($item['sucursales'] as $row): ?><article class="content-card"><h3><?= e($row['nombre']) ?></h3><p><?= e($row['institucion']) ?> · <?= e($row['ciudad']??'Ciudad sin confirmar') ?></p><p><?= e($row['direccion']??'Dirección sin confirmar') ?></p><p><?= e($row['telefono']??'Teléfono sin confirmar') ?></p><p><?= e($row['observaciones']??'') ?></p>
            <?php foreach($row['horarios'] as $hours): ?><p><?= e(['1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo'][(string)$hours['dia_semana']]) ?>: <?= e(substr($hours['hora_apertura'],0,5)) ?>–<?= e(substr($hours['hora_cierre'],0,5)) ?></p><?php endforeach; ?>
            <?php if($row['horarios']===[]): ?><p>Horario pendiente de verificación.</p><?php endif; ?>
            <?php $attention=$row['atencion'];require BASE_PATH.'/app/views/catalog/attention.php'; ?>
        </article><?php endforeach; ?>
        <?php if($item['sucursales']===[]): ?><p>No hay sucursales con disponibilidad registrada.</p><?php endif; ?>
    </section>
    <section class="dashboard-panel"><h2>Fuentes de esta información</h2>
        <?php foreach($item['fuentes'] as $row): ?><article class="content-card"><h3><?= e($row['nombre']) ?></h3><p>Tipo de fuente: <?= e($row['tipo']) ?> · Revisión: <?= e(verification_date($row['fecha_verificacion'])) ?></p><?php if(external_url($row['url'])!==null): ?><a href="<?= e($row['url']) ?>" rel="noopener noreferrer">Consultar fuente</a><?php endif; ?></article><?php endforeach; ?>
    </section>
</section>

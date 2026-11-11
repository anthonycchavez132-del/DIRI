<?php if($attention===null): ?><p>Espera: sin información reciente suficiente.</p>
<?php else: ?>
<div class="attention-state"><p><strong>Demanda <?= e($attention['nivel_demanda']) ?></strong></p>
<p>Espera <?= $attention['fuente']==='ESTIMACION'?'estimada':'' ?>:
<?php if($attention['tiempo_minimo']!==null&&$attention['tiempo_maximo']!==null): ?><?= (int)$attention['tiempo_minimo'] ?>–<?= (int)$attention['tiempo_maximo'] ?> minutos
<?php elseif($attention['tiempo_estimado']!==null): ?><?= (int)$attention['tiempo_estimado'] ?> minutos informados por la fuente
<?php else: ?>Sin tiempo informado<?php endif; ?></p>
<p>Personas esperando, aproximadamente: <?= e($attention['personas_esperando']===null?'Sin dato':(string)$attention['personas_esperando']) ?></p>
<p>Fuente: <?= e(['OFICIAL_API'=>'API oficial','DIRI_USUARIOS'=>'Reportado por usuarios','ADMINISTRADOR'=>'Reporte administrativo','ESTIMACION'=>'Estimación DIRI a partir de reportes ciudadanos'][$attention['fuente']]) ?> · Confianza: <?= e($attention['confianza']??'Sin clasificar') ?></p>
<p>Actualizado: <?= e(local_datetime($attention['ultima_actualizacion'])) ?> · Válido hasta: <?= e(local_datetime($attention['valido_hasta'])) ?></p>
<?php if($attention['cantidad_observaciones']!==null): ?><p><?= (int)$attention['cantidad_observaciones'] ?> observaciones. La espera puede cambiar.</p><?php endif; ?></div>
<?php endif; ?>

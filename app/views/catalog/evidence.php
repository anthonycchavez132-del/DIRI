<?php
$citedSource=$sourceIndex[$evidenceSourceId??0]??null;
?>
<?php if($citedSource!==null): ?>
<p class="source-reference">Fuente: <?php if(external_url($citedSource['url'])!==null): ?><a href="<?= e($citedSource['url']) ?>" rel="noopener noreferrer"><?= e($citedSource['nombre']) ?></a><?php else: ?><?= e($citedSource['nombre']) ?><?php endif; ?> · Consulta: <?= e(verification_date($evidenceDate??null)) ?></p>
<?php endif; ?>

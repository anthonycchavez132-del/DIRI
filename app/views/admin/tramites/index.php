<?php use App\Services\TramiteValidator; ?>
<section class="admin-section container">
    <div class="admin-heading"><div><span class="eyebrow">GESTIÓN DE CONTENIDO</span><h1>Trámites</h1><p>Organiza la información y revisa su fuente antes de publicarla.</p></div><a class="button" href="<?= e(url('admin/tramites/crear')) ?>">Crear trámite <span aria-hidden="true">+</span></a></div>
    <?php if ($notice !== null): ?><div class="form-alert" role="status"><?= e($notice) ?></div><?php endif; ?>
    <form action="<?= e(url('admin/tramites')) ?>" method="get" class="admin-filter">
        <label for="estado">Mostrar</label>
        <select id="estado" name="estado"><option value="">Todos los estados</option><?php foreach (TramiteValidator::STATES as $key => $label): ?><option value="<?= e($key) ?>" <?= $state === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
        <button type="submit" class="nav-button">Filtrar</button><span><?= e((string) $result['total']) ?> trámites</span>
    </form>
    <?php if ($result['items'] === []): ?>
        <div class="admin-empty"><h2>No hay trámites para mostrar</h2><p>Prueba con otro estado o crea un trámite en borrador.</p></div>
    <?php else: ?>
        <div class="table-scroll" role="region" aria-label="Listado de trámites" tabindex="0">
            <table class="admin-table">
                <caption class="visually-hidden">Trámites y acciones de administración</caption>
                <thead><tr><th scope="col">Trámite</th><th scope="col">Estado</th><th scope="col">Información</th><th scope="col">Actualización</th><th scope="col">Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($result['items'] as $item): ?>
                        <tr>
                            <td><strong><?= e($item['nombre']) ?></strong><span class="list-context"><?= e($item['categoria']) ?></span></td>
                            <td><span class="status-label"><?= e(TramiteValidator::STATES[$item['estado']]) ?></span></td>
                            <td><?= e(TramiteValidator::VERIFICATIONS[$item['estado_verificacion']]) ?></td>
                            <td><?= e(local_datetime($item['fecha_actualizacion'])) ?></td>
                            <td><div class="table-actions"><a href="<?= e(url('admin/tramites/ver?id=' . $item['id'])) ?>" aria-label="Consultar <?= e($item['nombre']) ?>">Ver</a><a href="<?= e(url('admin/tramites/editar?id=' . $item['id'])) ?>" aria-label="Editar <?= e($item['nombre']) ?>">Editar</a><?php if ($item['estado'] !== 'inactivo'): ?><a href="<?= e(url('admin/tramites/desactivar?id=' . $item['id'])) ?>" aria-label="Desactivar <?= e($item['nombre']) ?>">Desactivar</a><?php endif; ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <nav class="pagination" aria-label="Paginación de trámites">
        <?php if ($page > 1): ?><a href="<?= e(url('admin/tramites?' . http_build_query(['estado' => $state, 'pagina' => $page - 1]))) ?>">← Anterior</a><?php endif; ?>
        <span>Página <?= e((string) $page) ?> de <?= e((string) $result['pages']) ?></span>
        <?php if ($page < $result['pages']): ?><a href="<?= e(url('admin/tramites?' . http_build_query(['estado' => $state, 'pagina' => $page + 1]))) ?>">Siguiente →</a><?php endif; ?>
    </nav>
</section>

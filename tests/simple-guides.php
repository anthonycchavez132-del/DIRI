<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/core/bootstrap.php';
require __DIR__ . '/support/HttpTest.php';

use App\Core\Database;
use App\Services\CatalogService;
use App\Services\SimpleGuideService;

$base = rtrim($argv[1] ?? 'http://127.0.0.1/DIRI/public', '/');
$parsed = parse_url($base);
if ($parsed === false || !in_array($parsed['scheme'] ?? '', ['http', 'https'], true)
    || !in_array($parsed['host'] ?? '', ['127.0.0.1', 'localhost', '[::1]'], true)
    || isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['query']) || isset($parsed['fragment'])) {
    fwrite(STDERR, "Usa una URL HTTP local.\n"); exit(2);
}

try {
    $pdo = Database::connection();
    $before = $pdo->query('SELECT COUNT(*) FROM rutas_guardadas')->fetchColumn();
    $browser = new TestBrowser();
    [$status, $html] = $browser->request($base, '/guias');
    ensure($status === 200 && strpos($html, 'sin crear una cuenta') !== false, 'El listado beta es público y gratuito.');
    $service = new SimpleGuideService();
    $catalog = new CatalogService();
    $rows = $service->listing();
    ensure(count($rows) >= 20, 'El listado incluye el catálogo publicado.');
    ensure(count(array_filter($rows, static fn($row) => $row['count'] > 0)) === count($rows),
        'Todas las entradas publicadas tienen una ruta de pasos o de orientación.');
    ensure(strpos($html, 'Fichas cuya ruta seguimos preparando') === false,
        'El listado completo no muestra una sección vacía de rutas pendientes.');
    foreach ($rows as $row) {
        [$status, $html] = $browser->request($base, '/guia?slug=' . $row['slug']);
        ensure($status === 200 && strpos($html, 'TU RUTA SENCILLA') !== false, 'Guía pública: ' . $row['slug']);
        if ($row['count'] === 0) {
            ensure(strpos($html, 'Estamos preparando esta ruta') !== false && strpos($html, 'data-simple-guide') === false,
                'No se inventan pasos para ' . $row['slug']);
        }
    }
    $item = $catalog->detail('licencia-de-conducir');
    ensure($item !== null, 'Licencia publicada disponible.');
    $guide = $service->forItem($item);
    ensure(count($guide['steps']) === 5 && strpos($guide['steps'][0]['title'], 'médico') !== false
        && strpos($guide['steps'][1]['title'], 'autoescuela') !== false
        && strpos($guide['steps'][4]['place'], 'SEGIP') !== false,
        'Primera licencia: médico, autoescuela, antecedentes, pago y SEGIP.');
    ensure(strpos($guide['steps'][0]['note'], 'día siguiente') !== false
        && strpos($guide['steps'][2]['description'], 'FELCC y FELCN') !== false,
        'La ruta conserva la espera de registro médico y los antecedentes aplicables.');
    [$status, $html] = $browser->request($base, '/guia?slug=licencia-de-conducir');
    ensure(strpos($html, 'autoescuelas-autorizadas/') !== false
        && strpos($html, 'centros-de-salud-autorizados/') !== false
        && strpos($html, 'Consulta:') !== false,
        'La guía muestra directorios oficiales y evidencia por paso.');
    ensure(strpos($html, 'data-guide-key="' . $guide['fingerprint'] . '"') !== false,
        'Las marcas temporales se identifican por la versión del contenido.');
    [$status, $detail] = $browser->request($base, '/tramite?slug=licencia-de-conducir');
    ensure($status === 200 && strpos($detail, 'Ver mi ruta paso a paso') !== false,
        'La ficha enlaza la ruta gratuita antes de las herramientas Premium.');
    $renewal = $service->forItem($catalog->detail('renovacion-de-licencia'));
    ensure(strpos($renewal['steps'][1]['description'], 'cinco años y un día') !== false
        && strpos($renewal['steps'][1]['description'], 'Si no corresponde') !== false,
        'Renovación no obliga a todos a repetir la autoescuela.');
    $businessItem = $catalog->detail('apertura-de-negocio');
    $businessGuide = $service->forItem($businessItem);
    $businessSources = array_column($businessItem['fuentes'], null, 'id');
    ensure($businessGuide['kind'] === 'orientacion' && count($businessGuide['steps']) === 4
        && strpos($businessGuide['intro'], 'no es una secuencia obligatoria') !== false,
        'La apertura de negocio conserva su alcance de orientación condicional.');
    ensure($businessSources[$businessGuide['steps'][1]['source_id']]['url'] === 'https://www.seprec.gob.bo/index.php/tramite1/'
        && $businessSources[$businessGuide['steps'][2]['source_id']]['url'] === 'https://siatinfo.impuestos.gob.bo/index.php/requisitos-para-la-inscripcion/requisitos-para-obtener-el-nit-para-personas',
        'Las gestiones de SEPREC y SIN citan sus propias fuentes.');
    $seenTargets = [];
    foreach ($rows as $row) {
        $route = $service->forItem($catalog->detail($row['slug']));
        foreach ($route['steps'] as $step) {
            foreach ($step['related'] as $target) {
                if (isset($seenTargets[$target['slug']])) { continue; }
                $seenTargets[$target['slug']] = true;
                ensure($browser->request($base, '/guia?slug=' . $target['slug'])[0] === 200,
                    'Destino enlazado disponible: ' . $target['slug']);
            }
        }
    }
    ensure(count($seenTargets) >= 9, 'Las guías generales conectan con rutas específicas publicadas.');
    [$status, $html] = $browser->request($base, '/guia?slug=pasaporte');
    ensure($status === 200 && strpos($html, 'Información referencial') !== false
        && strpos($html, 'no permite confirmar el arancel total') !== false,
        'La orientación de pasaporte muestra su estado y sus límites.');
    $identity = $service->forItem($catalog->detail('cedula-de-identidad'));
    ensure(strpos($identity['steps'][0]['description'], 'Para menores') !== false
        && strpos($identity['steps'][1]['description'], 'Si eres mayor de edad') !== false,
        'Primera cédula distingue los documentos por edad.');
    $missingSource = $businessItem;
    $missingSource['fuentes'] = array_values(array_filter($missingSource['fuentes'],
        static fn($source) => $source['url'] !== 'https://www.seprec.gob.bo/index.php/tramite1/'));
    ensure($service->forItem($missingSource)['steps'] === [],
        'La falta de una fuente complementaria impide mostrar una ruta parcial.');
    $changed = $item;
    $changed['fecha_verificacion'] = '2026-10-22';
    ensure($service->forItem($changed)['steps'] === [], 'Una revisión nueva no reutiliza automáticamente el resumen anterior.');
    $changed = $item;
    $changed['pasos'] = [['titulo' => 'Paso administrativo revisado', 'descripcion' => 'Nueva modalidad',
        'institucion' => null, 'observaciones' => null, 'documentos' => [], 'previos' => [],
        'fuente_id' => $item['fuente_id'], 'fecha_verificacion' => $item['fecha_verificacion']]];
    ensure($service->forItem($changed)['steps'][0]['title'] === 'Paso administrativo revisado',
        'Los pasos publicados desde administración tienen prioridad.');
    foreach (['/guia', '/guia?slug[]=licencia-de-conducir', '/guia?slug=no-existe', '/guia?slug=%3Cscript%3E'] as $path) {
        ensure($browser->request($base, $path)[0] === 404, 'Entrada inválida controlada: ' . $path);
    }
    ensure($browser->request($base, '/comparar-sucursales?slug=licencia-de-conducir')[0] === 303,
        'La comparación avanzada conserva su autenticación.');
    ensure($pdo->query('SELECT COUNT(*) FROM rutas_guardadas')->fetchColumn() === $before,
        'Consultar la beta no guarda rutas en cuentas.');
    echo "OK: rutas sencillas beta comprobadas, sin modificar datos.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . "\n"); exit(1);
}

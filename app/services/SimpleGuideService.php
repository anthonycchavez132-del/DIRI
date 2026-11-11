<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class SimpleGuideService
{
    private ?array $publishedTargets = null;

    private function related(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }
        if ($this->publishedTargets === null) {
            $rows = Database::connection()->query("SELECT t.slug,t.nombre FROM tramites t JOIN categorias c ON c.id=t.categoria_id WHERE t.estado='publicado' AND c.estado=1")->fetchAll();
            $this->publishedTargets = array_column($rows, null, 'slug');
        }
        $result = [];
        foreach ($slugs as $slug) {
            if (is_string($slug) && isset($this->publishedTargets[$slug])) {
                $result[] = $this->publishedTargets[$slug];
            }
        }
        return $result;
    }

    public function forItem(array $item): array
    {
        $steps = [];
        $kind = 'pasos';
        $intro = 'Sigue los pasos publicados para esta modalidad. Revisa los documentos y las condiciones de tu caso antes de salir.';
        // Las ediciones publicadas desde administración tienen prioridad.
        foreach ($item['pasos'] as $step) {
            $steps[] = [
                'title' => $step['titulo'],
                'description' => $step['descripcion'] ?? '',
                'place' => $step['institucion'] ?? 'Consulta el lugar en la fuente oficial',
                'result' => null,
                'note' => $step['observaciones'] ?? '',
                'documents' => array_column($step['documentos'], 'nombre'),
                'previous' => array_column($step['previos'], 'titulo'),
                'source_id' => $step['fuente_id'],
                'reviewed_on' => $step['fecha_verificacion'],
                'links' => [],
                'related' => [],
            ];
        }
        $guides = require BASE_PATH . '/config/guias.php';
        $custom = $guides[$item['slug']] ?? null;
        $sources = array_column($item['fuentes'], null, 'id');
        $primary = $sources[$item['fuente_id']] ?? null;
        if ($steps === [] && $custom !== null && $primary !== null
            && $primary['url'] === $custom['source_url']
            && substr((string)$item['fecha_verificacion'], 0, 10) === $custom['reviewed_on']) {
            $candidateSteps = [];
            foreach ($custom['steps'] as $step) {
                $stepUrl = $step['source_url'] ?? $custom['source_url'];
                $evidence = null;
                foreach ($sources as $candidate) {
                    if ($candidate['url'] === $stepUrl
                        && substr((string)$candidate['fecha_verificacion'], 0, 10) === $custom['reviewed_on']) {
                        $evidence = $candidate;
                        break;
                    }
                }
                // No se publica una secuencia parcial si falta una de sus fuentes.
                if ($evidence === null) {
                    $candidateSteps = [];
                    break;
                }
                $step['related'] = $this->related($step['related'] ?? []);
                $candidateSteps[] = $step + [
                    'note' => '', 'links' => [], 'documents' => [], 'previous' => [],
                    'source_id' => $evidence['id'], 'reviewed_on' => $custom['reviewed_on'],
                ];
            }
            if ($candidateSteps !== []) {
                $steps = $candidateSteps;
                $intro = $custom['intro'];
                $kind = $custom['kind'] ?? 'pasos';
            }
        }
        return [
            'steps' => $steps,
            'intro' => $intro,
            'kind' => $kind,
            'fingerprint' => hash('sha256', json_encode([$item['slug'], $intro, $kind, $steps], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        ];
    }

    public function listing(): array
    {
        $rows = Database::connection()->query("SELECT t.slug FROM tramites t JOIN categorias c ON c.id=t.categoria_id WHERE t.estado='publicado' AND c.estado=1 ORDER BY t.nombre LIMIT 100")->fetchAll();
        $result = [];
        $catalog = new CatalogService();
        foreach ($rows as $row) {
            $item = $catalog->detail($row['slug']);
            if ($item === null) {
                continue;
            }
            $guide = $this->forItem($item);
            $result[] = ['slug' => $item['slug'], 'nombre' => $item['nombre'],
                'categoria' => $item['categoria'], 'count' => count($guide['steps']), 'kind' => $guide['kind']];
        }
        return $result;
    }
}

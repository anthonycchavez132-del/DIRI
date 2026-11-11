<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Auditoria;
use App\Models\Documento;
use App\Models\Requisito;
use App\Models\Resource;
use App\Models\Tramite;
use App\Models\TramiteDocumento;
use DomainException;
use RuntimeException;
use Throwable;

/** Carga revisada y explícita; no descarga páginas ni actualiza datos sola. */
class OfficialCatalogImporter
{
    private array $sources = [];
    private array $institutions = [];
    private array $documents = [];
    private string $date;

    public function load(string $file): array
    {
        $manifest = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($manifest) || !preg_match('/\Adatos_[a-z0-9_]+\z/', $manifest['batch'] ?? '')
            || count($manifest['procedures'] ?? []) !== 20) {
            throw new RuntimeException('El catálogo debe identificar un lote de veinte entradas.');
        }
        $slugs = array_column($manifest['procedures'], 'slug');
        if (count(array_unique($slugs)) !== 20) {
            throw new RuntimeException('No se permiten identificadores repetidos.');
        }
        foreach ($manifest['sources'] as $source) {
            $host = strtolower((string) parse_url($source['url'], PHP_URL_HOST));
            if (external_url($source['url']) === null || parse_url($source['url'], PHP_URL_SCHEME) !== 'https'
                || !(str_ends_with($host, '.gob.bo') || in_array($host,['web.oep.org.bo','lapaz.bo'],true))
                || str_contains($host, '.test.') || str_contains($host, 'test.')) {
                throw new RuntimeException('El lote solo admite las fuentes oficiales revisadas.');
            }
        }
        return $manifest;
    }

    private function textInput(array $values): array
    {
        return array_map(static fn ($value) => $value === null ? '' : (string) $value, $values);
    }

    private function resource(string $table, array $values): int
    {
        $resource = new Resource($table);
        [$data, $errors] = ResourceValidator::validate($resource, $this->textInput($values), null);
        if ($errors !== []) {
            throw new RuntimeException($table . ': ' . implode(' ', $errors));
        }
        return $resource->save($data, null);
    }

    private function content(string $kind, array $values): array
    {
        [$data, $errors] = ContenidoValidator::validate($kind, $this->textInput($values));
        if ($errors !== []) {
            throw new RuntimeException($kind . ': ' . implode(' ', $errors));
        }
        return $data;
    }

    private function sourceId(string $key): int
    {
        if (!isset($this->sources[$key])) {
            throw new RuntimeException('Referencia de fuente no resuelta: ' . $key);
        }
        return $this->sources[$key];
    }

    private function backup(array $rows, string $batch): string
    {
        $pdo = Database::connection();
        $ids = array_column($rows, 'id');
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $snapshot = ['batch' => $batch, 'created_at_utc' => gmdate('c'), 'tramites' => $rows];
        foreach (['requisitos','tramite_documentos','pasos_tramite','costos','tramite_fuentes','tramite_sucursales','palabras_clave_tramite'] as $table) {
            $query = $pdo->prepare("SELECT * FROM $table WHERE tramite_id IN ($marks)");
            $query->execute($ids);
            $snapshot[$table] = $query->fetchAll();
        }
        // Solo contenido público/administrativo; no se exportan usuarios ni hashes.
        foreach (['fuentes','instituciones','sucursales','documentos'] as $table) {
            $snapshot[$table] = $pdo->query("SELECT * FROM $table")->fetchAll();
        }
        $directory = BASE_PATH . '/storage/backups';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el respaldo privado.');
        }
        $path = $directory . '/' . $batch . '-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.json';
        $handle = fopen($path, 'x');
        if ($handle === false) {
            throw new RuntimeException('No se pudo reservar el respaldo.');
        }
        $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $written = fwrite($handle, $json);
        fclose($handle);
        if ($written !== strlen($json)) {
            throw new RuntimeException('Respaldo incompleto: carga cancelada.');
        }
        return $path;
    }

    /** $apply=false ejecuta la validación completa y revierte la transacción. */
    public function run(array $manifest, int $actor, bool $apply): array
    {
        $this->date = $manifest['reviewed_on'];
        $this->sources = $this->institutions = $this->documents = [];
        $checksum = hash('sha256', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $stamp = 'Catálogo oficial La Paz; SHA256 ' . $checksum;
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $query = $pdo->prepare("SELECT u.id FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.id=? AND u.estado='activo' AND r.nombre='administrador' FOR UPDATE");
            $query->execute([$actor]);
            if (!$query->fetch()) {
                throw new DomainException('Se requiere un administrador activo para auditar la carga.', 403);
            }
            $query = $pdo->prepare('SELECT descripcion FROM versiones_esquema WHERE version=? FOR UPDATE');
            $query->execute([$manifest['batch']]);
            $existing = $query->fetchColumn();
            if ($existing !== false) {
                if (!hash_equals($stamp, $existing)) {
                    throw new DomainException('El identificador del lote ya existe con contenido diferente.', 409);
                }
                $pdo->rollBack();
                return ['status' => 'already_applied', 'entries' => 20];
            }
            $slugs = array_column($manifest['procedures'], 'slug');
            $query = $pdo->prepare('SELECT * FROM tramites WHERE slug IN (' . implode(',', array_fill(0,20,'?')) . ') ORDER BY id FOR UPDATE');
            $query->execute($slugs);
            $originals = $query->fetchAll();
            if (count($originals) !== 20) {
                throw new DomainException('No se encontraron los veinte borradores iniciales.', 409);
            }
            $bySlug = array_column($originals, null, 'slug');
            foreach ($originals as $row) {
                if ($row['estado'] !== 'borrador' || $row['estado_verificacion'] !== 'pendiente'
                    || $row['descripcion'] !== 'Pendiente de verificación' || (int) $row['revision'] !== 1) {
                    throw new DomainException('Hay contenido editado manualmente: se conserva y se cancela la carga.',409);
                }
                foreach (['requisitos','tramite_documentos','pasos_tramite','costos','tramite_fuentes','tramite_sucursales','palabras_clave_tramite'] as $table) {
                    $query = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE tramite_id=?");
                    $query->execute([$row['id']]);
                    if ((int) $query->fetchColumn() !== 0) {
                        throw new DomainException('Hay relaciones existentes: se cancela para no sustituirlas.',409);
                    }
                }
            }
            $backup = $apply ? $this->backup($originals, $manifest['batch']) : null;
            foreach ($manifest['sources'] as $key => $source) {
                $query = $pdo->prepare('SELECT id,tipo FROM fuentes WHERE url=? ORDER BY id LIMIT 1');
                $query->execute([$source['url']]);
                $found = $query->fetch();
                if ($found) {
                    if ($found['tipo'] !== 'oficial') {
                        throw new DomainException('Una URL ya existe con otra clasificación; revisar manualmente.',409);
                    }
                    $this->sources[$key] = (int) $found['id'];
                    continue;
                }
                $notes = $source['observaciones'] . ' Consulta: ' . $this->date . '.';
                if (isset($source['evidence_sha256'])) {
                    $notes .= ' Huella de evidencia: ' . $source['evidence_sha256'];
                }
                $this->sources[$key] = $this->resource('fuentes', [
                    'nombre' => $source['nombre'], 'url' => $source['url'], 'tipo' => 'oficial',
                    'fecha_verificacion' => $this->date, 'observaciones' => $notes,
                ]);
            }
            foreach ($manifest['institutions'] as $key => $institution) {
                $query = $pdo->prepare('SELECT id FROM instituciones WHERE nombre=?');
                $query->execute([$institution['nombre']]);
                $found = $query->fetchColumn();
                $this->institutions[$key] = $found ? (int) $found : $this->resource('instituciones', [
                    'nombre' => $institution['nombre'], 'sitio_web' => $institution['sitio_web'], 'estado' => '1',
                    'fuente_id' => $this->sourceId($institution['fuente']), 'fecha_verificacion' => $this->date,
                ]);
            }
            foreach ($manifest['documents'] as $key => $document) {
                $query = $pdo->prepare('SELECT id FROM documentos WHERE nombre=?');
                $query->execute([$document['nombre']]);
                $found = $query->fetchColumn();
                $this->documents[$key] = $found ? (int) $found : (new Documento())->save($this->content('documento', [
                    'nombre' => $document['nombre'], 'descripcion' => $document['descripcion'],
                    'fuente_id' => $this->sourceId($document['fuente']), 'fecha_verificacion' => $this->date,
                ]), null);
            }
            foreach ($manifest['procedures'] as $entry) {
                $id = (int) $bySlug[$entry['slug']]['id'];
                foreach ($entry['requisitos'] as $requirement) {
                    (new Requisito())->save($this->content('requisito', [
                        'nombre' => $requirement['nombre'], 'descripcion' => $requirement['descripcion'],
                        'obligatorio' => (int) $requirement['obligatorio'], 'observaciones' => $requirement['observaciones'],
                        'fuente_id' => $this->sourceId($requirement['fuente']),
                        'estado_verificacion' => 'verificado', 'fecha_verificacion' => $this->date,
                    ]), $id, null);
                }
                foreach ($entry['documentos'] as $link) {
                    $documentId = $this->documents[$link['documento']];
                    (new TramiteDocumento())->save($this->content('asociacion', [
                        'documento_id' => $documentId, 'obligatorio' => (int) $link['obligatorio'],
                        'cantidad' => $link['cantidad'], 'presentacion' => $link['presentacion'],
                        'observaciones' => $link['observaciones'], 'fuente_id' => $this->sourceId($link['fuente']),
                        'fecha_verificacion' => $this->date,
                    ]), $id, $documentId, false);
                }
                foreach ($entry['pasos'] as $index => $step) {
                    $this->resource('pasos_tramite', [
                        'tramite_id' => $id, 'numero_paso' => $index+1, 'titulo' => $step['titulo'],
                        'descripcion' => $step['descripcion'], 'observaciones' => $step['observaciones'],
                        'institucion_id' => $this->institutions[$step['institucion']],
                        'fuente_id' => $this->sourceId($step['fuente']), 'fecha_verificacion' => $this->date,
                    ]);
                }
                foreach ($entry['costos'] as $cost) {
                    $this->resource('costos', [
                        'tramite_id' => $id, 'institucion_id' => $this->institutions[$entry['institucion']],
                        'concepto' => $cost['concepto'], 'monto' => $cost['monto'], 'moneda' => $cost['moneda'],
                        'observaciones' => $cost['observaciones'], 'lugar_pago' => $cost['lugar_pago'], 'medio_pago' => $cost['medio_pago'],
                        'estado_verificacion' => $cost['estado_verificacion'], 'fuente_id' => $this->sourceId($cost['fuente']),
                        'fecha_verificacion' => $this->date,
                    ]);
                }
                foreach (array_unique(array_merge([$entry['fuente']],$entry['fuentes_adicionales'])) as $source) {
                    $this->resource('tramite_fuentes', ['tramite_id' => $id, 'fuente_id' => $this->sourceId($source), 'fecha_verificacion' => $this->date]);
                }
                foreach (array_unique($entry['palabras_clave']) as $word) {
                    $this->resource('palabras_clave_tramite', ['tramite_id' => $id, 'palabra' => $word]);
                }
                $input = [
                    'nombre' => $entry['nombre'], 'slug' => $entry['slug'],
                    'descripcion' => 'Entidad: ' . $manifest['institutions'][$entry['institucion']]['nombre'] . ".\n\n" . $entry['descripcion'],
                    'categoria_id' => $bySlug[$entry['slug']]['categoria_id'], 'estado' => 'publicado',
                    'dificultad' => '', 'tiempo_estimado' => $entry['tiempo_estimado'],
                    'fuente_id' => $this->sourceId($entry['fuente']),
                    'estado_verificacion' => $entry['estado_verificacion'], 'fecha_verificacion' => $this->date,
                ];
                [$data,$errors] = TramiteValidator::validate($this->textInput($input),$id);
                if ($errors !== []) {
                    throw new RuntimeException($entry['slug'] . ': ' . implode(' ',$errors));
                }
                (new Tramite())->update($id,$data);
                (new Auditoria())->record($actor,$id,'publicar_catalogo_revisado','tramites',$manifest['batch'] . '; ' . $checksum);
            }
            foreach ($manifest['branches'] as $branch) {
                $branchId = $this->resource('sucursales', [
                    'institucion_id' => $this->institutions[$branch['institucion']], 'nombre' => $branch['nombre'],
                    'direccion' => $branch['direccion'], 'ciudad' => $branch['ciudad'], 'telefono' => $branch['telefono'],
                    'estado' => 'activa', 'fuente_id' => $this->sourceId($branch['fuente']), 'fecha_verificacion' => $this->date,
                ]);
                foreach ($branch['tramites'] as $slug) {
                    $this->resource('tramite_sucursales', [
                        'tramite_id' => $bySlug[$slug]['id'], 'sucursal_id' => $branchId, 'disponible' => '1',
                        'observaciones' => 'Ubicación y servicio según ficha municipal consultada. Confirmar atención antes de acudir.',
                        'fuente_id' => $this->sourceId($branch['fuente']), 'fecha_verificacion' => $this->date,
                    ]);
                }
                foreach ($branch['horarios'] as $hours) {
                    $this->resource('horarios', $hours + ['sucursal_id' => $branchId, 'fuente_id' => $this->sourceId($branch['fuente']), 'fecha_verificacion' => $this->date]);
                }
            }
            $pdo->prepare('INSERT INTO versiones_esquema (version,descripcion) VALUES (?,?)')->execute([$manifest['batch'],$stamp]);
            $verified = count(array_filter($manifest['procedures'], static fn($row) => $row['estado_verificacion']==='verificado'));
            $result = ['status' => $apply ? 'applied' : 'validated_rollback', 'entries' => 20, 'verified' => $verified, 'referential' => 20-$verified, 'sources' => count($this->sources), 'backup' => $backup];
            if ($apply) {$pdo->commit();} else {$pdo->rollBack();}
            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {$pdo->rollBack();}
            throw $exception;
        }
    }
}

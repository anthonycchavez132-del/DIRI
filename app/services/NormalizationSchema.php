<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use RuntimeException;

final class NormalizationSchema
{
    public const TABLES=['paso_documentos','paso_dependencias','paso_costos','progreso_documentos','progreso_pasos'];

    /** No ejecuta DDL; compara antes de aplicar y permite reanudar pasos ya completados. */
    public static function pending(PDO $pdo): array
    {
        $steps=SqlScript::statements(file_get_contents(BASE_PATH.'/database/migrations/009_normalizacion_3fn.sql'));
        $pending=[];
        foreach ($steps as $step) {
            if (preg_match('/^CREATE TABLE (\w+)/', $step, $match)) {
                $q=$pdo->prepare('SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
                $q->execute([$match[1]]); $type=$q->fetchColumn();
                if ($type===false) { $pending[]=$step; continue; }
                if ($type!=='BASE TABLE') { throw new RuntimeException('El catálogo de palabras tiene un objeto incompatible.'); }
                $columns=$pdo->query('SHOW COLUMNS FROM palabras_clave')->fetchAll();
                if (array_column($columns,'Field')!==['palabra','palabra_normalizada'] || $columns[0]['Key']!=='PRI' || $columns[0]['Type']!=='varchar(150)' || strpos($columns[1]['Extra'],'STORED GENERATED')===false) {
                    throw new RuntimeException('El catálogo de palabras tiene una definición incompatible.');
                }
                $expression=$pdo->query("SELECT GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='palabras_clave' AND COLUMN_NAME='palabra_normalizada'")->fetchColumn();
                if (str_replace(['`',' ','lower('],['','','lcase('],strtolower($expression))!=='lcase(trim(palabra))') { throw new RuntimeException('Expresión de alias incompatible.'); }
                $index=$pdo->query("SHOW INDEX FROM palabras_clave WHERE Key_name='idx_palabras_normalizada'")->fetchAll();
                if (array_column($index,'Column_name')!==['palabra_normalizada','palabra']) { throw new RuntimeException('Índice de alias incompatible.'); }
            } elseif (preg_match('/^INSERT INTO palabras_clave\(/',$step)) {
                // Se ejecuta únicamente antes de quitar la columna anterior.
                if (self::column($pdo,'palabras_clave_tramite','palabra_normalizada')) { $pending[]=str_replace('INSERT INTO','INSERT IGNORE INTO',$step); }
            } elseif (preg_match('/^ALTER TABLE (\w+)/', $step, $match)) {
                $column=$match[1]==='palabras_clave_tramite'?'palabra_normalizada':($match[1]==='integraciones_api'?'tipo':'tramite_id');
                if (self::column($pdo,$match[1],$column)) { $pending[]=$step; }
                else {
                    preg_match_all('/ADD CONSTRAINT (\w+) FOREIGN KEY\s*\((\w+)\) REFERENCES (\w+)\((\w+)\)/',$step,$keys,PREG_SET_ORDER);
                    foreach ($keys as $key) {
                        $q=$pdo->prepare('SELECT COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=?');
                        $q->execute([$match[1],$key[1]]); $row=$q->fetch();
                        if (!$row || array_values($row)!==[$key[2],$key[3],$key[4]]) { throw new RuntimeException('Relaciones incompletas en '.$match[1]); }
                    }
                }
            } elseif (preg_match('/^CREATE TRIGGER (\w+) BEFORE (INSERT|UPDATE|DELETE) ON (\w+) FOR EACH ROW\s*([\s\S]+)$/',$step,$match)) {
                $q=$pdo->prepare('SELECT ACTION_STATEMENT,EVENT_OBJECT_TABLE,EVENT_MANIPULATION,ACTION_TIMING FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?');
                $q->execute([$match[1]]); $row=$q->fetch();
                if (!$row) { $pending[]=$step; }
                else {
                    $normalize=fn($sql)=>preg_replace('/\s+/',' ',strtolower(str_replace('`','',trim($sql))));
                    if ($normalize($row['ACTION_STATEMENT'])!==$normalize($match[4]) || $row['EVENT_OBJECT_TABLE']!==$match[3] || $row['EVENT_MANIPULATION']!==$match[2] || $row['ACTION_TIMING']!=='BEFORE') {
                        throw new RuntimeException('Trigger incompatible: '.$match[1]);
                    }
                }
            } else { throw new RuntimeException('Paso de migración no reconocido.'); }
        }
        return $pending;
    }

    private static function column(PDO $pdo,string $table,string $column): bool
    {
        $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $q->execute([$table,$column]); return (int)$q->fetchColumn()===1;
    }

    public static function apply(PDO $pdo): void
    {
        foreach (self::pending($pdo) as $step) { $pdo->exec($step); }
        if (self::pending($pdo)!==[]) { throw new RuntimeException('La migración quedó incompleta.'); }
        $pdo->exec("INSERT INTO versiones_esquema(version,descripcion) SELECT '009_normalizacion_3fn','Relaciones sin dependencias parciales y catálogo de alias' WHERE NOT EXISTS(SELECT 1 FROM versiones_esquema WHERE version='009_normalizacion_3fn')");
    }
}

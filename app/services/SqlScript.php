<?php
declare(strict_types=1);
namespace App\Services;

/** Lector de nuestros archivos SQL, con instrucciones DELIMITER de MariaDB/MySQL. */
final class SqlScript
{
    public static function statements(string $sql): array
    {
        $delimiter=';'; $buffer=''; $statements=[];
        foreach (preg_split('/\r?\n/', $sql) as $line) {
            if (preg_match('/^\s*--/', $line)) { continue; }
            if (preg_match('/^DELIMITER\s+(\S+)\s*$/i', trim($line), $matches)) {
                if (trim($buffer)!=='') { throw new \RuntimeException('DELIMITER con instrucción incompleta.'); }
                $delimiter=$matches[1]; continue;
            }
            $buffer.=$line."\n";
            if (substr(rtrim($buffer), -strlen($delimiter))===$delimiter) {
                $statements[]=trim(substr(rtrim($buffer),0,-strlen($delimiter)));
                $buffer='';
            }
        }
        if (trim($buffer)!=='') { throw new \RuntimeException('Instrucción SQL incompleta.'); }
        return $statements;
    }
}

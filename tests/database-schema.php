<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/core/bootstrap.php';

use App\Core\Database;

function verify(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException($description);
    }
    echo 'PASS: ' . $description . "\n";
}

function insertRow(PDO $pdo, string $sql, array $parameters = []): int
{
    $pdo->prepare($sql)->execute($parameters);
    return (int) $pdo->lastInsertId();
}

function rejected(PDO $pdo, string $sql, array $parameters, array $expectedCodes, string $description): void
{
    try {
        $pdo->prepare($sql)->execute($parameters);
    } catch (PDOException $exception) {
        verify(in_array((int) ($exception->errorInfo[1] ?? 0), $expectedCodes, true), $description);
        return;
    }
    throw new RuntimeException($description);
}

$pdo = null;

try {
    $pdo = Database::connection();
    $meta = $pdo->query('SELECT @@session.sql_mode AS modo, @@session.time_zone AS zona')->fetch();
    verify(strpos($meta['modo'], 'STRICT_TRANS_TABLES') !== false && $meta['zona'] === '+00:00', 'La sesion usa modo estricto y UTC.');

    $tables = $pdo->query("SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'")->fetchAll();
    verify(count($tables) === 40, 'Las 40 tablas del esquema normalizado estan instaladas.');
    foreach ($tables as $table) {
        verify($table['ENGINE'] === 'InnoDB' && strpos($table['TABLE_COLLATION'], 'utf8mb4_') === 0, 'Motor y codificacion: ' . $table['TABLE_NAME']);
    }
    verify($pdo->query("SELECT version FROM versiones_esquema WHERE version = '003_inicial'")->fetchColumn() === '003_inicial', 'La instalacion inicial esta registrada.');
    $role = (int) $pdo->query("SELECT id FROM roles WHERE nombre = 'usuario'")->fetchColumn();
    $plan = (int) $pdo->query("SELECT id FROM planes WHERE nombre = 'free'")->fetchColumn();
    $category = (int) $pdo->query('SELECT id FROM categorias ORDER BY id LIMIT 1')->fetchColumn();
    verify($role > 0 && $plan > 0 && $category > 0, 'Los catalogos necesarios para registro estan disponibles.');

    // Todos los datos de prueba se revierten, incluso cuando una comprobacion falla.
    // Los contadores AUTO_INCREMENT pueden avanzar a pesar del ROLLBACK.
    $pdo->beginTransaction();
    $suffix = bin2hex(random_bytes(8));
    $hash = password_hash('Clave temporal de pruebas', PASSWORD_DEFAULT);
    $user = insertRow($pdo, 'INSERT INTO usuarios (nombre,apellido,email,password,rol_id,plan_id) VALUES (?,?,?,?,?,?)', ['Prueba','Temporal',$suffix . '@example.invalid',$hash,$role,$plan]);
    rejected($pdo, 'INSERT INTO usuarios (nombre,apellido,email,password,rol_id,plan_id) VALUES (?,?,?,?,?,?)', ['Otra','Prueba',$suffix . '@example.invalid',$hash,$role,$plan], [1062], 'Se rechaza un email duplicado.');
    rejected($pdo, 'INSERT INTO usuarios (nombre,apellido,email,password,rol_id,plan_id) VALUES (?,?,?,?,?,?)', ['Otra','Prueba','invalid-' . $suffix . '@example.invalid',$hash,0,$plan], [1452], 'Se rechaza un rol inexistente.');

    $a = insertRow($pdo, 'INSERT INTO tramites (nombre,slug,categoria_id) VALUES (?,?,?)', ['Prueba A','test-a-' . $suffix,$category]);
    $b = insertRow($pdo, 'INSERT INTO tramites (nombre,slug,categoria_id) VALUES (?,?,?)', ['Prueba B','test-b-' . $suffix,$category]);
    rejected($pdo, 'INSERT INTO tramites (nombre,slug,categoria_id) VALUES (?,?,?)', ['Duplicado','test-a-' . $suffix,$category], [1062], 'Se rechaza un slug duplicado.');
    rejected($pdo, 'UPDATE tramites SET estado_verificacion = ? WHERE id = ?', ['verificado',$a], [4025,3819], 'No se marca como verificado sin fuente y fecha.');
    rejected($pdo, 'UPDATE tramites SET estado = ? WHERE id = ?', ['estado_invalido',$a], [1265,1366], 'Un estado invalido no se convierte silenciosamente.');

    $docA = insertRow($pdo, 'INSERT INTO documentos (nombre) VALUES (?)', ['Documento A ' . $suffix]);
    $docB = insertRow($pdo, 'INSERT INTO documentos (nombre) VALUES (?)', ['Documento B ' . $suffix]);
    insertRow($pdo, 'INSERT INTO tramite_documentos (tramite_id,documento_id) VALUES (?,?)', [$a,$docA]);
    insertRow($pdo, 'INSERT INTO tramite_documentos (tramite_id,documento_id) VALUES (?,?)', [$b,$docB]);
    $progress = insertRow($pdo, 'INSERT INTO progreso_tramites (usuario_id,tramite_id) VALUES (?,?)', [$user,$a]);
    insertRow($pdo, 'INSERT INTO progreso_documentos (progreso_tramite_id,documento_id,completado) VALUES (?,?,1)', [$progress,$docA]);
    verify(true, 'El progreso admite documentos del tramite correcto.');
    rejected($pdo, 'INSERT INTO progreso_documentos (progreso_tramite_id,documento_id) VALUES (?,?)', [$progress,$docB], [1644], 'Se rechaza un documento de otro tramite.');
    rejected($pdo, 'UPDATE progreso_tramites SET tramite_id=? WHERE id=?', [$b,$progress], [1644], 'No se cambia el tramite de un seguimiento con documentos asociados.');

    $stepA = insertRow($pdo, 'INSERT INTO pasos_tramite (tramite_id,numero_paso,titulo) VALUES (?,1,?)', [$a,'Paso A']);
    $stepB = insertRow($pdo, 'INSERT INTO pasos_tramite (tramite_id,numero_paso,titulo) VALUES (?,1,?)', [$b,'Paso B']);
    rejected($pdo, 'INSERT INTO progreso_pasos (progreso_tramite_id,paso_id) VALUES (?,?)', [$progress,$stepB], [1644], 'Se rechaza el seguimiento de pasos de otro tramite.');
    rejected($pdo, 'INSERT INTO paso_dependencias (paso_id,paso_previo_id) VALUES (?,?)', [$stepA,$stepA], [4025,3819], 'Un paso no depende de si mismo.');
    rejected($pdo, 'INSERT INTO paso_dependencias (paso_id,paso_previo_id) VALUES (?,?)', [$stepA,$stepB], [1644], 'Las dependencias no mezclan pasos de tramites diferentes.');

    $cost = insertRow($pdo, 'INSERT INTO costos (tramite_id,concepto) VALUES (?,?)', [$a,'Valor desconocido']);
    $query = $pdo->prepare('SELECT monto FROM costos WHERE id = ?');
    $query->execute([$cost]);
    verify($query->fetch()['monto'] === null, 'Un costo desconocido conserva NULL, no cero.');
    rejected($pdo, 'INSERT INTO costos (tramite_id,concepto,monto) VALUES (?,?,?)', [$a,'Monto invalido',-1], [4025,3819], 'Se rechazan montos negativos.');
    rejected($pdo, 'INSERT INTO paso_costos (paso_id,costo_id) VALUES (?,?)', [$stepB,$cost], [1644], 'Un costo no se asocia con pasos de otro tramite.');

    $institution = insertRow($pdo, 'INSERT INTO instituciones (nombre) VALUES (?)', ['Institucion de prueba ' . $suffix]);
    $branch = insertRow($pdo, 'INSERT INTO sucursales (institucion_id,nombre) VALUES (?,?)', [$institution,'Sucursal de prueba']);
    $otherBranch = insertRow($pdo, 'INSERT INTO sucursales (institucion_id,nombre) VALUES (?,?)', [$institution,'Otra sucursal de prueba']);
    $report = insertRow($pdo, "INSERT INTO reportes_atencion (usuario_id,sucursal_id,fecha,hora,nivel_demanda) VALUES (?,?,CURRENT_DATE(),'10:00:00','media')", [$user,$branch]);
    rejected($pdo, "INSERT INTO historico_atencion (sucursal_id,fecha,hora,personas_esperando,fuente,reporte_id) VALUES (?,CURRENT_DATE(),'10:00:00',5,'DIRI_USUARIOS',?)", [$otherBranch,$report], [1452], 'Un reporte no se atribuye al historico de otra sucursal.');
    rejected($pdo, 'INSERT INTO horarios (sucursal_id,dia_semana,hora_apertura,hora_cierre) VALUES (?,1,?,?)', [$branch,'18:00:00','08:00:00'], [4025,3819], 'Se rechaza un horario invertido.');
    rejected($pdo, "INSERT INTO estado_atencion (sucursal_id,fuente,tiempo_minimo,tiempo_maximo,ultima_actualizacion,valido_hasta) VALUES (?,'ESTIMACION',50,20,UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE))", [$branch], [4025,3819], 'Se rechaza un intervalo de espera invertido.');
    insertRow($pdo, "INSERT INTO estado_atencion (sucursal_id,fuente,tiempo_minimo,tiempo_maximo,ultima_actualizacion,valido_hasta) VALUES (?,'ESTIMACION',20,50,UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE))", [$branch]);
    rejected($pdo, "INSERT INTO estado_atencion (sucursal_id,fuente,ultima_actualizacion,valido_hasta) VALUES (?,'ESTIMACION',UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE))", [$branch], [1062], 'Solo existe un estado actual por sucursal.');
    rejected($pdo, "INSERT INTO estado_atencion (sucursal_id,fuente,ultima_actualizacion,valido_hasta) VALUES (?,'OFICIAL_INVENTADA',UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE))", [$branch], [1265,1366], 'Se rechaza una procedencia fuera del catalogo.');

    $route = insertRow($pdo, 'INSERT INTO rutas (nombre,slug) VALUES (?,?)', ['Ruta de prueba','ruta-test-' . $suffix]);
    insertRow($pdo, 'INSERT INTO ruta_tramites (ruta_id,tramite_id,numero_orden) VALUES (?,?,1)', [$route,$a]);
    rejected($pdo, 'INSERT INTO ruta_dependencias (ruta_id,tramite_id,tramite_previo_id) VALUES (?,?,?)', [$route,$a,$b], [1452], 'Una dependencia de ruta requiere tramites de esa ruta.');
    rejected($pdo, 'DELETE FROM tramites WHERE id = ?', [$a], [1451], 'No se elimina un tramite con progreso y referencias.');

    $pdo->rollBack();
    $query = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE email = ?');
    $query->execute([$suffix . '@example.invalid']);
    verify((int) $query->fetchColumn() === 0, 'Los datos temporales se revierten al terminar.');
    echo "OK: integridad comprobada. No se conservaron datos de prueba.\n";
    exit(0);
} catch (Throwable $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "FAIL: integridad del esquema no confirmada.\n";
    if ($exception instanceof PDOException) {
        echo 'Codigo SQL: ' . ($exception->errorInfo[1] ?? 'desconocido') . "\n";
    } else {
        echo $exception->getMessage() . "\n";
    }
    exit(1);
}

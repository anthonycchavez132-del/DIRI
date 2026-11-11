<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/core/bootstrap.php';
require __DIR__ . '/support/HttpTest.php';

use App\Core\Database;
use App\Models\User;
use App\Models\Tramite;
use App\Models\Documento;
use App\Services\ContenidoService;

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8087', '/');
$url = parse_url($base);
if ($url === false || !in_array($url['scheme'] ?? '', ['http','https'], true) || !in_array($url['host'] ?? '', ['127.0.0.1','localhost','[::1]'], true)
    || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
    echo "ERROR: usa una URL local de desarrollo.\n"; exit(2);
}

function contentVersion(string $html): string
{
    if (!preg_match('/name="version" value="([a-f0-9]{64})"/', $html, $matches)) {
        throw new RuntimeException('Falta la version del contenido.');
    }
    return $matches[1];
}

function authenticateContent(TestBrowser $browser, string $base, string $email, string $password): void
{
    [, $html] = $browser->request($base, '/login');
    [$status] = $browser->request($base, '/login', ['_token' => token($html),'email' => $email,'password' => $password]);
    ensure($status === 303, 'Cuenta temporal autenticada.', false);
}

function publishContent(TestBrowser $browser, string $base, int $parentId, int $sourceId, string $classification = 'referencial'): int
{
    [, $html] = $browser->request($base, '/admin/tramites/editar?id=' . $parentId);
    $data = (new Tramite())->find($parentId);
    $data['estado'] = 'publicado';
    $data['estado_verificacion'] = $classification;
    $data['fuente_id'] = (string) $sourceId;
    $data['fecha_verificacion'] = $classification === 'verificado' ? '2026-01-01' : '';
    $data['_token'] = token($html);
    $data['version'] = contentVersion($html);
    return $browser->request($base, '/admin/tramites/editar', $data)[0];
}

$suffix = bin2hex(random_bytes(12));
$emails = ['content-admin-' . $suffix . '@example.invalid','content-user-' . $suffix . '@example.invalid'];
$password = 'Temporal-' . bin2hex(random_bytes(16));
$users = $parents = $documentIds = [];
$officialSource = null;
$pdo = null;
$admin = new TestBrowser();
$ordinary = new TestBrowser();
$failure = null;

try {
    $pdo = Database::connection();
    [$status] = $admin->request($base, '/admin/documentos');
    ensure($status === 303, 'El catalogo exige sesion.');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    foreach ($emails as $email) {
        $users[] = (new User())->create('Prueba de contenido', 'Temporal', $email, $hash);
    }
    $pdo->prepare("UPDATE usuarios SET rol_id=(SELECT id FROM roles WHERE nombre='administrador') WHERE id=?")->execute([$users[0]]);
    $category = (int) $pdo->query('SELECT id FROM categorias WHERE estado=1 ORDER BY id LIMIT 1')->fetchColumn();
    $source = (int) $pdo->query("SELECT id FROM fuentes WHERE tipo='DIRI' ORDER BY id LIMIT 1")->fetchColumn();
    foreach (['a','b'] as $key) {
        $parents[] = (new Tramite())->create(['nombre'=>'Contenido ' . $key . ' ' . $suffix,'slug'=>'contenido-' . $key . '-' . $suffix,'descripcion'=>'Texto temporal referencial, sin requisitos oficiales.','categoria_id'=>$category,'estado'=>'publicado','dificultad'=>null,'tiempo_estimado'=>null,'fuente_id'=>$source,'estado_verificacion'=>'referencial','fecha_verificacion'=>null]);
    }
    authenticateContent($ordinary, $base, $emails[1], $password);
    $readRoutes = ['/admin/tramites/contenido?tramite_id=' . $parents[0],'/admin/documentos','/admin/requisitos/crear','/admin/requisitos/editar','/admin/documentos/crear','/admin/documentos/editar','/admin/tramites/documentos/asociar','/admin/tramites/documentos/editar'];
    $writeRoutes = ['/admin/requisitos/crear','/admin/requisitos/editar','/admin/documentos/crear','/admin/documentos/editar','/admin/tramites/documentos/asociar','/admin/tramites/documentos/editar'];
    foreach ($readRoutes as $path) { ensure($ordinary->request($base, $path)[0] === 403, 'Lectura restringida.', false); }
    [, $html] = $ordinary->request($base, '/perfil');
    foreach ($writeRoutes as $path) { ensure($ordinary->request($base, $path, ['_token'=>token($html)])[0] === 403, 'Escritura restringida.', false); }
    ensure(true, 'Todos los nuevos endpoints requieren rol administrador.');
    authenticateContent($admin, $base, $emails[0], $password);
    [$status, $html] = $admin->request($base, '/admin/tramites/contenido?tramite_id=' . $parents[0]);
    ensure($status === 200 && strpos($html, 'No se presupone') !== false && strpos($html, 'No hay documentos asociados') !== false, 'Un tramite sin contenido muestra estados vacios honestos.');
    foreach ($writeRoutes as $path) { ensure($admin->request($base, $path, [])[0] === 419, 'CSRF exigido.', false); }
    ensure(true, 'Todos los formularios de contenido exigen CSRF.');
    ensure($admin->request($base, '/admin/tramites/contenido?tramite_id[]=1')[0] === 404, 'El contexto del tramite se valida.');
    [, $html] = $admin->request($base, '/admin/tramites/editar?id=' . $parents[0]);
    $oldParentVersion = contentVersion($html);
    $csrf = token($html);
    $requirement = ['tramite_id'=>(string)$parents[0],'nombre'=>'Requisito <script>alert(1)</script> ' . $suffix,'descripcion'=>'Texto de prueba','obligatorio'=>'1','observaciones'=>'','fuente_id'=>'','estado_verificacion'=>'pendiente','fecha_verificacion'=>'','_token'=>$csrf];
    ensure($admin->request($base, '/admin/requisitos/crear', array_merge($requirement,['obligatorio'=>[]]))[0] === 422, 'No se convierten tipos invalidos en obligatoriedad.');
    ensure($admin->request($base, '/admin/requisitos/crear', array_merge($requirement,['estado_verificacion'=>'verificado','fuente_id'=>(string)$source,'fecha_verificacion'=>'2026-01-01']))[0] === 422, 'Un requisito verificado requiere fuente oficial.');
    ensure($admin->request($base, '/admin/requisitos/crear', $requirement)[0] === 303, 'Se crea un requisito pendiente sin inventar evidencia.');
    $query = $pdo->prepare('SELECT * FROM requisitos WHERE tramite_id=?');
    $query->execute([$parents[0]]); $req = $query->fetch(); $reqId = (int) $req['id'];
    $parent = (new Tramite())->find($parents[0]);
    ensure($parent['estado'] === 'borrador' && $parent['estado_verificacion'] === 'pendiente' && (int)$parent['revision'] > 1, 'Cambiar contenido retira la publicacion y aumenta la revision.');
    $oldParent = array_merge($parent, ['_token'=>$csrf,'version'=>$oldParentVersion]);
    ensure($admin->request($base, '/admin/tramites/editar', $oldParent)[0] === 409, 'Un formulario general anterior al cambio de contenido queda obsoleto.');
    ensure(publishContent($admin,$base,$parents[0],$source) === 422, 'No se publica un tramite con requisitos pendientes.');
    ensure($admin->request($base, '/admin/requisitos/editar?tramite_id=' . $parents[1] . '&id=' . $reqId)[0] === 404, 'Un requisito no se edita bajo el contexto de otro tramite.');
    [, $html] = $admin->request($base, '/admin/requisitos/editar?tramite_id=' . $parents[0] . '&id=' . $reqId);
    $reqVersion = contentVersion($html);
    ensure(strpos($html,'&lt;script&gt;') !== false && strpos($html,'<script>alert(1)</script>') === false, 'Los requisitos se muestran escapados y el formulario conserva sus datos.');
    $reviewedReq = array_merge($requirement,['id'=>(string)$reqId,'version'=>$reqVersion,'obligatorio'=>'0','descripcion'=>'0','estado_verificacion'=>'referencial','fuente_id'=>(string)$source]);
    ensure($admin->request($base, '/admin/requisitos/editar', $reviewedReq)[0] === 303, 'Se edita el requisito con fuente y obligatoriedad explicita.');
    ensure($admin->request($base, '/admin/requisitos/editar', array_merge($reviewedReq,['nombre'=>'Version antigua']))[0] === 409, 'Un requisito con version antigua no sobrescribe cambios.');
    $query->execute([$parents[0]]); $req = $query->fetch();
    ensure($req['descripcion'] === '0' && (int)$req['obligatorio'] === 0, 'Se preservan el texto cero y el indicador opcional.');

    $docData = ['nombre'=>'Documento compartido <script>alert(1)</script> ' . $suffix,'descripcion'=>'Descripcion compartida','fuente_id'=>(string)$source,'fecha_verificacion'=>'','_token'=>$csrf];
    ensure($admin->request($base, '/admin/documentos/crear', array_merge($docData,['fecha_verificacion'=>'2999-01-01']))[0] === 422, 'Los documentos rechazan fechas futuras sin errores internos.');
    ensure($admin->request($base, '/admin/documentos/crear', $docData)[0] === 303, 'Se crea un documento reutilizable.');
    $query = $pdo->prepare('SELECT id FROM documentos WHERE nombre=?'); $query->execute([$docData['nombre']]);
    $documentId = (int)$query->fetchColumn(); $documentIds[] = $documentId;
    ensure($admin->request($base, '/admin/documentos/crear', $docData)[0] === 422, 'El nombre del documento es unico.');
    $association = ['tramite_id'=>(string)$parents[0],'documento_id'=>(string)$documentId,'obligatorio'=>'0','cantidad'=>'','presentacion'=>'','observaciones'=>'Condicion A','fuente_id'=>'','fecha_verificacion'=>'','_token'=>$csrf];
    ensure($admin->request($base, '/admin/tramites/documentos/asociar', array_merge($association,['cantidad'=>'-1']))[0] === 422, 'Una cantidad negativa se rechaza.');
    ensure($admin->request($base, '/admin/tramites/documentos/asociar', array_merge($association,['presentacion'=>'inventada']))[0] === 422, 'La presentacion se limita a valores validos.');
    ensure($admin->request($base, '/admin/tramites/documentos/asociar', $association)[0] === 303, 'Se asocia el documento con condiciones aun sin verificar.');
    ensure($admin->request($base, '/admin/tramites/documentos/asociar', $association)[0] === 422, 'No se duplica una asociacion existente.');
    $query = $pdo->prepare('SELECT * FROM tramite_documentos WHERE tramite_id=? AND documento_id=?');
    $query->execute([$parents[0],$documentId]); $linkA = $query->fetch();
    ensure($linkA['cantidad'] === null && $linkA['presentacion'] === null && $linkA['fuente_id'] === null && (int)$linkA['obligatorio'] === 0, 'Las condiciones desconocidas conservan NULL.');
    ensure(publishContent($admin,$base,$parents[0],$source) === 422, 'No se publica sin fuente para las condiciones del documento.');
    $associationB = array_merge($association,['tramite_id'=>(string)$parents[1],'cantidad'=>'2','presentacion'=>'copia','obligatorio'=>'1','observaciones'=>'Condicion B','fuente_id'=>(string)$source]);
    ensure($admin->request($base, '/admin/tramites/documentos/asociar', $associationB)[0] === 303, 'El mismo documento se reutiliza en otro tramite.');
    [, $html] = $admin->request($base, '/admin/tramites/documentos/editar?tramite_id=' . $parents[0] . '&documento_id=' . $documentId);
    $associationA = array_merge($association,['version'=>contentVersion($html),'cantidad'=>'3','presentacion'=>'original','fuente_id'=>(string)$source]);
    ensure($admin->request($base, '/admin/tramites/documentos/editar', $associationA)[0] === 303, 'Se editan solo las condiciones de un tramite.');
    $query->execute([$parents[1],$documentId]); $linkB = $query->fetch();
    ensure((int)$linkB['cantidad'] === 2 && $linkB['presentacion'] === 'copia' && (int)$linkB['obligatorio'] === 1, 'Las condiciones del segundo tramite no cambian.');
    ensure($admin->request($base, '/admin/tramites/documentos/editar', array_merge($associationA,['cantidad'=>'4']))[0] === 409, 'Las condiciones tambien detectan versiones antiguas.');

    $pdo->prepare('INSERT INTO progreso_tramites (usuario_id,tramite_id) VALUES (?,?)')->execute([$users[1],$parents[0]]);
    $progressId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO progreso_documentos (progreso_tramite_id,documento_id,completado) VALUES (?,?,1)')->execute([$progressId,$documentId]);
    ensure(publishContent($admin,$base,$parents[0],$source) === 303 && publishContent($admin,$base,$parents[1],$source) === 303, 'El contenido referencial revisado permite republicar.');
    [, $html] = $admin->request($base, '/admin/documentos/editar?id=' . $documentId);
    ensure(strpos($html,'2') !== false && strpos($html,'compartida') !== false, 'Editar un documento informa su alcance compartido.');
    $docVersion = contentVersion($html);
    $changedDoc = array_merge($docData,['id'=>(string)$documentId,'version'=>$docVersion,'nombre'=>'Documento revisado ' . $suffix,'descripcion'=>'0','fuente_id'=>(string)$source]);
    ensure($admin->request($base, '/admin/documentos/editar', $changedDoc)[0] === 303, 'Se actualiza la informacion compartida del documento.');
    ensure((new Tramite())->find($parents[0])['estado'] === 'borrador' && (new Tramite())->find($parents[1])['estado'] === 'borrador', 'Cambiar un documento compartido exige revisar ambos tramites.');
    $query->execute([$parents[0],$documentId]); $afterA = $query->fetch();
    $query->execute([$parents[1],$documentId]); $afterB = $query->fetch();
    ensure((int)$afterA['cantidad'] === 3 && $afterA['presentacion'] === 'original' && (int)$afterB['cantidad'] === 2 && $afterB['presentacion'] === 'copia', 'Editar el catalogo conserva las condiciones particulares.');
    $progressQuery = $pdo->prepare('SELECT completado FROM progreso_documentos WHERE progreso_tramite_id=? AND documento_id=?');
    $progressQuery->execute([$progressId,$documentId]);
    ensure((int)$progressQuery->fetchColumn() === 1, 'Las ediciones conservan el progreso del usuario.');
    ensure($admin->request($base, '/admin/documentos/editar', array_merge($changedDoc,['nombre'=>'Version antigua ' . $suffix]))[0] === 409, 'El catalogo compartido evita sobrescribir una version nueva.');

    ensure(publishContent($admin,$base,$parents[0],$source) === 303 && publishContent($admin,$base,$parents[1],$source) === 303, 'Los tramites pueden revisarse y republicarse tras un cambio compartido.');
    [, $html] = $admin->request($base, '/admin/documentos/editar?id=' . $documentId);
    $unchangedDoc = array_merge($changedDoc,['version'=>contentVersion($html)]);
    ensure($admin->request($base, '/admin/documentos/editar', $unchangedDoc)[0] === 303 && (new Tramite())->find($parents[1])['estado'] === 'publicado', 'Guardar sin cambios no retira una publicacion.');

    $pdo->prepare("INSERT INTO fuentes (nombre,tipo,url) VALUES (?,'oficial','https://example.invalid/contenido')")->execute(['Fuente simulada de contenido ' . $suffix]);
    $officialSource = (int)$pdo->lastInsertId();
    [, $html] = $admin->request($base, '/admin/requisitos/editar?tramite_id=' . $parents[0] . '&id=' . $reqId);
    $verifiedReq = array_merge($reviewedReq,['version'=>contentVersion($html),'estado_verificacion'=>'verificado','fuente_id'=>(string)$officialSource,'fecha_verificacion'=>'2026-01-01']);
    ensure($admin->request($base, '/admin/requisitos/editar', $verifiedReq)[0] === 303 && (new Tramite())->find($parents[1])['estado'] === 'publicado', 'Editar un requisito afecta solo a su tramite.');
    ensure(publishContent($admin,$base,$parents[0],$officialSource,'verificado') === 422, 'No se declara oficial un conjunto con condiciones documentales referenciales.');
    [, $html] = $admin->request($base, '/admin/tramites/documentos/editar?tramite_id=' . $parents[0] . '&documento_id=' . $documentId);
    $verifiedLink = array_merge($associationA,['version'=>contentVersion($html),'fuente_id'=>(string)$officialSource,'fecha_verificacion'=>'2026-01-01']);
    ensure($admin->request($base, '/admin/tramites/documentos/editar', $verifiedLink)[0] === 303 && publishContent($admin,$base,$parents[0],$officialSource,'verificado') === 422, 'El documento compartido tambien requiere verificacion antes de declarar oficial el conjunto.');
    [, $html] = $admin->request($base, '/admin/documentos/editar?id=' . $documentId);
    $verifiedDocument = array_merge($changedDoc,['version'=>contentVersion($html),'fuente_id'=>(string)$officialSource,'fecha_verificacion'=>'2026-01-01']);
    ensure($admin->request($base, '/admin/documentos/editar', $verifiedDocument)[0] === 303 && publishContent($admin,$base,$parents[0],$officialSource,'verificado') === 303, 'Publicar como verificado exige respaldo de todo el contenido.');

    (new Tramite())->deactivate($parents[1]);
    [, $html] = $admin->request($base, '/admin/documentos/editar?id=' . $documentId);
    $finalDoc = array_merge($changedDoc,['version'=>contentVersion($html),'descripcion'=>'Nueva descripcion temporal']);
    ensure($admin->request($base, '/admin/documentos/editar', $finalDoc)[0] === 303 && (new Tramite())->find($parents[1])['estado'] === 'inactivo', 'Revisar contenido no reactiva un tramite inactivo.');
    $beforeDoc = (new Documento())->find($documentId);
    $beforeParent = (new Tramite())->find($parents[0]);
    $rollback = false;
    try {
        (new ContenidoService())->documento($documentId, ['nombre'=>'No debe guardarse ' . $suffix,'descripcion'=>'Prueba','fuente_id'=>$source,'fecha_verificacion'=>null], ContenidoService::version($beforeDoc), 0);
    } catch (PDOException $exception) { $rollback = (int)($exception->errorInfo[1] ?? 0) === 1452; }
    ensure($rollback && (new Documento())->find($documentId) === $beforeDoc && (new Tramite())->find($parents[0]) === $beforeParent, 'Un fallo de auditoria revierte documento y revisiones de los padres.');
    $audit = $pdo->prepare("SELECT COUNT(DISTINCT entidad) FROM auditoria_administrativa WHERE usuario_id=? AND entidad IN ('requisitos','documentos','tramite_documentos','tramites')");
    $audit->execute([$users[0]]);
    ensure((int)$audit->fetchColumn() === 4, 'Requisitos, documentos, asociaciones y revisiones quedan auditados.');
} catch (Throwable $exception) { $failure = $exception; }
finally {
    if ($pdo instanceof PDO) {
        $pdo->beginTransaction();
        foreach ($users as $id) {
            $pdo->prepare('DELETE FROM auditoria_administrativa WHERE usuario_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM progreso_tramites WHERE usuario_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$id]);
        }
        foreach ($parents as $id) {
            $pdo->prepare('DELETE FROM tramite_documentos WHERE tramite_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM requisitos WHERE tramite_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM tramites WHERE id=?')->execute([$id]);
        }
        foreach ($documentIds as $id) { $pdo->prepare('DELETE FROM documentos WHERE id=?')->execute([$id]); }
        if ($officialSource !== null) { $pdo->prepare('DELETE FROM fuentes WHERE id=?')->execute([$officialSource]); }
        $pdo->commit();
    }
    foreach ([$admin->ip,$ordinary->ip] as $ip) {
        foreach ($emails as $email) {
            $file = BASE_PATH . '/storage/cache/login-' . hash('sha256',$email . '|' . $ip) . '.json';
            if (is_file($file)) { unlink($file); }
        }
    }
}
if ($failure !== null) {
    echo 'FAIL: ' . ($failure instanceof PDOException ? 'Error SQL en contenido, codigo ' . ($failure->errorInfo[1] ?? 'desconocido') : $failure->getMessage()) . "\n"; exit(1);
}
echo "OK: requisitos y documentos comprobados, fixtures eliminados.\n";

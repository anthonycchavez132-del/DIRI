<?php

declare(strict_types=1);

// Instalación local: no ofrece un endpoint web ni crea contraseñas.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/core/bootstrap.php';

use App\Core\Database;
use App\Services\AuthValidator;

if ($argc !== 2 || !AuthValidator::emailValid(AuthValidator::email(['email' => $argv[1] ?? '']))) {
    echo "Uso: php scripts/enable-admin.php correo-de-tu-cuenta\n";
    exit(2);
}
$pdo = null;
try {
    $pdo = Database::connection();
    $pdo->beginTransaction();
    // Serializa dos intentos simultáneos de configurar el primer administrador.
    $role = $pdo->query("SELECT id FROM roles WHERE nombre = 'administrador' FOR UPDATE")->fetchColumn();
    if ($role === false) {
        throw new DomainException('No existe el rol administrador. Revisa la instalación.');
    }
    $query = $pdo->prepare('SELECT id, rol_id, estado FROM usuarios WHERE email = ? FOR UPDATE');
    $query->execute([AuthValidator::email(['email' => $argv[1]])]);
    $user = $query->fetch();
    if ($user === false || $user['estado'] !== 'activo') {
        throw new DomainException('Primero registra una cuenta activa con ese correo.');
    }
    if ((int) $user['rol_id'] === (int) $role) {
        $pdo->commit();
        echo "OK: la cuenta ya es administradora.\n";
        exit(0);
    }
    $count = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = ? AND estado = 'activo'");
    $count->execute([$role]);
    if ((int) $count->fetchColumn() > 0) {
        throw new DomainException('Ya existe un administrador activo. Este comando solo configura el primero.');
    }
    $pdo->prepare('UPDATE usuarios SET rol_id = ? WHERE id = ?')->execute([$role,$user['id']]);
    $pdo->commit();
    echo "OK: primer administrador habilitado. Su contraseña y plan no cambiaron.\n";
    exit(0);
} catch (Throwable $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo 'ERROR: ' . ($exception instanceof DomainException ? $exception->getMessage() : 'No se pudo habilitar la cuenta. Revisa la conexión y la instalación.') . "\n";
    exit(1);
}

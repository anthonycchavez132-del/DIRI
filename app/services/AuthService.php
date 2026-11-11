<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\User;

class AuthService
{
    private static bool $loaded = false;
    private static ?array $user = null;

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = $_SESSION['user_id'] ?? null;
            if (is_int($id) && $id > 0) {
                self::$user = (new User())->findActive($id);
                // Una cuenta bloqueada o desactivada pierde acceso en la siguiente solicitud.
                if (self::$user === null) {
                    unset($_SESSION['user_id']);
                    Session::renew();
                }
            }
        }
        return self::$user;
    }

    public function login(string $email, string $password): bool
    {
        $model = new User();
        $user = $model->findForLogin($email);
        // Hash ficticio válido para mantener una verificación incluso si no existe el correo.
        $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $valid = password_verify($password, $user['password'] ?? $dummyHash);
        if (!$valid || $user === null || $user['estado'] !== 'activo') {
            return false;
        }
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $model->updateHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Session::renew();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['created_at'] = time();
        self::$loaded = false;
        self::$user = null;
        return true;
    }

    public function register(array $data): void
    {
        (new User())->create($data['nombre'], $data['apellido'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT));
    }

    public function logout(): void
    {
        Session::destroy();
        self::$loaded = true;
        self::$user = null;
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        global $config;
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        session_name('DIRISESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => ($config['base_path'] ?: '') . '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (!session_start()) {
            throw new \RuntimeException('No se pudo iniciar la sesión.');
        }

        $now = time();
        // Caducidad de 30 minutos sin actividad o 12 horas de duración total.
        if (isset($_SESSION['last_activity'], $_SESSION['created_at'])
            && ($now - $_SESSION['last_activity'] > 1800 || $now - $_SESSION['created_at'] > 43200)) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['created_at'] ??= $now;
        $_SESSION['last_activity'] = $now;
    }

    public static function renew(): void
    {
        if (!session_regenerate_id(true)) {
            throw new \RuntimeException('No se pudo renovar la sesión.');
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_destroy();
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

class LoginLimiter
{
    // Cinco verificaciones por pareja correo/IP cada quince minutos.
    // Se guarda fuera de public/ y no depende de la cookie de sesión.
    public static function consume(string $email, string $ip): bool
    {
        return self::update($email, $ip, false);
    }

    public static function reset(string $email, string $ip): void
    {
        self::update($email, $ip, true);
    }

    private static function update(string $email, string $ip, bool $reset): bool
    {
        $file = BASE_PATH . '/storage/cache/login-' . hash('sha256', $email . '|' . $ip) . '.json';
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            throw new \RuntimeException('No se pudo comprobar el límite de acceso.');
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('No se pudo bloquear el contador de acceso.');
            }
            $state = json_decode(stream_get_contents($handle), true);
            $now = time();
            if (!is_array($state) || !isset($state['expires'], $state['count']) || $state['expires'] <= $now || $reset) {
                $state = ['expires' => $now + 900, 'count' => 0];
            }
            if (!$reset && $state['count'] >= 5) {
                return false;
            }
            if (!$reset) {
                $state['count']++;
            }
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, json_encode($state, JSON_THROW_ON_ERROR)) === false || !fflush($handle)) {
                throw new \RuntimeException('No se pudo actualizar el contador de acceso.');
            }
            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}

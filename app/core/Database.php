<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static array $connections = [];

    public static function connection(bool $selectDatabase = true): PDO
    {
        $key = $selectDatabase ? 'database' : 'server';
        if (isset(self::$connections[$key])) {
            return self::$connections[$key];
        }

        $file = BASE_PATH . '/config/database.php';
        if (!is_file($file)) {
            throw new RuntimeException('Falta config/database.php. Copia y configura database.example.php.');
        }

        $settings = require $file;
        self::validateSettings($settings);

        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('La extensión pdo_mysql no está habilitada.');
        }

    
        $dsn = 'mysql:host=' . $settings['host'] . ';port=' . $settings['port'] . ';charset=utf8mb4';
        if ($selectDatabase) {
            $dsn .= ';dbname=' . $settings['database'];
        }

        try {
            $connection = new PDO($dsn, $settings['username'], $settings['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_TIMEOUT => 5,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
                // Evita conversiones silenciosas y guarda marcas de tiempo en UTC.
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION', time_zone = '+00:00'",
            ]);
        } catch (PDOException $exception) {
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            $message = match ($driverCode) {
                1049 => 'La base de datos configurada no existe. Revisa la configuración o instala database/diri.sql en una base nueva.',
                1045, 1044 => 'Acceso denegado. Revisa las credenciales y permisos en config/database.php.',
                2002, 2003 => 'No se pudo contactar a MariaDB/MySQL. Revisa el servicio, host y puerto.',
                default => 'No se pudo establecer la conexión con MariaDB/MySQL.',
            };
            throw new RuntimeException($message, $driverCode);
        }

        self::$connections[$key] = $connection;
        return $connection;
    }

    private static function validateSettings($settings): void
    {
        if (!is_array($settings)) {
            throw new RuntimeException('La configuración de base de datos debe devolver un arreglo.');
        }

        foreach (['host', 'database', 'username', 'password'] as $field) {
            if (!isset($settings[$field]) || !is_string($settings[$field])) {
                throw new RuntimeException('La configuración de base de datos está incompleta.');
            }
        }

        if (!preg_match('/\A[a-zA-Z0-9_.:\-]+\z/', $settings['host'])
            || !preg_match('/\A[a-zA-Z0-9_]+\z/', $settings['database'])
            || trim($settings['username']) === ''
            || !isset($settings['port']) || !is_int($settings['port'])
            || $settings['port'] < 1 || $settings['port'] > 65535) {
            throw new RuntimeException('Revisa el host, puerto, nombre de base y usuario en config/database.php.');
        }
    }
}

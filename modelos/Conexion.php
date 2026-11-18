<?php

declare(strict_types=1);

final class Conexion
{
    private static ?PDO $conexion = null;

    public static function obtener(): PDO
    {
        if (self::$conexion === null) {
            self::$conexion = new PDO(
                'mysql:host=127.0.0.1;port=3306;dbname=diri2;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }


        $usuarioId = $_SESSION['usuario_id'] ?? null;
        $consulta = self::$conexion->prepare('SET @usuario_id = :usuario_id');
        $consulta->bindValue(
            ':usuario_id',
            $usuarioId,
            is_int($usuarioId) ? PDO::PARAM_INT : PDO::PARAM_NULL
        );
        $consulta->execute();

        return self::$conexion;
    }
}

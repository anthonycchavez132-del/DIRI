<?php

declare(strict_types=1);

namespace App\Services;

class AuthValidator
{
    public static function text(array $input, string $key): string
    {
        $value = $input[$key] ?? '';
        return is_string($value) && mb_check_encoding($value, 'UTF-8') ? trim($value) : '';
    }

    public static function email(array $input): string
    {
        return strtolower(self::text($input, 'email'));
    }

    public static function password(array $input): string
    {
        // La contraseña no se recorta ni se normaliza.
        return isset($input['password']) && is_string($input['password']) ? $input['password'] : '';
    }

    public static function emailValid(string $email): bool
    {
        return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function passwordValid(string $password): bool
    {
        // PHP 8.0 usa bcrypt por defecto: no aceptar truncamientos por encima de 72 bytes.
        return mb_check_encoding($password, 'UTF-8') && mb_strlen($password) >= 8
            && strlen($password) <= 72 && strpos($password, "\0") === false;
    }

    public static function registration(array $input): array
    {
        $data = [
            'nombre' => self::text($input, 'nombre'),
            'apellido' => self::text($input, 'apellido'),
            'email' => self::email($input),
            'password' => self::password($input),
        ];
        $errors = [];
        foreach (['nombre' => 'nombre', 'apellido' => 'apellido'] as $key => $label) {
            if (mb_strlen($data[$key]) < 1 || mb_strlen($data[$key]) > 100
                || !preg_match("/\A[\p{L}\p{M}][\p{L}\p{M} .’'\-]*\z/u", $data[$key])) {
                $errors[$key] = 'Escribe tu ' . $label . ' usando entre 1 y 100 caracteres de texto.';
            }
        }
        if (!self::emailValid($data['email'])) {
            $errors['email'] = 'Escribe un correo electrónico válido de hasta 254 caracteres.';
        }
        if (!self::passwordValid($data['password'])) {
            $errors['password'] = 'Usa entre 8 y 72 caracteres; si incluyes tildes o emojis, el máximo puede ser menor.';
        }
        $confirmation = $input['password_confirmation'] ?? null;
        if (!is_string($confirmation) || $confirmation !== $data['password']) {
            $errors['password_confirmation'] = 'Las contraseñas deben coincidir.';
        }
        return [$data, $errors];
    }
}

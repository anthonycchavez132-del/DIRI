<?php

declare(strict_types=1);

use App\Services\AuthValidator;

return [
    'Normaliza el correo quitando espacios y convirtiendo a minusculas' => static function ($assertSame): void {
        $assertSame('persona@example.com', AuthValidator::email(['email' => '  Persona@Example.COM  ']));
    },
    'Rechaza un correo sin dominio' => static function ($assertSame): void {
        $assertSame(false, AuthValidator::emailValid('persona@'));
    },
    'Rechaza un correo mayor de 254 bytes' => static function ($assertSame): void {
        $assertSame(false, AuthValidator::emailValid(str_repeat('a', 255) . '@example.com'));
    },
    'Conserva los espacios de la contrasena' => static function ($assertSame): void {
        $assertSame(' clave segura ', AuthValidator::password(['password' => ' clave segura ']));
    },
    'Acepta una contrasena de ocho caracteres' => static function ($assertSame): void {
        $assertSame(true, AuthValidator::passwordValid('Abc12345'));
    },
    'Rechaza una contrasena de siete caracteres' => static function ($assertSame): void {
        $assertSame(false, AuthValidator::passwordValid('Abc1234'));
    },
    'Acepta el limite de 72 bytes de contrasena' => static function ($assertSame): void {
        $assertSame(true, AuthValidator::passwordValid(str_repeat('a', 72)));
    },
    'Rechaza una contrasena de 73 bytes' => static function ($assertSame): void {
        $assertSame(false, AuthValidator::passwordValid(str_repeat('a', 73)));
    },
    'Aplica el limite de bytes a caracteres Unicode' => static function ($assertSame): void {
        $assertSame(true, AuthValidator::passwordValid(str_repeat('á', 36)));
        $assertSame(false, AuthValidator::passwordValid(str_repeat('á', 37)));
    },
    'Rechaza bytes nulos y codificacion invalida' => static function ($assertSame): void {
        $assertSame(false, AuthValidator::passwordValid("Abc12345\0"));
        $assertSame(false, AuthValidator::passwordValid("Abc12345\xFF"));
    },
    'Acepta un registro con nombres Unicode y correo normalizado' => static function ($assertSame): void {
        [$data, $errors] = AuthValidator::registration([
            'nombre' => ' José ', 'apellido' => ' Peñarrieta Peña ',
            'email' => ' PERSONA@example.com ', 'password' => 'Clave1234',
            'password_confirmation' => 'Clave1234',
        ]);
        $assertSame([], $errors);
        $assertSame('José', $data['nombre']);
        $assertSame('Peñarrieta Peña', $data['apellido']);
        $assertSame('persona@example.com', $data['email']);
    },
    'Exige que la confirmacion de contrasena coincida' => static function ($assertSame): void {
        [, $errors] = AuthValidator::registration([
            'nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@example.com',
            'password' => 'Clave1234', 'password_confirmation' => 'Otra1234',
        ]);
        $assertSame(['password_confirmation'], array_keys($errors));
    },
    'Marca los campos obligatorios de un registro vacio' => static function ($assertSame): void {
        [, $errors] = AuthValidator::registration([]);
        $assertSame(['nombre', 'apellido', 'email', 'password', 'password_confirmation'], array_keys($errors));
    },
    'Rechaza estructuras de formulario donde se espera texto' => static function ($assertSame): void {
        $assertSame('', AuthValidator::text(['nombre' => ['Ana']], 'nombre'));
        $assertSame('', AuthValidator::password(['password' => ['Clave1234']]));
    },
];

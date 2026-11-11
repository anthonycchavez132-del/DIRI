<?php

declare(strict_types=1);

use App\Services\AuthValidator;

return [
    'Rechaza un correo sin dominio' => static function ($assertSame): void {
        $assertSame(false, AuthValidator::emailValid('persona@'));
    },
    'Exige que la confirmacion de contrasena coincida' => static function ($assertSame): void {
        [, $errors] = AuthValidator::registration([
            'nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@example.com',
            'password' => 'Clave1234', 'password_confirmation' => 'Otra1234',
        ]);
        $assertSame(['password_confirmation'], array_keys($errors));
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
    'Rechaza una contrasena de siete caracteres' => static function ($assertSame): void {
        $assertSame(false, AuthValidator::passwordValid('Abc1234'));
    },
];

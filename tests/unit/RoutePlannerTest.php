<?php

declare(strict_types=1);

use App\Services\RoutePlanner;

return [
    'Una ruta vacia produce una lista vacia' => static function ($assertSame): void {
        $assertSame([], RoutePlanner::order([], []));
    },
    'Conserva el orden editorial cuando no hay dependencias' => static function ($assertSame): void {
        $nodes = [['id' => 3], ['id' => 1], ['id' => 2]];
        $assertSame($nodes, RoutePlanner::order($nodes, []));
    },
    'Ubica certificado medico y habilidades antes de la solicitud final' => static function ($assertSame): void {
        $nodes = [
            ['id' => 3, 'nombre' => 'Solicitud final'],
            ['id' => 1, 'nombre' => 'Certificado médico'],
            ['id' => 2, 'nombre' => 'Certificado de habilidades'],
        ];
        $ordered = RoutePlanner::order($nodes, [[3, 1], [3, 2]]);
        $assertSame([1, 2, 3], array_column($ordered, 'id'));
        $assertSame('Certificado médico', $ordered[0]['nombre']);
    },
    'Ordena una cadena completa de dependencias' => static function ($assertSame): void {
        $ordered = RoutePlanner::order([['id' => 3], ['id' => 2], ['id' => 1]], [[3, 2], [2, 1]]);
        $assertSame([1, 2, 3], array_column($ordered, 'id'));
    },
    'Admite una columna de identificador personalizada' => static function ($assertSame): void {
        $ordered = RoutePlanner::order([['paso_id' => 2], ['paso_id' => 1]], [[2, 1]], 'paso_id');
        $assertSame([1, 2], array_column($ordered, 'paso_id'));
    },
    'Rechaza dependencias hacia pasos ajenos a la ruta' => static function ($assertSame): void {
        try {
            RoutePlanner::order([['id' => 1]], [[1, 99]]);
        } catch (DomainException $exception) {
            $assertSame(422, $exception->getCode());
            $assertSame('Dependencia fuera de la ruta.', $exception->getMessage());
            return;
        }
        throw new RuntimeException('Se esperaba rechazar el paso ajeno.');
    },
    'Detecta ciclos entre pasos' => static function ($assertSame): void {
        try {
            RoutePlanner::order([['id' => 1], ['id' => 2]], [[1, 2], [2, 1]]);
        } catch (DomainException $exception) {
            $assertSame(422, $exception->getCode());
            $assertSame('Las dependencias forman un ciclo.', $exception->getMessage());
            return;
        }
        throw new RuntimeException('Se esperaba rechazar el ciclo.');
    },
    'Detecta un paso que depende de si mismo' => static function ($assertSame): void {
        try {
            RoutePlanner::order([['id' => 1]], [[1, 1]]);
        } catch (DomainException $exception) {
            $assertSame(422, $exception->getCode());
            return;
        }
        throw new RuntimeException('Se esperaba rechazar la dependencia propia.');
    },
];

<?php

declare(strict_types=1);

namespace App\Services;

class LicenseProviderService
{
    public const ZONES = ['todas' => 'Cualquier zona de La Paz', 'centro' => 'Centro', 'norte' => 'Zona Norte',
        'san-pedro' => 'San Pedro', 'sopocachi' => 'Sopocachi', 'miraflores' => 'Miraflores',
        'calacoto' => 'Calacoto', 'achumani' => 'Achumani', 'irpavi' => 'Irpavi',
        'chasquipampa' => 'Chasquipampa', 'bajo-llojeta' => 'Bajo Llojeta',
        'villa-dolores' => 'Villa Dolores', 'pura-pura' => 'Pura Pura'];

    public function data(): array
    {
        return json_decode(file_get_contents(BASE_PATH . '/database/catalogo/proveedores-licencia-lapaz-20261021.json'), true, 32, JSON_THROW_ON_ERROR);
    }

    public function options(string $type, string $zone): array
    {
        $rows = array_values(array_filter($this->data()['providers'], static fn($row) => $row['type'] === $type));
        foreach ($rows as &$row) {
            $row['matches_zone'] = $zone !== 'todas' && $row['zone'] === $zone;
        }
        unset($row);
        usort($rows, static fn($a, $b) => ((int)$b['matches_zone'] <=> (int)$a['matches_zone'])
            ?: strcmp($a['name'], $b['name']) ?: strcmp($a['id'], $b['id']));
        return $rows;
    }
}

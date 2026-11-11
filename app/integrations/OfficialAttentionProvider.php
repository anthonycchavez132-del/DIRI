<?php

declare(strict_types=1);
namespace App\Integrations;
/** Implementar únicamente después de obtener documentación y autorización. */
interface OfficialAttentionProvider
{
    /** @return OfficialAttentionReading[] Sin cuerpos privados ni datos personales. */
    public function fetch(array $integration,?string $token): array;
}

<?php

declare(strict_types=1);
namespace App\Integrations;
use DomainException;
/** Lista local explícita: una fila de MariaDB no puede instanciar código. */
class IntegrationRegistry
{
    private array $providers;
    public function __construct(array $providers=[])
    {
        foreach($providers as $id=>$provider) {
            if(!is_int($id)||$id<1||!$provider instanceof OfficialAttentionProvider) {
                throw new \InvalidArgumentException('Registro de proveedores inválido.');
            }
        }
        $this->providers=$providers;
    }
    public static function configured(): self
    {
        $path=BASE_PATH.'/config/integrations.php';
        return new self(is_file($path)?require $path:[]);
    }
    public function provider(int $id): OfficialAttentionProvider
    {
        if(!isset($this->providers[$id])) {
            throw new DomainException('No hay adaptador autorizado registrado para esta integración.',409);
        }
        return $this->providers[$id];
    }
}

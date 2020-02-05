<?php

namespace Sulu\Bundle\PreviewBundle\Preview\Object;

use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;

class PreviewObjectProviderPool implements PreviewObjectProviderPoolInterface
{
    /**
     * @var PreviewObjectProviderInterface[]
     */
    private $objectProviders;

    /**
     * @param PreviewObjectProviderInterface[] $objectProviders
     */
    public function __construct(array $objectProviders)
    {
        $this->objectProviders = $objectProviders;
    }

    /**
     * @return PreviewObjectProviderInterface[]
     */
    public function getObjectProviders(): array
    {
        return $this->objectProviders;
    }

    public function getObjectProvider(string $providerKey): PreviewObjectProviderInterface
    {
        if (!$this->hasObjectProvider($providerKey)) {
            throw new ProviderNotFoundException($providerKey);
        }
        
        return $this->objectProviders[$providerKey];
    }

    public function hasObjectProvider(string $providerKey): bool
    {
        return array_key_exists($providerKey, $this->objectProviders);
    }
}

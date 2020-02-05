<?php

namespace Sulu\Bundle\PreviewBundle\Preview\Object;

interface PreviewObjectProviderPoolInterface
{
    /**
     * @return PreviewObjectProviderInterface[]
     */
    public function getObjectProviders(): array;

    public function getObjectProvider(string $providerKey): PreviewObjectProviderInterface;
    
    public function hasObjectProvider(string $providerKey): bool;
}

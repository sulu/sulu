<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Object;

use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Provider\PreviewDefaultsProviderInterface;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
class PreviewObjectProviderRegistry implements PreviewObjectProviderRegistryInterface
{
    /**
     * @param array<string, PreviewDefaultsProviderInterface> $previewObjectProviders
     */
    public function __construct(private array $previewObjectProviders)
    {
    }

    public function getPreviewObjectProviders(): array
    {
        return $this->previewObjectProviders;
    }

    public function getPreviewObjectProvider(string $providerKey): PreviewDefaultsProviderInterface
    {
        if (!$this->hasPreviewObjectProvider($providerKey)) {
            throw new ProviderNotFoundException($providerKey);
        }

        return $this->previewObjectProviders[$providerKey];
    }

    public function hasPreviewObjectProvider(string $providerKey): bool
    {
        return \array_key_exists($providerKey, $this->previewObjectProviders);
    }
}

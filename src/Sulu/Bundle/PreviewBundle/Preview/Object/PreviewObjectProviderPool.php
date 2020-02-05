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
     * {@inheritdoc}
     */
    public function getObjectProviders(): array
    {
        return $this->objectProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectProvider(string $providerKey): PreviewObjectProviderInterface
    {
        if (!$this->hasObjectProvider($providerKey)) {
            throw new ProviderNotFoundException($providerKey);
        }

        return $this->objectProviders[$providerKey];
    }

    /**
     * {@inheritdoc}
     */
    public function hasObjectProvider(string $providerKey): bool
    {
        return array_key_exists($providerKey, $this->objectProviders);
    }
}

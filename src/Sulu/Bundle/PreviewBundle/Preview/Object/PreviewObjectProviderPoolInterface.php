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

interface PreviewObjectProviderPoolInterface
{
    /**
     * Returns all PreviewObjectProviders.
     *
     * @return PreviewObjectProviderInterface[]
     */
    public function getObjectProviders(): array;

    /**
     * Returns the PreviewObjectProvider for given $providerKey.
     */
    public function getObjectProvider(string $providerKey): PreviewObjectProviderInterface;

    /**
     * Returns true if a PreviewObjectProvider for given $providerKey exists.
     */
    public function hasObjectProvider(string $providerKey): bool;
}

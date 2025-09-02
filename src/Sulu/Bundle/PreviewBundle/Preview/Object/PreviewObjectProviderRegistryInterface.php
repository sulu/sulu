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

use Sulu\Bundle\PreviewBundle\Preview\Provider\PreviewDefaultsProviderInterface;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
interface PreviewObjectProviderRegistryInterface
{
    /**
     * Returns all PreviewObjectProviders.
     *
     * @return array<PreviewDefaultsProviderInterface>
     */
    public function getPreviewObjectProviders(): array;

    /**
     * Returns the PreviewObjectProvider for given $providerKey.
     */
    public function getPreviewObjectProvider(string $providerKey): PreviewDefaultsProviderInterface;

    /**
     * Returns true if a PreviewObjectProvider for given $providerKey exists.
     */
    public function hasPreviewObjectProvider(string $providerKey): bool;
}

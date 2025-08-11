<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentResolver\ResolvableResourceLoader;

use Sulu\Content\Application\ContentResolver\Value\ResolvableInterface;

/**
 * @internal This interface is intended for internal use only within the package/library.
 * Modifying or depending on this interface may result in unexpected behavior and is not supported.
 */
interface ResolvableResourceLoaderInterface
{
    /**
     * Loads and resolves resources from various resource loaders.
     *
     * @param array<string, array<string|int, array<string, ResolvableInterface>>> $resourcesPerLoader Resource loaders and their associated resources to load
     *
     * @return array<string, array<string|int, array<string, mixed>>> Resolved resources organized by resource loader key
     */
    public function loadResources(array $resourcesPerLoader, ?string $locale): array;
}

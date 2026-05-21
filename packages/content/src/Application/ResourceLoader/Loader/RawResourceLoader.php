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

namespace Sulu\Content\Application\ResourceLoader\Loader;

/**
 * Returns the given ids unchanged.
 *
 * Useful as a default loader for properties that wrap ids in `ResolvableResource`
 * purely to participate in the HTTP cache reference store, without resolving the
 * id to a richer representation.
 */
class RawResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'raw';

    public function load(array $ids, ?string $locale, array $params = []): array
    {
        return \array_combine($ids, $ids);
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ResourceLoader\Loader;

use Symfony\Contracts\Service\ResetInterface;

class CachedResourceLoader implements ResourceLoaderInterface, ResetInterface
{
    /**
     * @var array<string, array<int|string, mixed>>
     */
    private array $cache = [];

    public function __construct(private ResourceLoaderInterface $decoratedResourceLoader)
    {
    }

    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $cacheKey = $this->generateCacheKey($ids, $locale, $params);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $result = $this->decoratedResourceLoader->load($ids, $locale, $params);
        $this->cache[$cacheKey] = $result;

        return $result;
    }

    public static function getKey(): string
    {
        throw new \LogicException('Should not be called statically on CachedResourceLoader');
    }

    /**
     * @param array<int|string> $ids
     * @param mixed[] $params
     */
    private function generateCacheKey(array $ids, ?string $locale, array $params): string
    {
        return \md5((string) \json_encode([
            'ids' => $ids,
            'locale' => $locale,
            'params' => $params,
        ]));
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}

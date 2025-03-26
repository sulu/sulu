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

/**
 * @internal this class is internal and should not be used or extended in custom code
 */
class CachedResourceLoader implements ResourceLoaderInterface, ResetInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $cache = [];

    public function __construct(private ResourceLoaderInterface $decoratedResourceLoader)
    {
    }

    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $result = [];
        $uncachedIds = [];

        foreach ($ids as $index => $id) {
            $cacheKey = $this->generateCacheKey($id, $locale, $params);
            if (!isset($this->cache[$cacheKey])) {
                $uncachedIds[$index] = $id;
                continue;
            }

            $result[$id] = $this->cache[$cacheKey];
        }

        if ([] !== $uncachedIds) {
            $loadedResults = $this->decoratedResourceLoader->load($uncachedIds, $locale, $params);

            // Cache and merge the newly loaded results
            foreach ($loadedResults as $id => $resource) {
                $cacheKey = $this->generateCacheKey($id, $locale, $params);
                $this->cache[$cacheKey] = $resource;
                $result[$id] = $resource;
            }
        }

        return $result;
    }

    public static function getKey(): string
    {
        throw new \LogicException('Should not be called statically on CachedResourceLoader');
    }

    /**
     * @param mixed[] $params
     */
    private function generateCacheKey(int|string $id, ?string $locale, array $params): string
    {
        return \md5((string) \json_encode([
            'id' => $id,
            'locale' => $locale,
            'params' => $params,
        ]));
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}

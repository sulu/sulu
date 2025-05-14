<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Icon\providers;

use Psr\Cache\CacheItemPoolInterface;
use Sulu\Bundle\AdminBundle\Icon\IconProviderInterface;
use Symfony\Component\Finder\Finder;

/**
 * @experimental This is an experimental feature and may change in future releases.
 */
class SvgProvider implements IconProviderInterface
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return array<array{id: string, content: string}>
     */
    public function getIcons(string $path): array
    {
        $cacheKey = 'icons_' . \md5($path);

        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            /** @var array<array{id: string, content: string}> $icons */
            $icons = $cacheItem->get();

            return $icons;
        }

        $cacheItem->set($this->getIconsFromPath($path));
        $this->cache->save($cacheItem);
        /** @var array<array{id: string, content: string}> $icons */
        $icons = $cacheItem->get();

        return $icons;
    }

    /**
     * @return array<array{id: string, content: string}>
     */
    private function getIconsFromPath(string $path): array
    {
        $icons = [];

        $finder = new Finder();
        $finder->in($path);

        foreach ($finder as $file) {
            if ('svg' !== $file->getExtension()) {
                continue;
            }

            $icons[] = [
                'id' => $file->getBasename('.svg'),
                'content' => $file->getContents(),
            ];
        }

        return $icons;
    }
}

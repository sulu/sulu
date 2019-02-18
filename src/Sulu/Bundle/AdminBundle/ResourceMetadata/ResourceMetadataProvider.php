<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class ResourceMetadataProvider implements ResourceMetadataProviderInterface, CacheWarmerInterface
{
    /**
     * @var array
     */
    private $resources;

    /**
     * @var array
     */
    private $locales;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var array
     */
    private $cacheData = [];

    public function __construct(
        array $resources,
        array $locales,
        string $cacheDir,
        bool $debug
    ) {
        $this->resources = $resources;
        $this->locales = $locales;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllResourceMetadata(string $locale): array
    {
        $resourceMetadataArray = [];

        foreach (array_keys($this->resources) as $resourceKey) {
            $resourceMetadataArray[] = $this->getResourceMetadata($resourceKey, $locale);
        }

        return $resourceMetadataArray;
    }

    public function getResourceMetadata(string $resourceKey, string $locale): ?ResourceMetadataInterface
    {
        if (!array_key_exists($resourceKey, $this->resources)) {
            return null;
        }

        $cacheKey = $resourceKey . '_' . $locale;
        if (!array_key_exists($cacheKey, $this->cacheData)) {
            $cache = $this->getCache($locale, $resourceKey);

            if (!$cache->isFresh()) {
                $this->loadResourceMetadata();
            }

            $this->cacheData[$cacheKey] = unserialize(file_get_contents($cache->getPath()));
        }

        return $this->cacheData[$cacheKey];
    }

    public function isOptional()
    {
        return false;
    }

    public function warmUp($cacheDir)
    {
        $this->loadResourceMetadata();
    }

    private function loadResourceMetadata(): void
    {
        foreach ($this->resources as $resourceKey => $resource) {
            $this->writeResourceMetadataCache(
                $resourceKey,
                array_key_exists('list', $resource) ? $resource['list'] : null,
                $resource['endpoint']
            );
        }
    }

    private function writeResourceMetadataCache(string $resourceKey, ?string $list, string $endpoint): void
    {
        $fileResources = [];

        // generate resource metadata for each locale and write it to the cache
        foreach ($this->locales as $locale) {
            $cache = $this->getCache($locale, $resourceKey);

            $resourceMetadata = new ResourceMetadata();
            $resourceMetadata->setKey($resourceKey);
            $resourceMetadata->setEndpoint($endpoint);

            $cache->write(
                serialize($resourceMetadata),
                $fileResources
            );
        }
    }

    private function getCache(string $locale, string $resourceKey): ConfigCache
    {
        $cachePath = sprintf(
            '%s%s%s_%s',
            $this->cacheDir,
            DIRECTORY_SEPARATOR,
            $locale,
            $resourceKey
        );

        $cache = new ConfigCache($cachePath, $this->debug);

        return $cache;
    }
}

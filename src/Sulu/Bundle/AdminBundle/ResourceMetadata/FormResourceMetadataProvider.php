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

use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class FormResourceMetadataProvider implements ResourceMetadataProviderInterface, CacheWarmerInterface
{
    /**
     * @var array
     */
    private $resources;

    /**
     * @var FormXmlLoader
     */
    private $formXmlLoader;

    /**
     * @var ResourceMetadataMapper
     */
    private $resourceMetadataMapper;

    /**
     * @var array
     */
    private $locales;

    /**
     * @var KernelInterface
     */
    private $kernel;

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
        FormXmlLoader $formXmlLoader,
        ResourceMetadataMapper $resourceMetadataMapper,
        array $locales,
        KernelInterface $kernel,
        string $cacheDir,
        bool $debug
    ) {
        $this->resources = $resources;
        $this->formXmlLoader = $formXmlLoader;
        $this->resourceMetadataMapper = $resourceMetadataMapper;
        $this->locales = $locales;
        $this->kernel = $kernel;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
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
            foreach ($this->locales as $locale) {
                $this->writeResourceMetadataCache(
                    $resourceKey,
                    $resource['form'],
                    $resource['datagrid'],
                    $locale
                );
            }
        }
    }

    private function writeResourceMetadataCache(string $resourceKey, array $forms, string $list, string $locale): void
    {
        $cache = $this->getCache($locale, $resourceKey);
        $fileResources = [];

        $children = [];
        $properties = [];

        foreach ($forms as $form) {
            $formFile = $this->kernel->locateResource($form);
            /** @var FormMetadata $formStructure */
            $formStructure = $this->formXmlLoader->load($formFile, $resourceKey);

            $fileResources = [new FileResource($formFile)];

            $children = array_merge($children, $formStructure->getChildren());
            $properties = array_merge($properties, $formStructure->getProperties());
        }

        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata->setDatagrid($this->resourceMetadataMapper->mapDatagrid($list, $locale));
        $resourceMetadata->setForm($this->resourceMetadataMapper->mapForm($children, $locale));
        $resourceMetadata->setSchema($this->resourceMetadataMapper->mapSchema($properties));

        $cache->write(
            serialize($resourceMetadata),
            $fileResources
        );
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

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class XmlFormMetadataLoader implements FormMetadataLoaderInterface, CacheWarmerInterface
{
    /**
     * @var FormXmlLoader
     */
    private $formXmlLoader;

    /**
     * @var string[]
     */
    private $formDirectories;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(
        FormXmlLoader $formXmlLoader,
        array $formDirectories,
        string $cacheDir,
        bool $debug
    ) {
        $this->formXmlLoader = $formXmlLoader;
        $this->formDirectories = $formDirectories;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions = []): ?FormMetadata
    {
        $configCache = $this->getConfigCache($key, $locale);

        if (!file_exists($configCache->getPath())) {
            return null;
        }

        if (!$configCache->isFresh()) {
            $this->warmUp($this->cacheDir);
        }

        $form = unserialize(file_get_contents($configCache->getPath()));

        return $form;
    }

    public function warmUp($cacheDir)
    {
        $formFinder = (new Finder())->in($this->formDirectories)->name('*.xml');
        $formsMetadataCollection = [];
        $formsMetadataResources = [];
        foreach ($formFinder as $formFile) {
            $formMetadataCollection = $this->formXmlLoader->load($formFile->getPathName());
            $items = $formMetadataCollection->getItems();
            $formKey = reset($items)->getKey();
            $formsMetadataResources[$formKey][] = $formFile->getPathName();
            if (!array_key_exists($formKey, $formsMetadataCollection)) {
                $formsMetadataCollection[$formKey] = $formMetadataCollection;
            } else {
                $formsMetadataCollection[$formKey] = $formsMetadataCollection[$formKey]->merge($formMetadataCollection);
            }
        }

        foreach ($formsMetadataCollection as $key => $formMetadataCollection) {
            foreach ($formMetadataCollection->getItems() as $locale => $formMetadata) {
                $configCache = $this->getConfigCache($key, $locale);
                $configCache->write(
                    serialize($formMetadata),
                    array_map(function(string $resource) {
                        return new FileResource($resource);
                    }, $formsMetadataResources[$key])
                );
            }
        }
    }

    public function isOptional()
    {
        return false;
    }

    private function getConfigCache(string $key, string $locale): ConfigCache
    {
        return new ConfigCache(sprintf('%s%s%s.%s', $this->cacheDir, DIRECTORY_SEPARATOR, $key, $locale), $this->debug);
    }
}

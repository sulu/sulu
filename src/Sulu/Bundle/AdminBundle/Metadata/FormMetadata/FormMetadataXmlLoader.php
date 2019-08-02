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
use Symfony\Component\Finder\Finder;

class FormMetadataXmlLoader implements FormMetadataLoaderInterface
{
    /**
     * @var FormXmlLoader
     */
    private $formXmlLoader;

    /**
     * @var array
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

    public function getMetadata(string $key, string $locale)
    {
        $configCache = $this->getConfigCache($key, $locale);

        if (!$configCache->isFresh()) {
            $this->warmUp();
        }

        if (!file_exists($configCache->getPath())) {
            return null;
        }

        $form = unserialize(file_get_contents($configCache->getPath()));

        return $form;
    }

    public function warmUp()
    {
        $formFinder = (new Finder())->in($this->formDirectories)->name('*.xml');
        foreach ($formFinder as $formFile) {
            $formMetadata = $this->formXmlLoader->load($formFile->getPathName());

            foreach ($formMetadata as $locale => $localizedFormMetadata) {
                $configCache = $this->getConfigCache($localizedFormMetadata->getKey(), $locale);
                $configCache->write(serialize($localizedFormMetadata));
            }
        }
    }

    private function getConfigCache(string $key, string $locale): ConfigCache
    {
        return new ConfigCache(sprintf('%s%s%s.%s', $this->cacheDir, DIRECTORY_SEPARATOR, $key, $locale), $this->debug);
    }
}

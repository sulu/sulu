<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * Delegates the loading of webspace configuration files to the corresponding loader. The different loaders are
 * responsible for different file types or different schema versions.
 */
class DelegatingFileLoader extends FileLoader
{
    /**
     * @var LoaderInterface[]
     */
    private $fileLoaders;

    public function __construct(array $fileLoaders = [])
    {
        $this->fileLoaders = $fileLoaders;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        foreach ($this->fileLoaders as $fileLoader) {
            if ($fileLoader->supports($resource, $type)) {
                return $fileLoader->load($resource, $type);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        foreach ($this->fileLoaders as $fileLoader) {
            if ($fileLoader->supports($resource, $type)) {
                return true;
            }
        }

        return false;
    }
}

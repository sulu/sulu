<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Renderer;

use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * Extends website-kernel from sulu-installation and override configuration.
 */
class PreviewKernel extends \AppKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        parent::registerContainerConfiguration($loader);

        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $loader->load(
                implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Resources', 'config', 'config_preview_dev.yml'])
            );
        }

        $loader->load(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Resources', 'config', 'config_preview.yml']));
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $reflectionClass = new \ReflectionClass(\AppKernel::class);
            $this->rootDir = dirname($reflectionClass->getFileName());
        }

        return $this->rootDir;
    }

    public function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            ['sulu.preview' => true]
        );
    }
}

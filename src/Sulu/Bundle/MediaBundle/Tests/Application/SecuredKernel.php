<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Application;

use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class SecuredKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        /** @var array<mixed, BundleInterface> $bundles */
        $bundles = parent::registerBundles();

        if (self::CONTEXT_WEBSITE === $this->getContext()) {
            $bundles[] = new SecurityBundle();
        }

        return $bundles;
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . \DIRECTORY_SEPARATOR
            . 'var' . \DIRECTORY_SEPARATOR
            . 'cache' . \DIRECTORY_SEPARATOR
            . $this->getContext() . '_secured' . \DIRECTORY_SEPARATOR
            . $this->environment;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        if (SuluKernel::CONTEXT_WEBSITE === $this->getContext()) {
            $loader->load(__DIR__ . '/config/config_secured.yml');
        }
    }
}

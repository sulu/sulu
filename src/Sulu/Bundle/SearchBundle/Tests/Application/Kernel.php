<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Application;

use Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\TestBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class Kernel extends SuluTestKernel
{
    public function registerBundles(): iterable
    {
        return \array_merge(
            [...parent::registerBundles()],
            [
                new TestBundle(),
            ]
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(__DIR__ . '/config/config.yml');

        $environmentConfig = __DIR__ . '/config/config_' . $this->getEnvironment() . '.yml';
        if (\file_exists($environmentConfig)) {
            $loader->load($environmentConfig);
        }
    }
}

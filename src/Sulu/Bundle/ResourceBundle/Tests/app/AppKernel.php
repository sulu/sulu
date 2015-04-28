<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\Config\Loader\LoaderInterface;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Bundle\TestBundle\SuluTestBundle;

class AppKernel extends SuluTestKernel
{
    public function registerBundles()
    {
        return parent::registerBundles();
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');
        $loader->load(__DIR__ . '/config/config.yml');
    }
}

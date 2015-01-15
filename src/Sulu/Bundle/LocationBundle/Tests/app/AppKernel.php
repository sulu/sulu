<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Bundle\TestBundle\SuluTestBundle;

class AppKernel extends SuluTestKernel
{
    public function registerBundles()
    {
        return array_merge(
            parent::registerBundles(),
            array(
                new \Sulu\Bundle\LocationBundle\SuluLocationBundle(),
            )
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');
        $loader->load(__DIR__ . '/config/config.yml');
    }
}

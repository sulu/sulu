<?php

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Bundle\TestBundle\SuluTestBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends SuluTestKernel
{
    public function registerBundles()
    {
        return array_merge(
            parent::registerBundles(),
            array(
                new \Massive\Bundle\SearchBundle\MassiveSearchBundle(),
                new \Sulu\Bundle\SearchBundle\SuluSearchBundle(),
            )
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');
        $loader->load(__DIR__ . '/config/config.yml');
    }
}

<?php

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Sulu\Bundle\TestBundle\SuluTestBundle;

class AppKernel extends SuluTestKernel
{
    public function registerBundles()
    {
        return array_merge(
            parent::registerBundles(),
            array(
                new DTL\Bundle\ContentBundle\ContentBundle(),
            )
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');
        $loader->load(__DIR__ . '/config/test.yml');
    }
}

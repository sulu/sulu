<?php

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Bundle\TestBundle\SuluTestBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends SuluTestKernel
{
    public function registerBundles()
    {
        if (!class_exists('Sulu\Bundle\SearchBundle\SuluSearchBundle')) {
            return parent::registerBundles();
        }

        return array_merge(
            parent::registerBundles(),
            array(
                new \Massive\Bundle\SearchBundle\MassiveSearchBundle(),
                new \Sulu\Bundle\SearchBundle\SuluSearchBundle(),
                new \Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\TestBundle(),
            )
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');
        if (class_exists('Sulu\Bundle\SearchBundle\SuluSearchBundle')) {
            $loader->load(__DIR__ . '/config/search.yml');
        }
    }
}

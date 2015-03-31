<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Sulu\Bundle\TestBundle\SuluTestBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;

class AppKernel extends SuluTestKernel
{
    public function registerBundles()
    {
        return array_merge(
            parent::registerBundles(),
            array(
                new \Sulu\Bundle\TranslateBundle\SuluTranslateBundle(),
            )
        );
    }
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');
    }
}

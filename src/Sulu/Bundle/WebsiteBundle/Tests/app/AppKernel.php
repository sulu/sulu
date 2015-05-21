<?php

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Bundle\TestBundle\SuluTestBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends SuluTestKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');
    }
}

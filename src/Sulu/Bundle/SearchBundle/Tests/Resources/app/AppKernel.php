<?php

use Symfony\Cmf\Component\Testing\HttpKernel\TestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends TestKernel
{
    public function configure()
    {
        $this->requireBundleSets(array(
            'default',
            'doctrine_orm',
        ));

        $this->addBundles(array(
            new \Massive\Bundle\SearchBundle\MassiveSearchBundle(),
            new \Sulu\Bundle\SearchBundle\SuluSearchBundle(),
        ));
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.php');
    }
}

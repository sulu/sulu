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
            new \Sulu\Bundle\SearchBundle\SuluSearchBundle(),
            new \Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\TestBundle()
        ));
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.php');
    }
}

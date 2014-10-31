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
            'phpcr_odm',
        ));

        $this->addBundles(array(
            new \Massive\Bundle\SearchBundle\MassiveSearchBundle(),
            new \Sulu\Bundle\SearchBundle\SuluSearchBundle(),
            new \Sulu\Bundle\CoreBundle\SuluCoreBundle(),
            new \Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\TestBundle(),
        ));
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.php');
    }

    protected function getEnvParameters()
    {
        return array_merge(
            parent::getEnvParameters(),
            array('sulu.context' => 'admin')
        );
    }
}

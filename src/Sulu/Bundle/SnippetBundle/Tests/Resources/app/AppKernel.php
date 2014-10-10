<?php

use Symfony\Cmf\Component\Testing\HttpKernel\TestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends TestKernel
{
    public function configure()
    {
        $this->requireBundleSets(array(
            'default',
            'phpcr_odm',
            'doctrine_orm',
        ));

        $this->addBundles(array(
            new \Sulu\Bundle\CoreBundle\SuluCoreBundle(),
            new \Sulu\Bundle\SnippetBundle\SuluSnippetBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \FOS\RestBundle\FOSRestBundle(),
            new \Sulu\Bundle\TestBundle\SuluTestBundle(),
            new \Sulu\Bundle\AdminBundle\SuluAdminBundle(),
        ));
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.php');
    }
}

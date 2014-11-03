<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    static $context = 'admin';

    public function registerBundles()
    {
        $bundles = array(
            // Dependencies
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new \Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle(),

            // Sulu
            new \Sulu\Bundle\CoreBundle\SuluCoreBundle(),
            new \Sulu\Bundle\TestBundle\SuluTestBundle(),
        );

        return $bundles;
    }

    /**
     * {@inheritDoc}
     */
    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            array(
                'sulu.context' => \Sulu\Component\HttpKernel\SuluKernel::CONTEXT_ADMIN
            )
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if (array_key_exists('APP_DB', $GLOBALS) &&
            file_exists(__DIR__ . '/config/config.' . $GLOBALS['APP_DB'] . '.yml')
        ) {
            $loader->load(__DIR__ . '/config/config.' . $GLOBALS['APP_DB'] . '.yml');
        } else {
            $loader->load(__DIR__ . '/config/config.mysql.yml');
        }
    }
}

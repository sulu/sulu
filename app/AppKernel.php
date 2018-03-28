<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AppKernel extends SuluKernel
{
    public function __construct($environment, $debug)
    {
        parent::__construct($environment, $debug);
        $this->setContext(self::CONTEXT_ADMIN);
    }

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\WebServerBundle\WebServerBundle(),
            new Sulu\Bundle\CoreBundle\SuluCoreBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),

            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Bazinga\Bundle\HateoasBundle\BazingaHateoasBundle(),

            new Massive\Bundle\SearchBundle\MassiveSearchBundle(),

            new Sulu\Bundle\SearchBundle\SuluSearchBundle(),
            new Sulu\Bundle\PersistenceBundle\SuluPersistenceBundle(),
            new Sulu\Bundle\ContactBundle\SuluContactBundle(),
            new Sulu\Bundle\MediaBundle\SuluMediaBundle(),
            new Sulu\Bundle\SecurityBundle\SuluSecurityBundle(),
            new Sulu\Bundle\CategoryBundle\SuluCategoryBundle(),
            new Sulu\Bundle\SnippetBundle\SuluSnippetBundle(),
            new Sulu\Bundle\ContentBundle\SuluContentBundle(),
            new Sulu\Bundle\TagBundle\SuluTagBundle(),
            new Sulu\Bundle\WebsiteBundle\SuluWebsiteBundle(),
            new Sulu\Bundle\LocationBundle\SuluLocationBundle(),
            new Sulu\Bundle\HttpCacheBundle\SuluHttpCacheBundle(),
            new Sulu\Bundle\WebsocketBundle\SuluWebsocketBundle(),
            new Sulu\Bundle\TranslateBundle\SuluTranslateBundle(),
            new Sulu\Bundle\DocumentManagerBundle\SuluDocumentManagerBundle(),
            new Sulu\Bundle\HashBundle\SuluHashBundle(),
            new Sulu\Bundle\CustomUrlBundle\SuluCustomUrlBundle(),
            new Sulu\Bundle\RouteBundle\SuluRouteBundle(),
            new Sulu\Bundle\MarkupBundle\SuluMarkupBundle(),
            new Sulu\Bundle\AdminBundle\SuluAdminBundle(),
            new Sulu\Bundle\CollaborationBundle\SuluCollaborationBundle(),
            new Sulu\Bundle\AudienceTargetingBundle\SuluAudienceTargetingBundle(),
            new Sulu\Bundle\PreviewBundle\SuluPreviewBundle(),

            new DTL\Bundle\PhpcrMigrations\PhpcrMigrationsBundle(),
            new Massive\Bundle\BuildBundle\MassiveBuildBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getProjectDir()
    {
        return dirname(__DIR__);
    }

    public function getCacheDir()
    {
        return $this->getProjectDir() . '/var/cache/' . $this->getEnvironment();
    }

    public function getLogDir()
    {
        return $this->getProjectDir() . '/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');

        $loader->load(function (ContainerBuilder $container) {
            $container->setParameter('phpcr.transport', $container->resolveEnvPlaceholders(
                $container->getParameter('phpcr.transport'),
                true
            ));
        });
    }

    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            [
                'kernel.var_dir' => dirname($this->rootDir) . DIRECTORY_SEPARATOR . 'var',
            ]
        );
    }
}

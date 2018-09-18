<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Kernel;

use Sulu\Bundle\TestBundle\SuluTestBundle;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Represents a kernel for sulu-application tests.
 */
class SuluTestKernel extends SuluKernel
{
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $environment, bool $debug, string $suluContext = SuluKernel::CONTEXT_ADMIN)
    {
        parent::__construct($environment, $debug, $suluContext);
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            // Dependencies
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Sulu\Bundle\CoreBundle\SuluCoreBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle(),
            new \Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle(),
            new \Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),

            // Massive
            new \Massive\Bundle\SearchBundle\MassiveSearchBundle(),

            // Sulu
            new \Sulu\Bundle\SearchBundle\SuluSearchBundle(),
            new \Sulu\Bundle\PersistenceBundle\SuluPersistenceBundle(),
            new \Sulu\Bundle\ContentBundle\SuluContentBundle(),
            new \Sulu\Bundle\ContactBundle\SuluContactBundle(),
            new \Sulu\Bundle\SecurityBundle\SuluSecurityBundle(),
            new \Sulu\Bundle\WebsiteBundle\SuluWebsiteBundle(),
            new \Sulu\Bundle\TestBundle\SuluTestBundle(),
            new \Sulu\Bundle\TagBundle\SuluTagBundle(),
            new \Sulu\Bundle\MediaBundle\SuluMediaBundle(),
            new \Sulu\Bundle\CategoryBundle\SuluCategoryBundle(),
            new \Sulu\Bundle\HttpCacheBundle\SuluHttpCacheBundle(),
            new \Sulu\Bundle\SnippetBundle\SuluSnippetBundle(),
            new \Sulu\Bundle\WebsocketBundle\SuluWebsocketBundle(),
            new \Sulu\Bundle\LocationBundle\SuluLocationBundle(),
            new \Sulu\Bundle\DocumentManagerBundle\SuluDocumentManagerBundle(),
            new \Sulu\Bundle\HashBundle\SuluHashBundle(),
            new \Sulu\Bundle\CustomUrlBundle\SuluCustomUrlBundle(),
            new \Sulu\Bundle\RouteBundle\SuluRouteBundle(),
            new \Sulu\Bundle\MarkupBundle\SuluMarkupBundle(),
            new \Sulu\Bundle\AudienceTargetingBundle\SuluAudienceTargetingBundle(),
        ];

        if (self::CONTEXT_WEBSITE === $this->getContext()) {
            $bundles[] = new \Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle();
        }

        if (self::CONTEXT_ADMIN === $this->getContext()) {
            $bundles[] = new \Bazinga\Bundle\HateoasBundle\BazingaHateoasBundle();
            $bundles[] = new \FOS\RestBundle\FOSRestBundle();
            $bundles[] = new \Symfony\Bundle\SecurityBundle\SecurityBundle();
            $bundles[] = new \Sulu\Bundle\ResourceBundle\SuluResourceBundle();
            $bundles[] = new \Sulu\Bundle\AdminBundle\SuluAdminBundle();
            $bundles[] = new \Sulu\Bundle\CollaborationBundle\SuluCollaborationBundle();
            $bundles[] = new \Sulu\Bundle\PreviewBundle\SuluPreviewBundle();
        }

        return $bundles;
    }

    /**
     * Gets the application root dir (path of the project's composer file).
     *
     * @return string The project root dir
     */
    public function getProjectDir()
    {
        if (null === $this->projectDir) {
            $this->projectDir = dirname($this->rootDir);
        }

        return $this->projectDir;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');

        // we need to resolve the SULU_PHPCR_TRANSPORT environment variable at this point,
        // because the doctrine phpcr configuration is not working with unresolved environment variables
        $loader->load(function (ContainerBuilder $container) {
            $container->setParameter('phpcr.transport', $container->resolveEnvPlaceholders(
                $container->getParameter('phpcr.transport'),
                true
            ));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->getProjectDir() . '/var/cache/' . $this->getContext() . '/' . $this->environment;
    }

    /**
     * {@inheritdoc}
     *
     * Add the Sulu environment to the container name
     */
    protected function getContainerClass()
    {
        return $this->name . ucfirst($this->getContext()) . ucfirst($this->environment) . ($this->debug ? 'Debug' : '') . 'ProjectContainer';
    }
}

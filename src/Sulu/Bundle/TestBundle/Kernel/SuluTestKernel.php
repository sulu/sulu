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

class SuluTestKernel extends SuluKernel
{
    public function registerBundles()
    {
        $bundles = [
            // Dependencies
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Dubture\FFmpegBundle\DubtureFFmpegBundle(),

            // Massive
            new \Massive\Bundle\SearchBundle\MassiveSearchBundle(),

            // Sulu
            new \Sulu\Bundle\SearchBundle\SuluSearchBundle(),
            new \Sulu\Bundle\CoreBundle\SuluCoreBundle(),
            new \Sulu\Bundle\PersistenceBundle\SuluPersistenceBundle(),
            new \Sulu\Bundle\AdminBundle\SuluAdminBundle(),
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
            new \Sulu\Bundle\ResourceBundle\SuluResourceBundle(),
            new \Sulu\Bundle\TranslateBundle\SuluTranslateBundle(),
            new \Sulu\Bundle\HashBundle\SuluHashBundle(),
            new \Sulu\Bundle\CustomUrlBundle\SuluCustomUrlBundle(),
            new \Sulu\Bundle\PreviewBundle\SuluPreviewBundle(),
            new \Sulu\Bundle\RouteBundle\SuluRouteBundle(),
            new \Sulu\Bundle\MarkupBundle\SuluMarkupBundle(),
            new \Sulu\Bundle\AutomationBundle\SuluAutomationBundle(),
        ];

        if ($this->getContext() === self::CONTEXT_WEBSITE) {
            // smyfony-cmf
            $bundles[] = new \Symfony\Cmf\Bundle\CoreBundle\CmfCoreBundle();
            $bundles[] = new \Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle();
        }

        if ($this->getContext() === self::CONTEXT_ADMIN) {
            // rest
            $bundles[] = new \Bazinga\Bundle\HateoasBundle\BazingaHateoasBundle();
            $bundles[] = new \FOS\RestBundle\FOSRestBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');

        // @see https://github.com/symfony/symfony/issues/7555
        $envParameters = $this->getEnvParameters();

        $loader->load(function ($container) use ($envParameters) {
            $container->getParameterBag()->add($envParameters);
        });
    }

    public function getCacheDir()
    {
        return $this->rootDir . '/cache/' . $this->getContext() . '/' . $this->environment;
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

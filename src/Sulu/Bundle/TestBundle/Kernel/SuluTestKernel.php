<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Kernel;

use Sulu\Bundle\TestBundle\SuluTestBundle;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

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

    public function registerBundles(): iterable
    {
        /** @var array<mixed, BundleInterface> $bundles */
        $bundles = [
            // Dependencies
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Sulu\Bundle\CoreBundle\SuluCoreBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \FOS\RestBundle\FOSRestBundle(),
            new \FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new \League\FlysystemBundle\FlysystemBundle(),

            // Sulu
            new \Sulu\Messenger\Infrastructure\Symfony\HttpKernel\SuluMessengerBundle(),
            new \Sulu\Bundle\AdminBundle\SuluAdminBundle(),
            new \Sulu\Bundle\PersistenceBundle\SuluPersistenceBundle(),
            new \Sulu\Bundle\ContactBundle\SuluContactBundle(),
            new \Sulu\Bundle\SecurityBundle\SuluSecurityBundle(),
            new \Sulu\Bundle\WebsiteBundle\SuluWebsiteBundle(),
            new \Sulu\Bundle\TestBundle\SuluTestBundle(),
            new \Sulu\Bundle\TagBundle\SuluTagBundle(),
            new \Sulu\Bundle\MediaBundle\SuluMediaBundle(),
            new \Sulu\Bundle\CategoryBundle\SuluCategoryBundle(),
            new \Sulu\Bundle\HttpCacheBundle\SuluHttpCacheBundle(),
            new \Sulu\Bundle\LocationBundle\SuluLocationBundle(),
            new \Sulu\Bundle\HashBundle\SuluHashBundle(),
            new \Sulu\Bundle\ActivityBundle\SuluActivityBundle(),
            new \Sulu\Route\Infrastructure\Symfony\HttpKernel\SuluRouteBundle(),
            new \Sulu\Bundle\MarkupBundle\SuluMarkupBundle(),
            new \Sulu\Bundle\PreviewBundle\SuluPreviewBundle(),
            new \Sulu\Bundle\AudienceTargetingBundle\SuluAudienceTargetingBundle(),
            new \Sulu\Bundle\TrashBundle\SuluTrashBundle(),
            new \Sulu\Bundle\ReferenceBundle\SuluReferenceBundle(),
            new \Sulu\Content\Infrastructure\Symfony\HttpKernel\SuluContentBundle(),
            new \Sulu\Page\Infrastructure\Symfony\HttpKernel\SuluPageBundle(),
        ];

        if (\class_exists(\Symfony\Bundle\MonologBundle\MonologBundle::class)) {
            $bundles[] = new \Symfony\Bundle\MonologBundle\MonologBundle();
        }

        if (\class_exists(\Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class)) {
            $bundles[] = new \Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
        }

        if (\class_exists(\Massive\Bundle\BuildBundle\MassiveBuildBundle::class)) {
            $bundles[] = new \Massive\Bundle\BuildBundle\MassiveBuildBundle();
        }

        if (\class_exists(\FOS\HttpCacheBundle\FOSHttpCacheBundle::class)) {
            $bundles[] = new \FOS\HttpCacheBundle\FOSHttpCacheBundle();
        }

        if (self::CONTEXT_WEBSITE === $this->getContext()) {
            $bundles[] = new \Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle();
        }

        if (self::CONTEXT_ADMIN === $this->getContext()) {
            $bundles[] = new \Symfony\Bundle\SecurityBundle\SecurityBundle();
        }

        return $bundles;
    }

    /**
     * Gets the application root dir (path of the project's composer file).
     *
     * @return string The project root dir
     */
    public function getProjectDir(): string
    {
        if (null === $this->projectDir) {
            $r = new \ReflectionObject($this);
            $this->projectDir = \dirname($r->getFileName());
        }

        return $this->projectDir;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');
    }
}

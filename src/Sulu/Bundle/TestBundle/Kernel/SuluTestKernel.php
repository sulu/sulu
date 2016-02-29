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
use Symfony\Component\Filesystem\Filesystem;
use Sulu\Bundle\TestBundle\Testing\Tool\WebspaceTool;

class SuluTestKernel extends SuluKernel
{
    static protected $webspaceDir = __DIR__ . '/../Resources/webspaces';

    public function registerBundles()
    {
        $bundles = [
            // Dependencies
            new \Bazinga\Bundle\HateoasBundle\BazingaHateoasBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle(),
            new \FOS\RestBundle\FOSRestBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \Liip\ThemeBundle\LiipThemeBundle(),
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
        ];

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
<<<<<<< HEAD
=======

    /**
     * {@inheritdoc}
     */
    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            [
                'sulu_test.bundle_dir' => realpath(__DIR__),
            ]
        );
    }

    /**
     * Generate a webspace file in the TestBundle webspace direectory.
     * Will erase any existing webspaces.
     *
     * @see WebspaceTool::generateWebspace
     */
    public static function generateWebspace(array $webspaceConfig)
    {
        if (!isset($webspaceConfig['key'])) {
            throw new \InvalidArgumentException(sprintf(
                'You must at least define a "key" for each webspace, got: "%s"',
                json_encode($webspaceConfig)
            ));
        }

        $dom = WebspaceTool::generateWebspace($webspaceConfig);
        $filename = self::$webspaceDir . '/' . $webspaceConfig['key'] . '.xml';
        $dom->save($filename);
    }

    public static function purgeWebspaces()
    {
        $fs = new Filesystem();
        $fs->remove(self::$webspaceDir);
        $fs->mkdir(self::$webspaceDir);
    }
>>>>>>> f0722c9... WebspaceGneerator
}

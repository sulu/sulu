<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpKernel;

use Sulu\Bundle\PreviewBundle\SuluPreviewBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * Base class for all Sulu kernels.
 */
abstract class SuluKernel extends Kernel
{
    use MicroKernelTrait;

    const CONTEXT_ADMIN = 'admin';

    const CONTEXT_WEBSITE = 'website';

    const CONTEXT_PREVIEW = 'preview';

    /**
     * @var string
     */
    private $context = self::CONTEXT_ADMIN;

    /**
     * @var string
     */
    private $reversedContext = self::CONTEXT_WEBSITE;

    /**
     * @var bool
     */
    private $isPreview = false;

    /**
     * Overload the parent constructor method to add an additional
     * constructor argument.
     *
     * {@inheritdoc}
     *
     * @param string $environment
     * @param bool $debug
     * @param string $suluContext The Sulu context (self::CONTEXT_ADMIN, self::CONTEXT_WEBSITE)
     */
    public function __construct($environment, $debug, $suluContext = self::CONTEXT_ADMIN)
    {
        $this->isPreview = false;

        if ($suluContext === self::CONTEXT_PREVIEW) {
            $this->isPreview = true;
            $suluContext = self::CONTEXT_WEBSITE;
        }

        $this->context = $suluContext;
        $this->reversedContext = self::CONTEXT_ADMIN === $this->context ? self::CONTEXT_WEBSITE : self::CONTEXT_ADMIN;
        parent::__construct($environment, $debug);
    }

    public function registerBundles()
    {
        $contents = require $this->getProjectDir() . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if (
                // if is all or current environment
                (isset($envs['all']) || isset($envs[$this->environment]))
                // and if not registered for other context.
                && !isset($envs[$this->reversedContext])
            ) {
                yield new $class();
            }
        }
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->addResource(new FileResource($this->getProjectDir() . '/config/bundles.php'));
        $confDir = $this->getProjectDir() . '/config';

        $this->load($loader, $confDir, '/{packages}/*');
        $this->load($loader, $confDir, '/{packages}/' . $this->environment . '/*');
        $this->load($loader, $confDir, '/{services}');
        $this->load($loader, $confDir, '/{services}_' . $this->context);
        $this->load($loader, $confDir, '/{services}_' . $this->environment);

        if ($this->isPreview) {
            // TODO move to preview bundle compiler pass
            $reflection = new \ReflectionClass(SuluPreviewBundle::class);
            $dirname = dirname($reflection->getFileName());

            $loader->load(function(ContainerBuilder $container) use ($loader, $dirname) {
                // disable web_profiler toolbar in preview if the web_profiler extension exist
                if ($container->hasExtension('web_profiler')) {
                    $loader->load(
                        implode(DIRECTORY_SEPARATOR, [$dirname, 'Resources', 'config', 'config_preview_dev.yml'])
                    );
                }
            });

            $loader->load(implode(DIRECTORY_SEPARATOR, [$dirname, 'Resources', 'config', 'config_preview.yml']));
        }
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $confDir = $this->getProjectDir() . '/config';

        $this->import($routes, $confDir, '/{routes}/*');
        $this->import($routes, $confDir, '/{routes}/' . $this->environment . '/*');
        $this->import($routes, $confDir, '/{routes}');
        $this->import($routes, $confDir, '/{routes}_' . $this->context);
    }

    protected function load(LoaderInterface $loader, $confDir, $pattern)
    {
        $configExtensions = $this->getConfigExtensions();
        $reversedConfigExtensions = $this->getReversedConfigExtensions();
        $configFiles = $this->glob($confDir, $pattern . $configExtensions);
        $excludedConfigFiles = $this->glob($confDir, $pattern . $reversedConfigExtensions);

        foreach ($configFiles as $resource) {
            if (!in_array($resource, $excludedConfigFiles)) {
                $loader->load($resource);
            }
        }
    }

    protected function import(RouteCollectionBuilder $routes, $confDir, $pattern)
    {
        $configExtensions = $this->getConfigExtensions();
        $reversedConfigExtensions = $this->getReversedConfigExtensions();

        $configFiles = $this->glob($confDir, $pattern . $configExtensions);
        $excludedConfigFiles = $this->glob($confDir, $pattern . $reversedConfigExtensions);

        foreach ($configFiles as $resource) {
            if (!in_array($resource, $excludedConfigFiles)) {
                $routes->import($resource, '/');
            }
        }
    }

    private function glob($confDir, $pattern)
    {
        $resources = new GlobResource($confDir, $pattern, false);

        return array_keys(iterator_to_array($resources));
    }

    public function getCacheDir()
    {
        return $this->getProjectDir() . DIRECTORY_SEPARATOR
            . 'var' . DIRECTORY_SEPARATOR
            . 'cache' . DIRECTORY_SEPARATOR
            . ($this->isPreview ? 'preview' : $this->context) . DIRECTORY_SEPARATOR
            . $this->environment;
    }

    public function getCommonCacheDir()
    {
        return $this->getProjectDir() . DIRECTORY_SEPARATOR
            . 'var' . DIRECTORY_SEPARATOR
            . 'cache' . DIRECTORY_SEPARATOR
            . 'common' . DIRECTORY_SEPARATOR
            . $this->environment;
    }

    public function getLogDir()
    {
        return $this->getProjectDir() . DIRECTORY_SEPARATOR
            . 'var' . DIRECTORY_SEPARATOR
            . 'log' . DIRECTORY_SEPARATOR
            . ($this->isPreview ? 'preview' : $this->context);
    }

    protected function getConfigExtensions(): string
    {
        return '.{php,xml,yaml,yml}';
    }

    /**
     * Return the application context.
     *
     * The context indicates to the runtime code which
     * front controller has been accessed (e.g. website or admin)
     */
    protected function getContext(): string
    {
        return $this->context;
    }

    /**
     * Set context.
     *
     * @return $this
     */
    protected function setContext(string $context)
    {
        $this->context = $context;

        return $this;
    }

    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            [
                'sulu.context' => $this->context,
                'sulu.common_cache_dir' => $this->getCommonCacheDir(),
                'sulu.preview' => $this->isPreview,
            ]
        );
    }

    private function getReversedConfigExtensions()
    {
        $configExtensions = $this->getConfigExtensions();

        return '_' . $this->reversedContext . $configExtensions;
    }

    public function createPreviewKernel(): self
    {
        return new static($this->environment, $this->debug, self::CONTEXT_PREVIEW);
    }
}

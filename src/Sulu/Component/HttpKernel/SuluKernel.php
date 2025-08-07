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

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Base class for all Sulu kernels.
 */
abstract class SuluKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @internal only for internal use, do not use in your code, might be removed in the future
     */
    public const CONTEXT_ADMIN = 'admin';

    /**
     * @internal only for internal use, do not use in your code, might be removed in the future
     */
    public const CONTEXT_WEBSITE = 'website';

    /**
     * @var string
     */
    private $context = self::CONTEXT_ADMIN;

    /**
     * @var string
     */
    private $reversedContext = self::CONTEXT_WEBSITE;

    /**
     * @var string[]
     */
    private $loadedFiles = [];

    /**
     * @var string[]
     */
    private $importedFiles = [];

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
        if (\property_exists($this, 'name')) {
            $this->name = $suluContext;
        }

        $this->context = $suluContext;
        $this->reversedContext = self::CONTEXT_ADMIN === $this->context ? self::CONTEXT_WEBSITE : self::CONTEXT_ADMIN;
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
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

        // this is a bc layer that sulu_admin.yaml is only loaded when bundle is registered
        // this can be removed when there is no longer context based configuration
        /** @var array<string, class-string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['SuluAdminBundle'])) {
            $this->load($loader, $confDir, '/{packages}/sulu_admin');
            $this->load($loader, $confDir, '/{packages}/' . $this->environment . '/sulu_admin');
        }
        if (isset($bundles['SuluWebsiteBundle'])) {
            $this->load($loader, $confDir, '/{packages}/sulu_website');
            $this->load($loader, $confDir, '/{packages}/' . $this->environment . '/sulu_website');
        }

        $this->load($loader, $confDir, '/{packages}/*');
        $this->load($loader, $confDir, '/{packages}/' . $this->environment . '/*');
        $this->load($loader, $confDir, '/{services}');
        $this->load($loader, $confDir, '/{services}_' . $this->context);
        $this->load($loader, $confDir, '/{services}_' . $this->environment);

        $this->loadedFiles = [];
    }

    /**
     * The "getContainerClass" need to be normalized for preview and other contexts
     * as its used by the symfony cache component as prefix.
     *
     * @see https://github.com/symfony/symfony/blob/v4.4.7/src/Symfony/Component/Cache/DependencyInjection/CachePoolPass.php#L56
     */
    protected function getContainerClass(): string
    {
        return $this->generateContainerClass(static::class);
    }

    /**
     * @internal
     *
     * This is only used to support Symfony ^4.4 and 5 at the same time.
     * To get the container class use `getContainerClass` instead.
     *
     * This is a copy of the symfony 5.0 getContainerClass which does not include $this->name.
     *
     * @see https://github.com/symfony/symfony/blob/v5.0.7/src/Symfony/Component/HttpKernel/Kernel.php#L394
     *
     * @param string $class
     *
     * @return string The container class
     */
    protected function generateContainerClass($class)
    {
        $class = false !== \strpos($class, "@anonymous\0") ? \get_parent_class($class) . \str_replace('.', '_', ContainerBuilder::hash($class)) : $class;
        $class = \str_replace('\\', '_', $class) . \ucfirst($this->environment) . ($this->debug ? 'Debug' : '') . 'Container';
        if (!\preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
            throw new \InvalidArgumentException(\sprintf('The environment "%s" contains invalid characters, it can only contain characters allowed in PHP class names.', $this->environment));
        }

        return $class;
    }

    protected function configureRoutes(RoutingConfigurator $routes)
    {
        $confDir = $this->getProjectDir() . '/config';

        $this->import($routes, $confDir, '/{routes}/*');
        $this->import($routes, $confDir, '/{routes}/' . $this->environment . '/*');
        $this->import($routes, $confDir, '/{routes}');
        $this->import($routes, $confDir, '/{routes}_' . $this->context);

        $this->importedFiles = [];
    }

    protected function load(LoaderInterface $loader, $confDir, $pattern)
    {
        $configExtensions = $this->getConfigExtensions();
        $reversedConfigExtensions = $this->getReversedConfigExtensions();
        $configFiles = $this->glob($confDir, $pattern . $configExtensions);
        $excludedConfigFiles = $this->glob($confDir, $pattern . $reversedConfigExtensions);

        foreach ($configFiles as $resource) {
            if (!\in_array($resource, $excludedConfigFiles) && !\in_array($resource, $this->loadedFiles)) {
                $loader->load($resource);

                $this->loadedFiles[] = $resource;
            }
        }
    }

    protected function import(RoutingConfigurator $routes, $confDir, $pattern)
    {
        $configExtensions = $this->getConfigExtensions();
        $reversedConfigExtensions = $this->getReversedConfigExtensions();

        $configFiles = $this->glob($confDir, $pattern . $configExtensions);
        $excludedConfigFiles = $this->glob($confDir, $pattern . $reversedConfigExtensions);

        foreach ($configFiles as $resource) {
            if (!\in_array($resource, $excludedConfigFiles) && !\in_array($resource, $this->importedFiles)) {
                $routes->import($resource);

                $this->importedFiles[] = $resource;
            }
        }
    }

    private function glob($confDir, $pattern)
    {
        $resources = new GlobResource($confDir, $pattern, false);

        return \array_keys(\iterator_to_array($resources));
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . \DIRECTORY_SEPARATOR
            . 'var' . \DIRECTORY_SEPARATOR
            . 'cache' . \DIRECTORY_SEPARATOR
            . $this->context . \DIRECTORY_SEPARATOR
            . $this->environment;
    }

    public function getCommonCacheDir(): string
    {
        return $this->getProjectDir() . \DIRECTORY_SEPARATOR
            . 'var' . \DIRECTORY_SEPARATOR
            . 'cache' . \DIRECTORY_SEPARATOR
            . 'common' . \DIRECTORY_SEPARATOR
            . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . \DIRECTORY_SEPARATOR
            . 'var' . \DIRECTORY_SEPARATOR
            . 'log' . \DIRECTORY_SEPARATOR
            . $this->context;
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
     *
     * @internal Only for internal use, do not use in your code, might be removed in the future.
     *           Check for the current Symfony firewall name instead: https://symfony.com/doc/7.4/security.html#fetching-the-firewall-configuration-for-a-request
     */
    protected function getContext(): string
    {
        return $this->context;
    }

    /**
     * Set context.
     *
     * @return $this
     *
     * @internal only for internal use, do not use in your code, might be removed in the future
     */
    protected function setContext(string $context)
    {
        $this->context = $context;

        return $this;
    }

    protected function getKernelParameters(): array
    {
        return \array_merge(
            parent::getKernelParameters(),
            [
                'sulu.context' => $this->context,
                'sulu.common_cache_dir' => $this->getCommonCacheDir(),
            ]
        );
    }

    private function getReversedConfigExtensions()
    {
        $configExtensions = $this->getConfigExtensions();

        return '_' . $this->reversedContext . $configExtensions;
    }
}

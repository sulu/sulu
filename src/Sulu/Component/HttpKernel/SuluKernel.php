<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpKernel;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
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

    /**
     * @var string
     */
    private $context = self::CONTEXT_ADMIN;

    /**
     * @var string
     */
    private $reversedContext = self::CONTEXT_WEBSITE;

    /**
     * Overload the parent constructor method to add an additional
     * constructor argument.
     *
     * {@inheritdoc}
     *
     * @param string $environment
     * @param bool   $debug
     * @param string $suluContext The Sulu context (self::CONTEXT_ADMIN, self::CONTEXT_WEBSITE)
     */
    public function __construct($environment, $debug, $suluContext)
    {
        $this->context = $suluContext;
        $this->reversedContext = $this->context === self::CONTEXT_ADMIN ? self::CONTEXT_WEBSITE : self::CONTEXT_ADMIN;
        parent::__construct($environment, $debug);
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
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

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $configExtensions = $this->getConfigExtensions();

        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $confDir = $this->getProjectDir().'/config';
        $notReversedContext = $this->getNotReversedContext();

        $loader->load($confDir.'/{packages}/*'.$configExtensions.$notReversedContext, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.$configExtensions.$notReversedContext, 'glob');
        $loader->load($confDir.'/{services}'.$configExtensions.$notReversedContext, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.$configExtensions.$notReversedContext, 'glob');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $configExtensions = $this->getConfigExtensions();

        $confDir = $this->getProjectDir().'/config';
        $notReversedContext = $this->getNotReversedContext();

        $routes->import($confDir.'/{routes}/*'.$configExtensions.$notReversedContext, '/', 'glob');
        $routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.$configExtensions.$notReversedContext, '/', 'glob');
        $routes->import($confDir.'/{routes}'.$configExtensions.$notReversedContext, '/', 'glob');
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->getProjectDir() . DIRECTORY_SEPARATOR
            . 'var' . DIRECTORY_SEPARATOR
            . 'cache' . DIRECTORY_SEPARATOR
            . $this->context . DIRECTORY_SEPARATOR
            . $this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->getProjectDir() . DIRECTORY_SEPARATOR
            . 'var' . DIRECTORY_SEPARATOR
            . 'logs' . DIRECTORY_SEPARATOR
            . $this->context;
    }

    private function getNotReversedContext()
    {
        $configExtensions = $this->getConfigExtensions();

        return '!(*_'.$this->reversedContext.'.'.$configExtensions.')';
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
     * @param string $context
     *
     * @return $this
     */
    protected function setContext(string $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            [
                'sulu.context' => $this->context,
            ]
        );
    }
}

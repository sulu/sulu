<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Component\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\KernelInterface;

class AppCache extends HttpCache implements KernelInterface
{
    public function __construct(KernelInterface $kernel, $hasAudienceTargeting = false, $cacheDir = null)
    {
        parent::__construct($kernel, $hasAudienceTargeting, $cacheDir);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return $this->kernel->serialize();
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->kernel->unserialize($serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return $this->kernel->registerBundles();
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(\Symfony\Component\Config\Loader\LoaderInterface $loader)
    {
        return $this->kernel->registerContainerConfiguration($loader);
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        return $this->kernel->boot();
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        return $this->kernel->shutdown();
    }

    /**
     * {@inheritdoc}
     */
    public function getBundles()
    {
        return $this->kernel->getBundles();
    }

    /**
     * {@inheritdoc}
     */
    public function getBundle($name, $first = true)
    {
        return $this->kernel->getBundle($name, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function locateResource($name, $dir = null, $first = true)
    {
        return $this->kernel->locateResource($name, $dir, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->kernel->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment()
    {
        return $this->kernel->getEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug()
    {
        return $this->kernel->isDebug();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return $this->kernel->getRootDir();
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return $this->kernel->getContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function getStartTime()
    {
        return $this->kernel->getStartTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->kernel->getCacheDir();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->kernel->getLogDir();
    }

    /**
     * {@inheritdoc}
     */
    public function getCharset()
    {
        return $this->kernel->getCharset();
    }

    /**
     * {@inheritdoc}
     */
    public function isClassInActiveBundle($class)
    {
        // necessary check, because method was removed in Symfony 3.0
        if (method_exists($this->kernel, 'isClassInActiveBundle')) {
            return $this->kernel->isClassInActiveBundle($class);
        }
    }
}

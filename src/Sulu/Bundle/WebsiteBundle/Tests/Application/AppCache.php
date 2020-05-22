<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Application;

use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AppCache extends SuluHttpCache implements KernelInterface
{
    public function __construct(KernelInterface $kernel, $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);
    }

    public function serialize()
    {
        return $this->kernel->serialize();
    }

    public function unserialize($serialized)
    {
        $this->kernel->unserialize($serialized);
    }

    public function registerBundles()
    {
        return $this->kernel->registerBundles();
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        return $this->kernel->registerContainerConfiguration($loader);
    }

    public function boot()
    {
        return $this->kernel->boot();
    }

    public function shutdown()
    {
        return $this->kernel->shutdown();
    }

    public function getBundles()
    {
        return $this->kernel->getBundles();
    }

    public function getBundle($name, $first = true)
    {
        return $this->kernel->getBundle($name, $first);
    }

    public function locateResource($name, $dir = null, $first = true)
    {
        return $this->kernel->locateResource($name, $dir, $first);
    }

    public function getName()
    {
        return $this->kernel->getName();
    }

    public function getEnvironment()
    {
        return $this->kernel->getEnvironment();
    }

    public function isDebug()
    {
        return $this->kernel->isDebug();
    }

    public function getRootDir()
    {
        return $this->kernel->getRootDir();
    }

    public function getProjectDir()
    {
        return $this->kernel->getProjectDir();
    }

    public function getContainer()
    {
        return $this->kernel->getContainer();
    }

    public function getStartTime()
    {
        return $this->kernel->getStartTime();
    }

    public function getCacheDir()
    {
        return $this->kernel->getCacheDir();
    }

    public function getLogDir()
    {
        return $this->kernel->getLogDir();
    }

    public function getCharset()
    {
        return $this->kernel->getCharset();
    }

    public function isClassInActiveBundle($class)
    {
        // necessary check, because method was removed in Symfony 3.0
        if (method_exists($this->kernel, 'isClassInActiveBundle')) {
            return $this->kernel->isClassInActiveBundle($class);
        }
    }
}

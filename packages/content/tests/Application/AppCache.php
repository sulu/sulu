<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Application;

use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class AppCache extends SuluHttpCache implements KernelInterface
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);
    }

    protected function createStore(): StoreInterface
    {
        return new Psr6Store([
            'cache' => new ArrayAdapter(),
            'lock_factory' => new LockFactory(new InMemoryStore()),
            'cache_tags_header' => TagHeaderFormatter::DEFAULT_HEADER_NAME,
        ]);
    }

    public function registerBundles(): iterable
    {
        return $this->kernel->registerBundles();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $this->kernel->registerContainerConfiguration($loader);
    }

    public function boot(): void
    {
        $this->kernel->boot();
    }

    public function shutdown(): void
    {
        $this->kernel->shutdown();
    }

    public function getBundles(): array
    {
        return $this->kernel->getBundles();
    }

    public function getBundle(string $name): BundleInterface
    {
        return $this->kernel->getBundle($name);
    }

    public function locateResource(string $name): string
    {
        return $this->kernel->locateResource($name);
    }

    public function getEnvironment(): string
    {
        return $this->kernel->getEnvironment();
    }

    public function isDebug(): bool
    {
        return $this->kernel->isDebug();
    }

    public function getProjectDir(): string
    {
        return $this->kernel->getProjectDir();
    }

    public function getContainer(): ContainerInterface
    {
        return $this->kernel->getContainer();
    }

    public function getStartTime(): float
    {
        return $this->kernel->getStartTime();
    }

    public function getCacheDir(): string
    {
        return $this->kernel->getCacheDir();
    }

    public function getBuildDir(): string
    {
        return $this->kernel->getBuildDir();
    }

    public function getLogDir(): string
    {
        return $this->kernel->getLogDir();
    }

    public function getCharset(): string
    {
        return $this->kernel->getCharset();
    }
}

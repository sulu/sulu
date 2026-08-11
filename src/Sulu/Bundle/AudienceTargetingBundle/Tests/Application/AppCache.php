<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Application;

use Sulu\Bundle\AudienceTargetingBundle\EventListener\AudienceTargetingCacheListener;
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AppCache extends SuluHttpCache implements KernelInterface
{
    public function __construct(KernelInterface $kernel, $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $this->addSubscriber(new AudienceTargetingCacheListener());
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

    public function getShareDir(): ?string
    {
        // before Symfony 7.4 the method is not part of the KernelInterface but SuluKernel provides it already
        return $this->kernel->getShareDir();
    }

    public function getLogDir(): string
    {
        // the KernelInterface returns a nullable string since Symfony 8 but requires a string before
        return $this->kernel->getLogDir() ?? '';
    }

    public function getCharset(): string
    {
        return $this->kernel->getCharset();
    }
}

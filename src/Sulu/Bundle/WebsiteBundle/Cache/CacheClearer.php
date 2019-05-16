<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Cache;

use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\WebsiteBundle\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Clear http_cache for website.
 */
class CacheClearer implements CacheClearerInterface
{
    /**
     * @var string
     */
    private $kernelRootDir;

    /**
     * @var string
     */
    private $kernelEnvironment;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $varDir;

    /**
     * @var null|CacheManager
     */
    private $cacheManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        Filesystem $filesystem,
        $kernelEnvironment,
        $kernelRootDir,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        $varDir = null,
        ?CacheManager $cacheManager
    ) {
        $this->kernelRootDir = $kernelRootDir;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->filesystem = $filesystem;
        $this->varDir = $varDir;
        $this->cacheManager = $cacheManager;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if ($this->cacheManager && $this->cacheManager->supportsInvalidate()) {
            $request = $this->requestStack->getCurrentRequest();
            if (!$request) {
                return;
            }

            $this->cacheManager->invalidateDomain($request->getHost());

            $this->eventDispatcher->dispatch(Events::CACHE_CLEAR);

            return;
        }

        $path = sprintf(
            '%s/cache/common/%s/http_cache',
            $this->varDir ?: $this->kernelRootDir,
            $this->kernelEnvironment
        );

        if ($this->filesystem->exists($path)) {
            $this->filesystem->remove($path);
        }

        $this->eventDispatcher->dispatch(Events::CACHE_CLEAR);
    }
}

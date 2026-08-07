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
use Sulu\Bundle\WebsiteBundle\Event\CacheClearEvent;
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
     * @param string $kernelEnvironment
     */
    public function __construct(
        private Filesystem $filesystem,
        private $kernelEnvironment,
        private RequestStack $requestStack,
        private EventDispatcherInterface $eventDispatcher,
        private string $varDir,
        private ?CacheManager $cacheManager,
        private bool $tagsEnabled = true,
    ) {
    }

    public function clear(?array $tags = null)
    {
        if (null !== $tags && $this->tagsEnabled && $this->cacheManager && $this->cacheManager->supportsTags()) {
            foreach ($tags as $tag) {
                $this->cacheManager->invalidateTag($tag);
            }

            $this->eventDispatcher->dispatch(new CacheClearEvent($tags), Events::CACHE_CLEAR);

            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request && $this->cacheManager && $this->cacheManager->supportsInvalidate()) {
            $this->cacheManager->invalidateDomain($request->getHost());
            $this->eventDispatcher->dispatch(new CacheClearEvent(), Events::CACHE_CLEAR);

            return;
        }

        if ($this->cacheManager && $this->cacheManager->supportsClear()) {
            $this->cacheManager->clear();
            $this->eventDispatcher->dispatch(new CacheClearEvent(), Events::CACHE_CLEAR);

            return;
        }

        $path = \sprintf(
            '%s/cache/common/%s/http_cache',
            $this->varDir,
            $this->kernelEnvironment
        );

        if ($this->filesystem->exists($path)) {
            // rename directory before removing it to prevent new requests from writing into the old directory
            $invalidatedPath = $path . '_invalidated';
            $this->filesystem->rename($path, $invalidatedPath, true);
            $this->filesystem->remove($invalidatedPath);
        }

        $this->eventDispatcher->dispatch(new CacheClearEvent(), Events::CACHE_CLEAR);
    }
}

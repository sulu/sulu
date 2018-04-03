<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Cache;

use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
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

    public function __construct(
        Filesystem $filesystem,
        $kernelEnvironment,
        $kernelRootDir,
        RequestStack $requestStack,
        $varDir = null,
        ?CacheManager $cacheManager
    ) {
        $this->kernelRootDir = $kernelRootDir;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->filesystem = $filesystem;
        $this->varDir = $varDir;
        $this->cacheManager = $cacheManager;
        $this->requestStack = $requestStack;
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

            return;
        }

        $path = sprintf(
            '%s/cache/website/%s/http_cache',
            $this->varDir ?: $this->kernelRootDir,
            $this->kernelEnvironment
        );

        if ($this->filesystem->exists($path)) {
            $this->filesystem->remove($path);
        }
    }
}

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

use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Clear http_cache for website.
 */
class CacheClearer implements CacheClearerInterface
{
    /**
     * Will be raised after caches have been cleared.
     */
    const CACHE_CLEAR_EVENT = 'sulu_website.cache_clear';

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
     * @var ProxyClientInterface
     */
    private $proxyClient;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param Filesystem $filesystem
     * @param $kernelEnvironment
     * @param $kernelRootDir
     * @param RequestStack $requestStack
     * @param EventDispatcher $eventDispatcher
     * @param string $varDir
     * @param ProxyClientInterface $proxyClient
     */
    public function __construct(
        Filesystem $filesystem,
        $kernelEnvironment,
        $kernelRootDir,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        $varDir = null,
        ProxyClientInterface $proxyClient = null
    ) {
        $this->kernelRootDir = $kernelRootDir;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->filesystem = $filesystem;
        $this->varDir = $varDir;
        $this->proxyClient = $proxyClient;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if ($this->proxyClient instanceof BanInterface) {
            $request = $this->requestStack->getCurrentRequest();
            if (!$request) {
                return;
            }

            $this->proxyClient->banPath(
                BanInterface::REGEX_MATCH_ALL,
                BanInterface::CONTENT_TYPE_ALL,
                [$request->getHost()]
            );

            $this->proxyClient->flush();

            $this->eventDispatcher->dispatch(self::CACHE_CLEAR_EVENT);

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

        $this->eventDispatcher->dispatch(self::CACHE_CLEAR_EVENT);
    }
}

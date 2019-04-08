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
     * @var ProxyClientInterface
     */
    private $proxyClient;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param Filesystem $filesystem
     * @param $kernelEnvironment
     * @param $kernelRootDir
     * @param RequestStack $requestStack
     * @param EventDispatcherInterface $eventDispatcher
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

            $this->eventDispatcher->dispatch(Events::CACHE_CLEAR);

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

        $this->eventDispatcher->dispatch(Events::CACHE_CLEAR);
    }
}

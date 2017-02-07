<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Handler;

use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Sulu\Component\HttpCache\HandlerFlushInterface;
use Sulu\Component\HttpCache\HandlerInvalidatePathInterface;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Invalidation service for paths and urls.
 */
class UrlHandler implements HandlerInvalidatePathInterface, HandlerFlushInterface
{
    /**
     * @var ProxyClientInterface
     */
    private $proxyClient;

    /**
     * @var ReplacerInterface
     */
    private $replacer;

    /**
     * @var string
     */
    private $requestHost;

    /**
     * @var string[]
     */
    private $pathsToInvalidate = [];

    /**
     * @param ProxyClientInterface $proxyClient
     * @param RequestStack $requestStack
     * @param ReplacerInterface $replacer
     */
    public function __construct(
        ProxyClientInterface $proxyClient,
        RequestStack $requestStack,
        ReplacerInterface $replacer
    ) {
        $this->proxyClient = $proxyClient;
        $this->replacer = $replacer;
        $this->requestHost = ($requestStack->getCurrentRequest()) ? $requestStack->getCurrentRequest()->getHost() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidatePath($path, array $headers = [])
    {
        $path = ($this->requestHost) ? $this->replacer->replaceHost($path, $this->requestHost) : $path;
        $this->pathsToInvalidate[] = ['path' => $path, 'headers' => $headers];
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if (!$this->proxyClient instanceof PurgeInterface) {
            return false;
        }

        if (!$this->pathsToInvalidate) {
            return true;
        }

        foreach ($this->pathsToInvalidate as $entry) {
            $this->proxyClient->purge($entry['path'], $entry['headers']);
        }

        $this->proxyClient->flush();

        return true;
    }
}

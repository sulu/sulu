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

use FOS\HttpCache\Exception\UnsupportedProxyOperationException;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Sulu\Component\HttpCache\HandlerFlushInterface;
use Sulu\Component\HttpCache\HandlerInvalidatePathInterface;

/**
 * Invalidation service for paths.
 */
class UrlHandler implements HandlerInvalidatePathInterface, HandlerFlushInterface
{
    /**
     * @var ProxyClientInterface
     */
    private $proxyClient;

    /**
     * @var string[]
     */
    private $pathsToInvalidate = [];

    /**
     * {@inheritdoc}
     */
    public function invalidatePath($path, array $headers = [])
    {
        $this->pathsToInvalidate[] = ['path' => $path, 'headers' => $headers];
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if (!$this->proxyClient instanceof PurgeInterface) {
            throw UnsupportedProxyOperationException::cacheDoesNotImplement('PURGE');
        }

        foreach ($this->pathsToInvalidate as $item) {
            $this->proxyClient->purge($item['path'], $item['headers']);
        }

        $this->proxyClient->flush();

        return true;
    }
}

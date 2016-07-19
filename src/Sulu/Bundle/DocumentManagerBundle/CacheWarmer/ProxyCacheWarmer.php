<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\CacheWarmer;

use ProxyManager\Configuration;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms the cache for the proxy files of the document manager.
 */
class ProxyCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var Configuration
     */
    private $proxyConfiguration;

    /**
     * @param Configuration $proxyConfiguration
     */
    public function __construct(Configuration $proxyConfiguration)
    {
        $this->proxyConfiguration = $proxyConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $proxyCacheDirectory = $this->proxyConfiguration->getProxiesTargetDir();

        if (!is_dir($proxyCacheDirectory)) {
            mkdir($proxyCacheDirectory, 0777, true);
        }
    }
}

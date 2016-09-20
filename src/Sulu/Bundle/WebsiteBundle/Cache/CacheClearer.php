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

use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
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
     * @param Filesystem $filesystem
     * @param $kernelEnvironment
     * @param $kernelRootDir
     * @param RequestStack $requestStack
     * @param string $varDir
     * @param ProxyClientInterface $proxyClient
     */
    public function __construct(
        Filesystem $filesystem,
        $kernelEnvironment,
        $kernelRootDir,
        RequestStack $requestStack,
        $varDir = null,
        ProxyClientInterface $proxyClient = null
    ) {
        $this->kernelRootDir = $kernelRootDir;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->filesystem = $filesystem;
        $this->varDir = $varDir;
        $this->proxyClient = $proxyClient;
        $this->requestStack = $requestStack;
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

            return $this->proxyClient->flush();
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

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

use FOS\HttpCache\ProxyClient;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\HandlerFlushInterface;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Invalidate all the paths (i.e. old and new) for a Sulu Structure.
 */
class PathsHandler implements HandlerFlushInterface, HandlerInvalidateStructureInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ProxyClientInterface
     */
    private $proxyClient;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var array
     */
    private $structuresToInvalidate = [];

    /**
     * @param WebspaceManagerInterface $webspaceManager
     * @param ProxyClientInterface     $proxyClient
     * @param string                   $environment     - kernel envionment, dev, prod, etc.
     */
    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        PurgeInterface $proxyClient,
        $environment
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->proxyClient = $proxyClient;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateStructure(StructureInterface $structure)
    {
        if (!$this->proxyClient instanceof PurgeInterface) {
            return;
        }

        $this->structuresToInvalidate[] = $structure;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if (!$this->structuresToInvalidate) {
            return;
        }

        foreach ($this->structuresToInvalidate as $structure) {
            if (false === $structure->hasTag('sulu.rlp') ||  null === $rlp = $structure->getPropertyValueByTagName('sulu.rlp')) {
                return;
            }

            $urls = $this->webspaceManager->findUrlsByResourceLocator(
                $rlp,
                $this->environment,
                $structure->getLanguageCode(),
                $structure->getWebspaceKey()
            );

            foreach ($urls as $url) {
                $this->proxyClient->purge($url);
            }
        }

        $this->proxyClient->flush();
    }
}

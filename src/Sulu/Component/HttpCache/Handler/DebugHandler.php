<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Handler;

use FOS\HttpCache\ProxyClient;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use Sulu\Component\Content\PageInterface;
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\HttpCache\HandlerUpdateResponseInterface;

/**
 * Adds some debug information
 */
class DebugHandler implements 
    HandlerUpdateResponseInterface
{
    const HEADER_HANDLERS = 'X-Sulu-Handlers';
    const HEADER_CLIENT_NAME = 'X-Sulu-Proxy-Client';
    const HEADER_STRUCTURE_TYPE = 'X-Sulu-Structure-Type';
    const HEADER_STRUCTURE_UUID = 'X-Sulu-Structure-UUID';
    const HEADER_STRUCTURE_TTL = 'X-Sulu-Page-TTL';

    /**
     * @var string[]
     */
    private $handlerNames;

    /**
     * @var string
     */
    private $proxyClientName;

    /**
     * @param array List of handlers (strings)
     * @param string Current proxy client nme
     */
    public function __construct(
        $handlerNames,
        $proxyClientName
    ) {
        $this->handlerNames = $handlerNames;
        $this->proxyClientName = $proxyClientName;
    }

    /**
     * {@inheritDoc}
     */
    public function updateResponse(Response $response, StructureInterface $structure)
    {
        $response->headers->set(self::HEADER_HANDLERS, implode(', ', $this->handlerNames));
        $response->headers->set(self::HEADER_CLIENT_NAME, $this->proxyClientName);
        $response->headers->set(self::HEADER_STRUCTURE_TYPE, get_class($structure));
        $response->headers->set(self::HEADER_STRUCTURE_UUID, $structure->getUuid());

        // Structures implementing PageInterface have a TTL
        if ($structure instanceOf PageInterface) {
            $response->headers->set(self::HEADER_STRUCTURE_TTL, $structure->getCacheLifeTime());
        }
    }
}

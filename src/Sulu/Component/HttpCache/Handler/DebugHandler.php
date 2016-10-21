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

use Sulu\Component\Content\Compat\PageInterface;
use Sulu\Component\Content\Compat\Structure\Page;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\CacheLifetimeResolverInterface;
use Sulu\Component\HttpCache\HandlerUpdateResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds some debug information.
 */
class DebugHandler implements HandlerUpdateResponseInterface
{
    const HEADER_HANDLERS = 'X-Sulu-Handlers';
    const HEADER_CLIENT_NAME = 'X-Sulu-Proxy-Client';
    const HEADER_STRUCTURE_TYPE = 'X-Sulu-Structure-Type';
    const HEADER_STRUCTURE_UUID = 'X-Sulu-Structure-UUID';
    const HEADER_STRUCTURE_TTL = 'X-Sulu-Page-TTL';

    /**
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    /**
     * @var string[]
     */
    private $handlerNames;

    /**
     * @var string
     */
    private $proxyClientName;

    /**
     * @param CacheLifetimeResolverInterface $cacheLifetimeResolver
     * @param array $handlerNames List of handlers (strings)
     * @param string $proxyClientName Current proxy client name
     */
    public function __construct(
        CacheLifetimeResolverInterface $cacheLifetimeResolver,
        $handlerNames,
        $proxyClientName
    ) {
        $this->cacheLifetimeResolver = $cacheLifetimeResolver;
        $this->handlerNames = $handlerNames;
        $this->proxyClientName = $proxyClientName;
    }

    /**
     * {@inheritdoc}
     */
    public function updateResponse(Response $response, StructureInterface $structure)
    {
        $response->headers->set(self::HEADER_HANDLERS, implode(', ', $this->handlerNames));
        $response->headers->set(self::HEADER_CLIENT_NAME, $this->proxyClientName);
        $response->headers->set(self::HEADER_STRUCTURE_TYPE, get_class($structure));
        $response->headers->set(self::HEADER_STRUCTURE_UUID, $structure->getUuid());

        // Structures implementing PageInterface have a TTL
        if ($structure instanceof PageInterface) {
            $cacheLifetimeData = $structure->getCacheLifeTime();
            $cacheLifeTime = $this->cacheLifetimeResolver->resolve($cacheLifetimeData['type'], $cacheLifetimeData['value']);
            $response->headers->set(self::HEADER_STRUCTURE_TTL, $cacheLifeTime);
        }
    }
}

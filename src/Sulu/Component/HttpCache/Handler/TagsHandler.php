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

use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Sulu\Bundle\ContentBundle\ReferenceStore\ReferenceStorePoolInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\HandlerFlushInterface;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;
use Sulu\Component\HttpCache\HandlerUpdateResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invalidation service for sulu structures.
 */
class TagsHandler implements HandlerInvalidateStructureInterface, HandlerUpdateResponseInterface, HandlerFlushInterface
{
    const TAGS_HEADER = 'X-Cache-Tags';

    /**
     * @var ProxyClientInterface
     */
    private $proxyClient;

    /**
     * @var ReferenceStorePoolInterface
     */
    private $referenceStore;

    /**
     * @var array
     */
    private $structuresToInvalidate;

    /**
     * @param ProxyClientInterface $proxyClient
     * @param ReferenceStorePoolInterface $referenceStore
     */
    public function __construct(ProxyClientInterface $proxyClient, ReferenceStorePoolInterface $referenceStore)
    {
        $this->proxyClient = $proxyClient;
        $this->referenceStore = $referenceStore;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateStructure(StructureInterface $structure)
    {
        $this->structuresToInvalidate[$structure->getUuid()] = $structure;
    }

    /**
     * {@inheritdoc}
     */
    public function updateResponse(Response $response, StructureInterface $structure)
    {
        $tags = array_merge([$structure->getUuid()], $this->referenceStore->getReferences());

        $response->headers->set(self::TAGS_HEADER, implode(',', $tags));
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if (!$this->structuresToInvalidate) {
            return false;
        }

        foreach ($this->structuresToInvalidate as $structure) {
            $this->proxyClient->ban(
                [
                    self::TAGS_HEADER => sprintf('(%s)(,.+)?$', preg_quote($structure->getUuid())),
                ]
            );
        }

        $this->proxyClient->flush();

        return true;
    }
}

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
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\HttpCache\HandlerFlushInterface;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;
use Sulu\Component\HttpCache\HandlerUpdateResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invalidation service for Sulu structures.
 */
class TagsHandler implements
    HandlerInvalidateStructureInterface,
    HandlerUpdateResponseInterface,
    HandlerFlushInterface
{
    const TAGS_HEADER = 'X-Cache-Tags';

    /**
     * @var ProxyClientInterface
     */
    private $proxyClient;

    /**
     * @var ContentTypeManager
     */
    private $contentTypeManager;

    /**
     * @var array
     */
    private $structuresToInvalidate;

    /**
     * @param ProxyClientInterface $proxyClient
     * @param ContentTypeManagerInterface $contentTypeManager
     */
    public function __construct(
        ProxyClientInterface $proxyClient,
        ContentTypeManagerInterface $contentTypeManager
    ) {
        $this->proxyClient = $proxyClient;
        $this->contentTypeManager = $contentTypeManager;
    }

    public function invalidateStructure(StructureInterface $structure)
    {
        $this->structuresToInvalidate[$structure->getUuid()] = $structure;
    }

    public function updateResponse(Response $response, StructureInterface $structure)
    {
        $tags = [
            $this->getBanKey($structure->getUuid()),
        ];

        foreach ($structure->getProperties(true) as $property) {
            foreach ($this->getReferencedUuids($property) as $uuid) {
                $tags[] = $this->getBanKey($uuid);
            }
        }

        $response->headers->set(
            self::TAGS_HEADER,
            implode(',', $tags)
        );
    }

    private function getReferencedUuids(PropertyInterface $property)
    {
        $contentTypeName = $property->getContentTypeName();
        $contentType = $this->contentTypeManager->get($contentTypeName);
        $referencedUuids = $contentType->getReferencedUuids($property);

        return $referencedUuids;
    }

    private function getBanKey($uuid)
    {
        return 'structure-' . $uuid;
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
            $banKey = $this->getBanKey($structure->getUuid());

            $this->proxyClient->ban([
                self::TAGS_HEADER => sprintf('(%s)(,.+)?$', preg_quote($banKey)),
            ]);
        }

        $this->proxyClient->flush();

        return true;
    }
}

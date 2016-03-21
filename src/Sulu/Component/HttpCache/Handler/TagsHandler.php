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

use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\HttpCache\HandlerFlushInterface;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;
use Sulu\Component\HttpCache\HandlerUpdateResponseInterface;
use Sulu\Component\HttpCache\ProxyClient\Invalidation\TagInterface;
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
     * @param WebspaceManagerInterface $webspaceManager
     * @param CacheHandler             $cacheInvalidator
     * @param string                   $environment      - kernel envionment, dev, prod, etc.
     * @param null                     $logger
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
            $this->getTagKey($structure->getUuid()),
        ];

        foreach ($structure->getProperties(true) as $property) {
            foreach ($this->getReferencedUuids($property) as $uuid) {
                $tags[] = $this->getTagKey($uuid);
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

    private function getTagKey($uuid)
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

        $tags = [];
        foreach ($this->structuresToInvalidate as $structure) {
            $tags[] = $this->getTagKey($structure->getUuid());
        }

        $this->doInvalidate($tags);
        $this->proxyClient->flush();
    }

    /**
     * TODO: In the future the FOSHttpClient Varnish proxy will implement the TagInterface and this
     *       code will no longer be necessary.
     */
    private function doInvalidate(array $tags)
    {
        if ($this->proxyClient instanceof TagInterface) {
            $this->proxyClient->invalidateTags($tags);

            return;
        }

        if ($this->proxyClient instanceof BanInterface) {
            $tagExpression = sprintf('(%s)(,.+)?$', implode('|', array_map('preg_quote', $this->escapeTags($tags))));
            $this->proxyClient->ban([
                self::TAGS_HEADER => $tagExpression,
            ]);

            return;
        }

        throw new \RuntimeException(sprintf(
            'Proxy client must either support BAN (BanInterface) or tag invalidation (TagInterface), "%s" supports neither',
            get_class($this->proxyClient)
        ));
    }

    /**
     * Escape tags (should be handled by the client, see comment on `doInvalidate`.
     */
    private function escapeTags(array $tags)
    {
        array_walk($tags, function (&$tag) {
            $tag = str_replace([',', "\n"], ['_', '_'], $tag);
        });

        return $tags;
    }
}

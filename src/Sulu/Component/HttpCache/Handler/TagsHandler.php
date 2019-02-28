<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Handler;

use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\HandlerFlushInterface;
use Sulu\Component\HttpCache\HandlerInvalidateReferenceInterface;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;
use Sulu\Component\HttpCache\HandlerUpdateResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invalidation service for sulu structures.
 */
class TagsHandler implements HandlerInvalidateStructureInterface, HandlerInvalidateReferenceInterface, HandlerUpdateResponseInterface, HandlerFlushInterface
{
    const TAGS_HEADER = 'X-Cache-Tags';

    /**
     * @var ProxyClientInterface
     */
    private $proxyClient;

    /**
     * @var ReferenceStorePoolInterface
     */
    private $referenceStorePool;

    /**
     * @var array
     */
    private $referencesToInvalidate = [];

    /**
     * @param ProxyClientInterface $proxyClient
     * @param ReferenceStorePoolInterface $referenceStorePool
     */
    public function __construct(ProxyClientInterface $proxyClient, ReferenceStorePoolInterface $referenceStorePool)
    {
        $this->proxyClient = $proxyClient;
        $this->referenceStorePool = $referenceStorePool;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateStructure(StructureInterface $structure)
    {
        $this->referencesToInvalidate[] = $structure->getUuid();
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateReference($alias, $id)
    {
        $reference = $id;
        if (!Uuid::isValid($id)) {
            $reference = sprintf('%s-%s', $alias, $id);
        }

        if (in_array($reference, $this->referencesToInvalidate)) {
            return;
        }

        $this->referencesToInvalidate[] = $reference;
    }

    /**
     * {@inheritdoc}
     */
    public function updateResponse(Response $response, StructureInterface $structure)
    {
        $tags = array_merge([$structure->getUuid()], $this->getTags());

        $response->headers->set(self::TAGS_HEADER, implode(',', $tags));
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if (0 === count($this->referencesToInvalidate)) {
            return false;
        }

        foreach ($this->referencesToInvalidate as $reference) {
            $this->proxyClient->ban(
                [
                    self::TAGS_HEADER => sprintf('(%s)(,.+)?$', preg_quote($reference)),
                ]
            );
        }

        $this->proxyClient->flush();
        $this->referencesToInvalidate = [];

        return true;
    }

    /**
     * Merges tags from all registered stores.
     *
     * @return array
     */
    private function getTags()
    {
        $tags = [];
        foreach ($this->referenceStorePool->getStores() as $alias => $referenceStore) {
            $tags = array_merge($tags, $this->getTagsFromStore($alias, $referenceStore));
        }

        return $tags;
    }

    /**
     * Returns tags from given store.
     *
     * @param string $alias
     * @param ReferenceStoreInterface $referenceStore
     *
     * @return array
     */
    private function getTagsFromStore($alias, ReferenceStoreInterface $referenceStore)
    {
        $tags = [];
        foreach ($referenceStore->getAll() as $reference) {
            $tag = $reference;
            if (!Uuid::isValid($reference)) {
                $tag = $alias . '-' . $reference;
            }

            $tags[] = $tag;
        }

        return $tags;
    }
}

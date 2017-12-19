<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\EventSubscriber;

use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds tags to the Symfony response tagger from all available reference stores.
 */
class TagsSubscriber implements EventSubscriberInterface
{
    /**
     * @var ReferenceStorePoolInterface
     */
    private $referenceStorePool;

    /**
     * @var SymfonyResponseTagger
     */
    private $symfonyResponseTagger;

    public function __construct(
        ReferenceStorePoolInterface $referenceStorePool,
        SymfonyResponseTagger $symfonyResponseTagger
    ) {
        $this->referenceStorePool = $referenceStorePool;
        $this->symfonyResponseTagger = $symfonyResponseTagger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['addTags', 1024],
        ];
    }

    /**
     * Adds tags from the reference store to the response tagger.
     */
    public function addTags()
    {
        $this->symfonyResponseTagger->addTags($this->getTags());
    }

    /**
     * Merges tags from all registered stores.
     */
    private function getTags(): array
    {
        $tags = [];
        foreach ($this->referenceStorePool->getStores() as $alias => $referenceStore) {
            $tags = array_merge($tags, $this->getTagsFromStore($alias, $referenceStore));
        }

        return $tags;
    }

    /**
     * Returns tags from given store.
     */
    private function getTagsFromStore($alias, ReferenceStoreInterface $referenceStore): array
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

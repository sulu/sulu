<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\EventSubscriber;

use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

/**
 * @internal This class should not be extended or initialized by any application outside of sulu.
 *           You can create your own response listener to change the behaviour or use Symfony
 *           dependency injection container to replace this service.
 */
class TagsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ReferenceStorePoolInterface $referenceStorePool,
        private SymfonyResponseTagger $symfonyResponseTagger,
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['addTags', 1024],
        ];
    }

    public function addTags(): void
    {
        $tags = $this->getTags();
        $objectTag = $this->getObjectTag();
        if ($objectTag && !\in_array($objectTag, $tags)) {
            $tags[] = $objectTag;
        }

        if (\count($tags) <= 0) {
            return;
        }

        $this->symfonyResponseTagger->addTags($tags);
    }

    private function getTags(): array
    {
        $tags = [];
        foreach ($this->referenceStorePool->getStores() as $alias => $referenceStore) {
            $tags = \array_merge($tags, $this->getTagsFromStore($alias, $referenceStore));
        }

        return $tags;
    }

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

    private function getObjectTag(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $object = $request->attributes->get('object');
        if (!$object instanceof DimensionContentInterface) {
            return null;
        }

        $objectTag = (string) $object->getResource()->getId();
        if (Uuid::isValid($objectTag)) {
            return $objectTag;
        }

        return $object::getResourceKey() . '-' . $objectTag;
    }
}

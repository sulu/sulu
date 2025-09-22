<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Infrastructure\Sulu\HttpCache\EventSubscriber;

use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal This class should not be extended or initialized by any application outside of sulu.
 *           You can create your own response listener to change the behaviour or use Symfony
 *           dependency injection container to replace this service.
 */
final class DimensionContentTagSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ReferenceStoreInterface $referenceStore,
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['addTag', 2048], // Priority needs to be higher than the TagsSubscriber (1024)
        ];
    }

    public function addTag(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $object = $request->attributes->get('object');
        if (!$object instanceof DimensionContentInterface) {
            return;
        }

        $objectId = (string) $object->getResource()->getId();
        $resourceKey = $object::getResourceKey();

        $this->referenceStore->add($objectId, $resourceKey);
    }
}

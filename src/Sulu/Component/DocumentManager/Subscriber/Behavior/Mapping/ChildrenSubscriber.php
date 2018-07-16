<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping;

use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\ProxyFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set the children on the document.
 */
class ChildrenSubscriber implements EventSubscriberInterface
{
    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @param ProxyFactory $proxyFactory
     */
    public function __construct(ProxyFactory $proxyFactory)
    {
        $this->proxyFactory = $proxyFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => 'handleHydrate',
        ];
    }

    /**
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ChildrenBehavior) {
            return;
        }

        $accessor = $event->getAccessor();
        $accessor->set('children', $this->proxyFactory->createChildrenCollection($document, $event->getOptions()));
    }
}

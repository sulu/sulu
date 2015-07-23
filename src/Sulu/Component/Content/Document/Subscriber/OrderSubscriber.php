<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\PropertyType;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Create a property with a value corresponding to the position of the node
 * relative to its siblings.
 */
class OrderSubscriber implements EventSubscriberInterface
{
    const FIELD = 'order';

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    public function __construct(PropertyEncoder $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * Checks if the given document is supported by this subscriber.
     *
     * @param $document
     *
     * @return bool
     */
    public function supports($document)
    {
        return $document instanceof OrderBehavior;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => 'handlePersist',
            Events::HYDRATE => 'handleHydrate',
            Events::REORDER => 'handleReorder',
        ];
    }

    /**
     * Adjusts the order of the document and its siblings.
     *
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $node = $event->getNode();
        $document = $event->getDocument();

        if (false == $this->supports($document)) {
            return;
        }

        $propertyName = $this->encoder->systemName(self::FIELD);

        if ($node->hasProperty($propertyName)) {
            return;
        }

        $parent = $node->getParent();
        $nodeCount = count($parent->getNodes());
        $order = ($nodeCount + 1) * 10;

        $node->setProperty($propertyName, $order, PropertyType::LONG);
        $this->handleHydrate($event);
    }

    /**
     * Adjusts the order of the document and its siblings.
     *
     * @param ReorderEvent $event
     */
    public function handleReorder(ReorderEvent $event)
    {
        $node = $event->getNode();
        $document = $event->getDocument();

        if (false == $this->supports($document)) {
            return;
        }

        $propertyName = $this->encoder->systemName(self::FIELD);

        $parent = $node->getParent();
        $count = 0;
        foreach ($parent->getNodes() as $childNode) {
            $childNode->setProperty($propertyName, ($count + 1) * 10, PropertyType::LONG);
            ++$count;
        }

        $this->handleHydrate($event);
    }

    /**
     * Adds the order to the document.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        if (false == $this->supports($event->getDocument())) {
            return;
        }

        $node = $event->getNode();

        $order = $node->getPropertyValueWithDefault(
            $this->encoder->systemName(self::FIELD),
            null
        );

        $event->getAccessor()->set('suluOrder', $order);
    }
}

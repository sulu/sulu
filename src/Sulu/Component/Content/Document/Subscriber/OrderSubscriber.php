<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use PHPCR\PropertyType;

/**
 * Create a property with a value corresponding to the position of the node
 * relative to its siblings.
 */
class OrderSubscriber implements EventSubscriberInterface
{
    const FIELD = 'order';

    private $encoder;

    public function __construct(PropertyEncoder $encoder)
    {
        $this->encoder = $encoder;
    }

    public function supports($document)
    {
        return $document instanceof OrderBehavior;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PERSIST => 'handlePersist',
        );
    }

    /**
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
    }
}

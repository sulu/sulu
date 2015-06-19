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
use PHPCR\PropertyType;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;

/**
 * Create a property with a value corresponding to the position of the node
 * relative to its siblings.
 */
class OrderSubscriber implements EventSubscriberInterface
{
    const FIELD = 'order';

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PERSIST => 'handlePersist',
            Events::METADATA_LOAD => 'handleMetadataLoad',
        );
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(OrderBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('suluOrder', array(
            'encoding' => 'system',
            'property' => self::FIELD,
        ));
    }

    /**
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (false == $document instanceof OrderBehavior) {
            return;
        }

        // TODO: This does not seem quite right..
        if ($document->getSuluOrder()) {
            return;
        }

        $node = $event->getNode();
        $parent = $node->getParent();
        $nodeCount = count($parent->getNodes());
        $order = ($nodeCount + 1) * 10;

        $event->getAccessor()->set('suluOrder', $order);
    }
}

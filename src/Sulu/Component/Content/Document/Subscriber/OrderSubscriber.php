<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Create a property with a value corresponding to the position of the node
 * relative to its siblings.
 */
class OrderSubscriber implements EventSubscriberInterface
{
    const FIELD = 'order';

    private $inspector;

    public function __construct(DocumentInspector $inspector)
    {
        $this->inspector = $inspector;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => 'handlePersist',
            Events::METADATA_LOAD => 'handleMetadataLoad',
            Events::REORDER => 'handleReorder',
        ];
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(OrderBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('suluOrder', [
            'encoding' => 'system',
            'property' => self::FIELD,
        ]);
    }

    /**
     * Adjusts the order of the document and its siblings.
     *
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (false == $document instanceof OrderBehavior) {
            return;
        }

        if ($document->getSuluOrder()) {
            return;
        }

        $node = $event->getNode();
        $parent = $node->getParent();
        $nodeCount = count($parent->getNodes());
        $order = ($nodeCount + 1) * 10;

        $event->getAccessor()->set('suluOrder', $order);
    }

    /**
     * Adjusts the order of the document and its siblings.
     *
     * @param ReorderEvent $event
     */
    public function handleReorder(ReorderEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof OrderBehavior) {
            return;
        }

        $parentDocument = $this->inspector->getParent($document);

        if (null === $parentDocument) {
            return;
        }

        $count = 0;
        foreach ($this->inspector->getChildren($parentDocument) as $childDocument) {
            if (!$childDocument instanceof OrderBehavior) {
                continue;
            }

            $accessor = new DocumentAccessor($childDocument);
            $order = ($count + 1) * 10;
            $accessor->set('suluOrder', $order);
            ++$count;
        }
    }
}

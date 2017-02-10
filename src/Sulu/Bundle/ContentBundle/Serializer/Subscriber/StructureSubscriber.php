<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\VisitorInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Structure\ManagedStructure;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * Normalize ManagedStructure instances to the Structure type.
 */
class StructureSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentInspector
     */
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
            [
                'event' => Events::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * @param PreSerializeEvent $event
     */
    public function onPreSerialize(PreSerializeEvent $event)
    {
        if ($event->getObject() instanceof Structure) {
            $event->setType(Structure::class);
        }
    }

    /**
     * Adds all the structure specific data (template, structure properties and breadcrumb) to the serialization.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $document = $event->getObject();
        $context = $event->getContext();

        if (!$document instanceof StructureBehavior) {
            return;
        }

        $visitor = $event->getVisitor();
        $structureMetadata = $this->inspector->getStructureMetadata($document);

        if ($structureMetadata) {
            $visitor->addData('template', $document->getStructureType());
            $visitor->addData('originTemplate', $document->getStructureType());
            $visitor->addData('internal', false);
            $visitor->addData(
                'localizedTemplate',
                $structureMetadata->getTitle(
                    $this->inspector->getLocale($document)
                )
            );

            if (array_search('defaultPage', $context->attributes->get('groups')->getOrElse([])) !== false) {
                $this->addStructureProperties($structureMetadata, $document, $visitor);
            }

            // create bread crumbs
            if (array_search('breadcrumbPage', $context->attributes->get('groups')->getOrElse([])) !== false) {
                $this->addBreadcrumb($document, $visitor);
            }
        }
    }

    /**
     * Adds the properties of the structure to the serialization.
     *
     * @param StructureBehavior $document
     * @param VisitorInterface $visitor
     */
    private function addStructureProperties(
        StructureMetadata $structureMetadata,
        StructureBehavior $document,
        VisitorInterface $visitor
    ) {
        /** @var ManagedStructure $structure */
        $structure = $document->getStructure();
        $data = $structure->toArray();
        foreach ($structureMetadata->getProperties() as $name => $property) {
            if ($name === 'title' || !array_key_exists($name, $data) || $property->hasTag('sulu.rlp')) {
                continue;
            }

            $visitor->addData($name, $data[$name]);
        }
    }

    /**
     * Adds the breadcrumb to the serialization.
     *
     * @param StructureBehavior $document
     * @param VisitorInterface $visitor
     */
    private function addBreadcrumb(StructureBehavior $document, VisitorInterface $visitor)
    {
        $items = [];
        $parentDocument = $this->inspector->getParent($document);
        while ($parentDocument instanceof StructureBehavior) {
            $item = [];
            if ($parentDocument instanceof UuidBehavior) {
                $item['uuid'] = $parentDocument->getUuid();
            }

            $item['title'] = $parentDocument->getStructure()->getProperty('title')->getValue();

            $items[] = $item;

            $parentDocument = $this->inspector->getParent($parentDocument);
        }

        $items = array_reverse($items);

        array_walk(
            $items,
            function (&$item, $index) {
                $item['depth'] = $index;
            }
        );

        $visitor->addData('breadcrumb', $items);
    }
}

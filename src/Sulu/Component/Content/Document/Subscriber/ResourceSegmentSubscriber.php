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

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;

class ResourceSegmentSubscriber extends AbstractMappingSubscriber
{
    private $inspector;

    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $inspector
    ) {
        parent::__construct($encoder);
        $this->inspector = $inspector;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // persist should happen before content is mapped
            Events::PERSIST => ['handlePersist', 10],
            // hydrate should happen afterwards
            Events::HYDRATE => ['handleHydrate', -10],
        ];
    }

    public function supports($document)
    {
        return $document instanceof ResourceSegmentBehavior && $document instanceof StructureBehavior;
    }

    /**
     * @param AbstractMappingEvent $event
     */
    public function doHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if ($document instanceof RedirectTypeBehavior && $document->getRedirectType() !== RedirectType::NONE) {
            return;
        }

        $node = $event->getNode();
        $property = $this->getResourceSegmentProperty($document);
        $originalLocale = $this->inspector->getOriginalLocale($document);
        $segment = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(
                $property->getName(),
                $originalLocale
            ),
            ''
        );

        $document->setResourceSegment($segment);
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        $property = $this->getResourceSegmentProperty($document);

        $document->getStructure()->getProperty(
            $property->getName()
        )->setValue($document->getResourceSegment());
    }

    private function getResourceSegmentProperty($document)
    {
        $structure = $this->inspector->getStructureMetadata($document);
        $property = $structure->getPropertyByTagName('sulu.rlp');

        if (!$property) {
            throw new \RuntimeException(
                sprintf(
                    'Structure "%s" does not have a "sulu.rlp" tag which is required for documents implementing the ' .
                    'ResourceSegmentBehavior. In "%s"',
                    $structure->name,
                    $structure->resource
                )
            );
        }

        return $property;
    }
}

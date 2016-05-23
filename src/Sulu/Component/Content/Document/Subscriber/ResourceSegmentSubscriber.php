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

use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * TODO: This could be made into a pure metadata subscriber if we make
 *       the resource locator a system property.
 */
class ResourceSegmentSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var RlpStrategyInterface
     */
    private $rlpStrategy;

    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $documentInspector,
        RlpStrategyInterface $rlpStrategy
    ) {
        $this->encoder = $encoder;
        $this->documentInspector = $documentInspector;
        $this->rlpStrategy = $rlpStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // persist should happen before content is mapped
            Events::PERSIST => [
                ['handlePersistDocument', 10],
                // has to happen after MappingSubscriber, because the mapped data is needed
                ['handlePersistRoute', -200],
            ],
            // hydrate should happen afterwards
            Events::HYDRATE => ['handleHydrate', -200],
        ];
    }

    /**
     * Checks if the given Document supports the operations done in this Subscriber.
     *
     * @param object $document
     *
     * @return bool
     */
    public function supports($document)
    {
        return $document instanceof ResourceSegmentBehavior && $document instanceof StructureBehavior;
    }

    /**
     * Sets the ResourceSegment of the document.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $node = $event->getNode();
        $property = $this->getResourceSegmentProperty($document);
        $locale = $this->documentInspector->getOriginalLocale($document);
        $segment = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(
                $property->getName(),
                $locale
            ),
            ''
        );

        $document->setResourceSegment($segment);
    }

    /**
     * Sets the ResourceSegment on the Structure.
     *
     * @param PersistEvent $event
     */
    public function handlePersistDocument(PersistEvent $event)
    {
        /** @var ResourceSegmentBehavior $document */
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $property = $this->getResourceSegmentProperty($document);
        $this->persistDocument($document, $property);
    }

    /**
     * Creates or updates the route for the document.
     *
     * @param PersistEvent $event
     */
    public function handlePersistRoute(PersistEvent $event)
    {
        /** @var ResourceSegmentBehavior $document */
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        if (!$event->getLocale()) {
            return;
        }

        if ($document instanceof HomeDocument) {
            return;
        }

        if ($document instanceof RedirectTypeBehavior && $document->getRedirectType() !== RedirectType::NONE) {
            return;
        }

        $this->persistRoute($document);
    }

    /**
     * Returns the property of the document's structure containing the ResourceSegment.
     *
     * @param $document
     *
     * @return PropertyMetadata
     */
    private function getResourceSegmentProperty($document)
    {
        $structure = $this->documentInspector->getStructureMetadata($document);
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

    /**
     * Sets the ResourceSegment to the given property of the given document.
     *
     * @param ResourceSegmentBehavior $document
     * @param PropertyMetadata $property
     */
    private function persistDocument(ResourceSegmentBehavior $document, PropertyMetadata $property)
    {
        $document->getStructure()->getProperty(
            $property->getName()
        )->setValue($document->getResourceSegment());
    }

    /**
     * Creates or updates the route of the document using the RlpStrategy.
     *
     * @param ResourceSegmentBehavior $document
     */
    private function persistRoute(ResourceSegmentBehavior $document)
    {
        $this->rlpStrategy->save($document, null);
    }
}

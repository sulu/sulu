<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;

/**
 * Adds information about the localization to the serialized information of a Document.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    public function __construct(DocumentInspector $documentInspector, DocumentRegistry $documentRegistry)
    {
        $this->documentInspector = $documentInspector;
        $this->documentRegistry = $documentRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * Adds the concrete languages available and the type (ghost or shadow) of the document to the serialization.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $document = $event->getObject();

        if (!$document instanceof LocaleBehavior || !$this->documentRegistry->hasDocument($document)) {
            return;
        }

        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        $availableLocales = $this->documentInspector->getLocales($document);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'availableLocales', $availableLocales),
            $availableLocales
        );

        $contentLocales = $this->documentInspector->getConcreteLocales($document);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'contentLocales', $contentLocales),
            $contentLocales
        );

        $localizationState = $this->documentInspector->getLocalizationState($document);

        $type = null;

        if (LocalizationState::GHOST === $localizationState) {
            $ghostLocale = $document->getLocale();
            $type = ['name' => 'ghost', 'value' => $ghostLocale];
            $visitor->visitProperty(
                new StaticPropertyMetadata('', 'ghostLocale', $ghostLocale),
                $ghostLocale
            );
        }

        if (LocalizationState::SHADOW === $localizationState) {
            $shadowLocale = $document->getLocale();
            $type = ['name' => 'shadow', 'value' => $shadowLocale];
            $visitor->visitProperty(
                new StaticPropertyMetadata('', 'shadowLocale', $shadowLocale),
                $shadowLocale
            );
        }

        if ($type) {
            // TODO should be removed at some point, and the ghostLocale resp. shadowLocale properties should be used
            $visitor->visitProperty(
                new StaticPropertyMetadata('', 'type', $type),
                $type
            );
        }
    }
}

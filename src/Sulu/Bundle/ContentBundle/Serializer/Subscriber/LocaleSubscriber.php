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

        $visitor = $event->getVisitor();

        $visitor->addData('concreteLanguages', $this->documentInspector->getConcreteLocales($document));

        $localizationState = $this->documentInspector->getLocalizationState($document);

        if ($localizationState === LocalizationState::GHOST) {
            $visitor->addData('type', ['name' => 'ghost', 'value' => $document->getLocale()]);
        }

        if ($localizationState === LocalizationState::SHADOW) {
            $visitor->addData('type', ['name' => 'shadow', 'value' => $document->getLocale()]);
        }
    }
}

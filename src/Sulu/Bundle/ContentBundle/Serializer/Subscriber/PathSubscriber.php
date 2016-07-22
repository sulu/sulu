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
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;

/**
 * Adds the relative path to the serialization of a document.
 */
class PathSubscriber implements EventSubscriberInterface
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
     * Adds the relative path to the serialization.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $visitor = $event->getVisitor();
        $document = $event->getObject();

        if (!$document instanceof PathBehavior || !$this->documentRegistry->hasDocument($document)) {
            return;
        }

        $visitor->addData('path', $this->documentInspector->getContentPath($document));
    }
}

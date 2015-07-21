<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use PHPCR\ItemNotFoundException;
use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Compat\Structure\Document;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;

/**
 * Handle document re-registration upon deserialization.
 *
 * Documents must implement the UuidBehavior.
 *
 * TODO: Remove this class if at all possible. The document should contain all the fields needed by the preview.
 * TODO: This class is hard-coded to the bundles PageDocument
 */
class PageDocumentSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentRegistry
     */
    private $registry;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param DocumentRegistry $registry
     * @param SessionInterface $session
     */
    public function __construct(
        DocumentRegistry $registry,
        SessionInterface $session
    ) {
        $this->registry = $registry;
        $this->session = $session;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_DESERIALIZE,
                'method' => 'onPostDeserialize',
            ],
        ];
    }

    /**
     * @param ObjectEvent $event
     */
    public function onPostDeserialize(ObjectEvent $event)
    {
        $document = $event->getObject();

        if (!$document instanceof PageDocument) {
            return;
        }

        if (!$document->getUuid()) {
            return;
        }

        try {
            $node = $this->session->getNodeByIdentifier($document->getUuid());
        } catch (ItemNotFoundException $e) {
            return;
        }

        if ($this->registry->hasNode($node)) {
            $registeredDocument = $this->registry->getDocumentForNode($node);
            $this->registry->deregisterDocument($registeredDocument);
        }

        // TODO use the original locale somehow
        $this->registry->registerDocument($document, $node, $document->getLocale());
    }
}

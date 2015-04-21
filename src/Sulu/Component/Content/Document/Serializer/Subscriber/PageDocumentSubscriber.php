<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Serializer\Subscriber;

use Sulu\Component\Content\Compat\Structure\Document;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\DocumentManager\Behavior\UuidBehavior;
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;

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
    private $registry;
    private $session;

    public function __construct(
        DocumentRegistry $registry,
        SessionInterface $session
    ) {
        $this->registry = $registry;
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event' => Events::POST_DESERIALIZE,
                'method' => 'onPostSerialize',
            ),
        );
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $document = $event->getObject();

        if (!$document instanceof PageDocument) {
            return;
        }

        $node = $this->session->getNodeByIdentifier($document->getUuid());

        if ($this->registry->hasNode($node)) {
            $registeredDocument = $this->registry->getDocumentForNode($node);
            $this->registry->deregisterDocument($registeredDocument);
        }

        $this->registry->registerDocument($document, $node);
    }
}

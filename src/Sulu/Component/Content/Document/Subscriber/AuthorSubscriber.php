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

use Sulu\Component\Content\Document\Behavior\AuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles author and authored.
 */
class AuthorSubscriber implements EventSubscriberInterface
{
    const AUTHORED_PROPERTY_NAME = 'authored';
    const AUTHOR_PROPERTY_NAME = 'author';

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @param PropertyEncoder $propertyEncoder
     */
    public function __construct(PropertyEncoder $propertyEncoder)
    {
        $this->propertyEncoder = $propertyEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => 'setAuthorOnDocument',
            Events::PERSIST => 'setAuthorOnNode',
            Events::PUBLISH => 'setAuthorOnNode',
        ];
    }

    /**
     * Set author/authored to document on-hydrate.
     *
     * @param HydrateEvent $event
     */
    public function setAuthorOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof LocalizedAuthorBehavior) {
            return;
        }

        $encoding = 'system_localized';
        if ($document instanceof AuthorBehavior) {
            $encoding = 'system';
        } elseif (!$event->getLocale()) {
            return;
        }

        $node = $event->getNode();
        $document->setAuthored(
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, self::AUTHORED_PROPERTY_NAME, $event->getLocale()),
                null
            )
        );
        $document->setAuthor(
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, self::AUTHOR_PROPERTY_NAME, $event->getLocale()),
                null
            )
        );
    }

    /**
     * Set author/authored to document on-persist.
     *
     * @param AbstractMappingEvent $event
     */
    public function setAuthorOnNode(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof LocalizedAuthorBehavior) {
            return;
        }

        // Set default value if authored is not set.
        if ($document->getAuthored() === null) {
            $document->setAuthored(new \DateTime());
        }

        // Set default value if author is not set.
        if ($document->getAuthor() === null) {
            $document->setAuthor($document->getCreator());
        }

        $encoding = 'system_localized';
        if ($document instanceof AuthorBehavior) {
            $encoding = 'system';
        } elseif (!$event->getLocale()) {
            return;
        }

        $node = $event->getNode();
        $node->setProperty(
            $this->propertyEncoder->encode($encoding, self::AUTHORED_PROPERTY_NAME, $event->getLocale()),
            $document->getAuthored()
        );
        $node->setProperty(
            $this->propertyEncoder->encode($encoding, self::AUTHOR_PROPERTY_NAME, $event->getLocale()),
            $document->getAuthor()
        );
    }
}

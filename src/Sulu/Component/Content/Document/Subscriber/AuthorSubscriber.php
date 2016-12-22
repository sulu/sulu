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
 * Handles authors and authored.
 */
class AuthorSubscriber implements EventSubscriberInterface
{
    const AUTHORED_PROPERTY_NAME = 'authored';
    const AUTHORS_PROPERTY_NAME = 'authors';

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
     * Set authors/authored to document on-hydrate.
     *
     * @param HydrateEvent $event
     */
    public function setAuthorOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof AuthorBehavior) {
            return;
        }

        $encoding = 'system';
        if ($document instanceof LocalizedAuthorBehavior) {
            if (!$event->getLocale()) {
                return;
            }

            $encoding = 'system_localized';
        }

        $node = $event->getNode();
        $document->setAuthored(
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, self::AUTHORED_PROPERTY_NAME, $event->getLocale()),
                null
            )
        );
        $document->setAuthors(
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, self::AUTHORS_PROPERTY_NAME, $event->getLocale()),
                []
            )
        );
    }

    /**
     * Set authors/authored to document on-persist.
     *
     * @param AbstractMappingEvent $event
     */
    public function setAuthorOnNode(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof AuthorBehavior) {
            return;
        }

        $encoding = 'system';
        if ($document instanceof LocalizedAuthorBehavior) {
            if (!$event->getLocale()) {
                return;
            }

            $encoding = 'system_localized';
        }

        $node = $event->getNode();
        $node->setProperty(
            $this->propertyEncoder->encode($encoding, self::AUTHORED_PROPERTY_NAME, $event->getLocale()),
            $document->getAuthored()
        );
        $node->setProperty(
            $this->propertyEncoder->encode($encoding, self::AUTHORS_PROPERTY_NAME, $event->getLocale()),
            $document->getAuthors()
        );
    }
}

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
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
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
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @param PropertyEncoder $propertyEncoder
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(PropertyEncoder $propertyEncoder, UserRepositoryInterface $userRepository)
    {
        $this->propertyEncoder = $propertyEncoder;
        $this->userRepository = $userRepository;
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
        if (null === $document->getAuthor() && null !== $document->getCreator()) {
            $user = $this->userRepository->findUserById($document->getCreator());

            if ($user && $user->getContact()) {
                $document->setAuthor($user->getContact()->getId());
            }
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

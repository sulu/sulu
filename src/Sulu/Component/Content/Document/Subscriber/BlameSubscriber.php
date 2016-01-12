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

use Sulu\Component\Content\Document\Behavior\BlameBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedBlameBehavior;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Manages user blame (log who creator the document and who updated it last).
 */
class BlameSubscriber implements EventSubscriberInterface
{
    const CREATOR = 'creator';
    const CHANGER = 'changer';

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @param TokenStorage $tokenStorage
     */
    public function __construct(TokenStorage $tokenStorage = null)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::CONFIGURE_OPTIONS => 'configureOptions',
            Events::PERSIST => 'handlePersist',
            Events::METADATA_LOAD => 'handleMetadataLoad',
        ];
    }

    /**
     * @param ConfigureOptionsEvent $event
     */
    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $event->getOptions()->setDefaults(
            [
                'user' => null,
            ]
        );
    }

    /**
     * Adds the creator and changer to the metadata for persisting.
     *
     * @param MetadataLoadEvent $event
     */
    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (!$metadata->getReflectionClass()->isSubclassOf(LocalizedBlameBehavior::class)) {
            return;
        }

        $encoding = 'system_localized';
        if ($metadata->getReflectionClass()->isSubclassOf(BlameBehavior::class)) {
            $encoding = 'system';
        }

        $metadata->addFieldMapping(
            'creator',
            [
                'encoding' => $encoding,
            ]
        );
        $metadata->addFieldMapping(
            'changer',
            [
                'encoding' => $encoding,
            ]
        );
    }

    /**
     * Persists the data of creator and changer to the Node.
     *
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof LocalizedBlameBehavior) {
            return;
        }

        $userId = $this->getUserId($event->getOptions());

        if (null === $userId) {
            return;
        }

        if (!$event->getLocale()) {
            return;
        }

        if (!$document->getCreator()) {
            $event->getAccessor()->set(self::CREATOR, $userId);
        }

        $event->getAccessor()->set(self::CHANGER, $userId);
    }

    /**
     * Either returns the user id from the options array, or sets the id of the user of the current session.
     *
     * @param $options
     *
     * @return int
     */
    private function getUserId($options)
    {
        if (isset($options['user'])) {
            return $options['user'];
        }

        if (null === $this->tokenStorage) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token || $token instanceof AnonymousToken) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'User must implement the Sulu UserInterface, got "%s"',
                    is_object($user) ? get_class($user) : gettype($user)
                )
            );
        }

        return $user->getId();
    }
}

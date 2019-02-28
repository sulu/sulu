<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\EventSubscriber\ORM;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Sulu\Component\Persistence\Model\UserBlameInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Ensure that blame can be assigned to users when they break things.
 *
 * Persists the user that created and the last user that changed ORM classes
 * implementing UserBlameInterface.
 */
class UserBlameSubscriber implements EventSubscriber
{
    const CHANGER_FIELD = 'changer';

    const CREATOR_FIELD = 'creator';

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $userClass;

    /**
     * @param TokenStorage $tokenStorage
     * @param string $userClass
     */
    public function __construct(TokenStorage $tokenStorage = null, $userClass)
    {
        $this->tokenStorage = $tokenStorage;
        $this->userClass = $userClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        $events = [
            Events::loadClassMetadata,
            Events::onFlush,
        ];

        return $events;
    }

    /**
     * Map creator and changer fields to User objects.
     *
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        $reflection = $metadata->getReflectionClass();

        if (null !== $reflection && $reflection->implementsInterface('Sulu\Component\Persistence\Model\UserBlameInterface')) {
            if (!$metadata->hasAssociation(self::CREATOR_FIELD)) {
                $metadata->mapManyToOne([
                    'fieldName' => self::CREATOR_FIELD,
                    'targetEntity' => $this->userClass,
                    'joinColumns' => [
                        [
                            'name' => 'idUsersCreator',
                            'onDelete' => 'SET NULL',
                            'referencedColumnName' => 'id',
                            'nullable' => true,
                        ],
                    ],
                ]);
            }

            if (!$metadata->hasAssociation(self::CHANGER_FIELD)) {
                $metadata->mapManyToOne([
                    'fieldName' => self::CHANGER_FIELD,
                    'targetEntity' => $this->userClass,
                    'joinColumns' => [
                        [
                            'name' => 'idUsersChanger',
                            'onDelete' => 'SET NULL',
                            'referencedColumnName' => 'id',
                            'nullable' => true,
                        ],
                    ],
                ]);
            }
        }
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        if (null === $this->tokenStorage) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        // if no token, do nothing
        if (null === $token || $token instanceof AnonymousToken) {
            return;
        }

        $user = $this->getUser($token);

        // if no sulu user, do nothing
        if (!$user instanceof UserInterface) {
            return;
        }

        $this->handleUserBlame($event, $user, true);
        $this->handleUserBlame($event, $user, false);
    }

    private function handleUserBlame(OnFlushEventArgs $event, UserInterface $user, bool $insertions)
    {
        $manager = $event->getEntityManager();
        $unitOfWork = $manager->getUnitOfWork();

        $entities = $insertions ? $unitOfWork->getScheduledEntityInsertions() :
            $unitOfWork->getScheduledEntityUpdates();

        foreach ($entities as $blameEntity) {
            if (!$blameEntity instanceof UserBlameInterface) {
                continue;
            }

            $meta = $manager->getClassMetadata(get_class($blameEntity));

            $changeset = $unitOfWork->getEntityChangeSet($blameEntity);
            $recompute = false;

            if ($insertions
                && (!isset($changeset[self::CREATOR_FIELD]) || null === $changeset[self::CREATOR_FIELD][1])
            ) {
                $meta->setFieldValue($blameEntity, self::CREATOR_FIELD, $user);
                $recompute = true;
            }

            if (!isset($changeset[self::CHANGER_FIELD]) || null === $changeset[self::CHANGER_FIELD][1]) {
                $meta->setFieldValue($blameEntity, self::CHANGER_FIELD, $user);
                $recompute = true;
            }

            if (true === $recompute) {
                $unitOfWork->recomputeSingleEntityChangeSet($meta, $blameEntity);
            }
        }
    }

    /**
     * Return the user from the token.
     *
     * @param TokenInterface $token
     *
     * @return UserInterface
     */
    private function getUser(TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        return $user;
    }
}

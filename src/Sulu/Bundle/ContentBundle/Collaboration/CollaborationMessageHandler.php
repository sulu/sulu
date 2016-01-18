<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Collaboration;

use Doctrine\Common\Cache\Cache;
use Ratchet\ConnectionInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Websocket\Exception\MissingParameterException;
use Sulu\Component\Websocket\MessageDispatcher\MessageBuilderInterface;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerException;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface;

/**
 * Handles messages for collaboration.
 *
 * @example {"command": "enter", "userId": "1", "id": "123-123-123", "type": "page"}
 *
 * The example calls the enter action and passes the current page and the user id
 * as message parameter
 */
class CollaborationMessageHandler implements MessageHandlerInterface
{
    /**
     * @var MessageBuilderInterface
     */
    private $messageBuilder;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var Cache
     */
    private $collaborationsEntityCache;

    /**
     * @var Cache
     */
    private $collaborationsConnectionCache;

    /**
     * @var ConnectionInterface[]
     */
    private $connections = [];

    public function __construct(
        MessageBuilderInterface $messageBuilder,
        UserRepositoryInterface $userRepository,
        Cache $collaborationsEntityCache,
        Cache $collaborationsConnectionCache
    ) {
        $this->messageBuilder = $messageBuilder;
        $this->userRepository = $userRepository;
        $this->collaborationsEntityCache = $collaborationsEntityCache;
        $this->collaborationsConnectionCache = $collaborationsConnectionCache;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ConnectionInterface $conn, array $message, MessageHandlerContext $context)
    {
        try {
            return $this->execute($conn, $context, $message);
        } catch (\Exception $e) {
            throw new MessageHandlerException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn, MessageHandlerContext $context)
    {
        $connectionId = $context->getId();

        foreach ($this->collaborationsConnectionCache->fetch($connectionId) as $connectionCollaboration) {
            /** @var Collaboration $connectionCollaboration */
            $type = $connectionCollaboration->getType();
            $id = $connectionCollaboration->getId();

            $this->removeCollaborator($type, $id, $connectionId, $connectionCollaboration->getUserId());

            $this->sendUpdate($type, $id, $this->getUsersInformation($type, $id));
        }
    }

    /**
     * Executes command.
     *
     * @param ConnectionInterface $conn
     * @param MessageHandlerContext $context
     * @param array $msg
     *
     * @return mixed|null
     *
     * @throws MissingParameterException
     */
    private function execute(ConnectionInterface $conn, MessageHandlerContext $context, array $msg)
    {
        if (!array_key_exists('command', $msg)) {
            throw new MissingParameterException('command');
        }
        $command = $msg['command'];
        $result = null;

        switch ($command) {
            case 'enter':
                $result = $this->enter($conn, $context, $msg);
                break;
            case 'leave':
                $result = $this->leave($context, $msg);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Command "%s" not known', $command));
                break;
        }

        return $result;
    }

    /**
     * Called when the user has entered the page.
     *
     * @param ConnectionInterface $conn
     * @param MessageHandlerContext $context
     * @param array $msg
     *
     * @return array
     */
    private function enter(ConnectionInterface $conn, MessageHandlerContext $context, array $msg)
    {
        $user = $this->userRepository->findUserById($msg['userId']);
        $this->addCollaborator($conn, $msg['type'], $msg['id'], $context->getId(), $user);

        $users = $this->getUsersInformation($msg['type'], $msg['id']);

        $this->sendUpdate($msg['type'], $msg['id'], $users);

        return [
            'type' => $msg['type'],
            'id' => $msg['id'],
            'users' => $users,
        ];
    }

    /**
     * Called when the user has left the page.
     *
     * @param MessageHandlerContext $context
     * @param array $msg
     *
     * @return array
     */
    private function leave(MessageHandlerContext $context, array $msg)
    {
        $this->removeCollaborator($msg['type'], $msg['id'], $context->getId(), $msg['userId']);

        $users = $this->getUsersInformation($msg['type'], $msg['id']);

        $this->sendUpdate($msg['type'], $msg['id'], $users);

        return [
            'type' => $msg['type'],
            'id' => $msg['id'],
            'users' => $users,
        ];
    }

    /**
     * Adds the collaborator with the given connection and user to the entity with the specified identifier.
     *
     * @param ConnectionInterface $conn The connection of the user
     * @param string $type The type of the entity
     * @param mixed $id The id of the entity
     * @param string $connectionId The id of the connection of the user
     * @param UserInterface $user The user being added as collaborator
     */
    private function addCollaborator(ConnectionInterface $conn, $type, $id, $connectionId, UserInterface $user)
    {
        $identifier = $this->getUniqueCollaborationKey($type, $id);
        $userId = $user->getId();

        if (!array_key_exists($userId, $this->connections)) {
            $this->connections[$userId] = $conn;
        }

        $collaboration = new Collaboration(
            $userId,
            $user->getUsername(),
            $user->getFullName(),
            $type,
            $id
        );

        $entityCollaborations = $this->collaborationsEntityCache->fetch($identifier) ?: [];
        if (!array_key_exists($identifier, $entityCollaborations)) {
            $entityCollaborations[$userId] = $collaboration;
            $this->collaborationsEntityCache->save($identifier, $entityCollaborations);
        }

        $connectionCollaborations = $this->collaborationsConnectionCache->fetch($connectionId) ?: [];
        if (!array_key_exists($connectionId, $connectionCollaborations)) {
            $connectionCollaborations[$identifier] = $collaboration;
            $this->collaborationsConnectionCache->save($connectionId, $connectionCollaborations);
        }
    }

    /**
     * Removes the collaborator with the given userId from the entity with the given identifier.
     *
     * @param string $type The type of the entity
     * @param mixed $id The id of the entity
     * @param string $connectionId The id of the connection of the user
     * @param int $userId The id of the user to remove
     */
    private function removeCollaborator($type, $id, $connectionId, $userId)
    {
        $identifier = $this->getUniqueCollaborationKey($type, $id);

        $entityCollaborations = $this->collaborationsEntityCache->fetch($identifier) ?: [];
        if (array_key_exists($userId, $entityCollaborations)) {
            unset($entityCollaborations[$userId]);
        }

        $this->collaborationsEntityCache->save($identifier, $entityCollaborations);

        $connectionCollaborations = $this->collaborationsConnectionCache->fetch($connectionId) ?: [];
        if (array_key_exists($identifier, $connectionCollaborations)) {
            unset($connectionCollaborations[$identifier]);
        }

        if (empty($connectionCollaborations)) {
            $this->collaborationsConnectionCache->delete($connectionId);
        } else {
            $this->collaborationsConnectionCache->save($connectionId, $connectionCollaborations);
        }
    }

    /**
     * Sends an update with the new collaborators to every collaborator.
     *
     * @param string $type The type of the entity
     * @param mixed $id The id of the entity
     * @param Collaboration[] $users The users currently working on the entity with the given identity
     */
    private function sendUpdate($type, $id, $users)
    {
        $identifier = $this->getUniqueCollaborationKey($type, $id);

        $message = $this->messageBuilder->build(
            'sulu_content.collaboration',
            [
                'command' => 'update',
                'type' => $type,
                'id' => $id,
                'users' => $users,
            ],
            []
        );

        foreach ($this->collaborationsEntityCache->fetch($identifier) as $collaboration) {
            /** @var $collaboration Collaboration */
            if (!array_key_exists($collaboration->getUserId(), $this->connections)) {
                // necessary because it has also to work with the ajax fallback, which does not store connections
                continue;
            }

            $this->connections[$collaboration->getUserId()]->send($message);
        }
    }

    /**
     * Returns the required information about the collaborator's users for returning in the messages.
     *
     * @param string $type The type of the entity
     * @param mixed $id The id of the entity
     *
     * @return array
     */
    private function getUsersInformation($type, $id)
    {
        return array_values(
            array_map(
                function (Collaboration $collaboration) {
                    return [
                        'id' => $collaboration->getUserId(),
                        'username' => $collaboration->getUsername(),
                        'fullName' => $collaboration->getFullName(),
                    ];
                },
                $this->collaborationsEntityCache->fetch($this->getUniqueCollaborationKey($type, $id))
            )
        );
    }

    private function getUniqueCollaborationKey($type, $id)
    {
        return $type . '_' . $id;
    }
}

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
     * An array which contains all the collaborators for a certain identifier (representing a page, product, ...).
     *
     * @var Collaborator[][][]
     */
    private $collaborators = [];

    public function __construct(MessageBuilderInterface $messageBuilder, UserRepositoryInterface $userRepository)
    {
        $this->messageBuilder = $messageBuilder;
        $this->userRepository = $userRepository;
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
        foreach ($this->collaborators as $type => $typeCollaborators) {
            foreach ($typeCollaborators as $id => $collaborators) {
                $oldCollaborators = $collaborators;
                foreach ($collaborators as $userId => $collaborator) {
                    if ($collaborator->getConnection() === $conn) {
                        unset($this->collaborators[$type][$id][$userId]);
                    }
                }

                if ($oldCollaborators !== $this->collaborators[$type][$id]) {
                    $this->sendUpdate($type, $id, $this->getUsersInformation($type, $id));
                }
            }
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
                $result = $this->leave($conn, $context, $msg);
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
        $this->addCollaborator($conn, $msg['type'], $msg['id'], $user);

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
     * @param ConnectionInterface $conn
     * @param MessageHandlerContext $context
     * @param $msg
     *
     * @return array
     */
    private function leave(ConnectionInterface $conn, MessageHandlerContext $context, array $msg)
    {
        $this->removeCollaborator($msg['type'], $msg['id'], $msg['userId']);

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
     * @param mixed $id The id of the entitiy
     * @param UserInterface $user The user being added as collaborator
     */
    private function addCollaborator(ConnectionInterface $conn, $type, $id, UserInterface $user)
    {
        if (!array_key_exists($type, $this->collaborators)) {
            $this->collaborators[$type] = [];
        }

        if (!array_key_exists($id, $this->collaborators[$type])) {
            $this->collaborators[$type][$id] = [];
        }

        if (!array_key_exists($user->getId(), $this->collaborators[$type][$id])) {
            $this->collaborators[$type][$id][$user->getId()] = new Collaborator($user, $conn, $type, $id);
        }
    }

    /**
     * Removes the collaborator with the given userId from the entity with the given identifier.
     *
     * @param string $type The type of the entity
     * @param mixed $id The id of the entitiy
     * @param int $userId The id of the user to remove
     */
    private function removeCollaborator($type, $id, $userId)
    {
        if (array_key_exists($id, $this->collaborators[$type])
            && array_key_exists($userId, $this->collaborators[$type][$id])
        ) {
            unset($this->collaborators[$type][$id][$userId]);
        }
    }

    /**
     * Sends an update with the new collaborators to every collaborator.
     *
     * @param string $type The type of the entity
     * @param mixed $id The id of the entity
     * @param Collaborator[] $users The users currently working on the entity with the given identity
     */
    private function sendUpdate($type, $id, $users)
    {
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

        foreach ($this->collaborators[$type][$id] as $user) {
            $user->getConnection()->send($message);
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
                function (Collaborator $collaborator) {
                    return [
                        'id' => $collaborator->getUser()->getId(),
                        'username' => $collaborator->getUser()->getUsername(),
                        'fullName' => $collaborator->getUser()->getFullName(),
                    ];
                },
                $this->collaborators[$type][$id]
            )
        );
    }
}

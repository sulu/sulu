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
     * @var Collaborator[][]
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
        $this->leave($conn, $context, ['command' => 'leave']);
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
        $identifier = $this->getUniqueCollaborationIdentifier($msg);
        $user = $this->userRepository->findUserById($msg['userId']);
        $this->addCollaborator($conn, $identifier, $user);

        $users = $this->getUsersInformation($identifier);

        $this->sendUpdate($msg, $users, $identifier);

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
        $identifier = $this->getUniqueCollaborationIdentifier($msg);
        $this->removeCollaborator($this->getUniqueCollaborationIdentifier($msg), $msg['userId']);

        $users = $this->getUsersInformation($identifier);

        $this->sendUpdate($msg, $users, $identifier);

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
     * @param mixed $identifier The unique identifier of the entity
     * @param UserInterface $user The user being added as collaborator
     */
    private function addCollaborator(ConnectionInterface $conn, $identifier, UserInterface $user)
    {
        if (!array_key_exists($identifier, $this->collaborators)) {
            $this->collaborators[$identifier] = [];
        }

        if (!array_key_exists($user->getId(), $this->collaborators[$identifier])) {
            $this->collaborators[$identifier][$user->getId()] = new Collaborator($user, $conn);
        }
    }

    /**
     * Removes the collaborator with the given userId from the entity with the given identifier.
     *
     * @param mixed $identifier The unique identifier of the entitiy
     * @param int $userId The id of the user to remove
     */
    private function removeCollaborator($identifier, $userId)
    {
        if (array_key_exists($userId, $this->collaborators[$identifier])) {
            unset($this->collaborators[$identifier][$userId]);
        }
    }

    /**
     * Returns a unique identifier for the entity passed in the message.
     *
     * @param array $msg The passed message
     *
     * @return string
     */
    private function getUniqueCollaborationIdentifier(array $msg)
    {
        return $msg['type'] . '_' . $msg['id'];
    }

    /**
     * Sends an update with the new collaborators to every collaborator.
     *
     * @param array $msg The passed message
     * @param Collaborator[] $users The users currently working on the entitiy with the given identity
     * @param mixed $identifier The identifier of the entity
     */
    private function sendUpdate(array $msg, $users, $identifier)
    {
        $message = $this->messageBuilder->build(
            'sulu_content.collaboration',
            [
                'command' => 'update',
                'id' => $msg['id'],
                'userId' => $msg['userId'],
                'users' => $users,
            ],
            []
        );

        foreach ($this->collaborators[$identifier] as $user) {
            $user->getConnection()->send($message);
        }
    }

    /**
     * Returns the required information about the collaborator's users for returning in the messages.
     *
     * @param mixed $identifier The identifier of the entity
     *
     * @return array
     */
    private function getUsersInformation($identifier)
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
                $this->collaborators[$identifier]
            )
        );
    }
}

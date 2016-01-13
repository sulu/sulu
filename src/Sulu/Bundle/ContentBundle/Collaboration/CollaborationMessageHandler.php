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
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * An array which contains all the collaborators for a certain identifier (representing a page, product, ...).
     *
     * @var Collaborator[][]
     */
    private $collaborators = [];

    public function __construct($userRepository)
    {
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
    private function execute(ConnectionInterface $conn, MessageHandlerContext $context, $msg)
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
    private function enter(ConnectionInterface $conn, MessageHandlerContext $context, $msg)
    {
        $identifier = $this->getUniqueCollaborationIdentifier($msg);
        $user = $this->userRepository->findUserById($msg['userId']);
        $this->addCollaborator($conn, $identifier, $user);

        $users = array_map(
            function (Collaborator $collaborator) {
                return [
                    'id' => $collaborator->getUser()->getId(),
                    'username' => $collaborator->getUser()->getUsername(),
                    'fullName' => $collaborator->getUser()->getFullName(),
                ];
            },
            $this->collaborators[$identifier]
        );

        $message = [
            'handler' => 'sulu_content.collaboration',
            'message' => [
                'command' => 'update',
                'id' => $msg['id'],
                'userId' => $msg['userId'],
                'users' => array_values($users)
            ]
        ];

        foreach ($this->collaborators[$identifier] as $user) {
            $user->getConnection()->send(json_encode($message));
        }

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
    private function leave(ConnectionInterface $conn, MessageHandlerContext $context, $msg)
    {
        $identifier = $this->getUniqueCollaborationIdentifier($msg);
        $this->removeCollaborator($this->getUniqueCollaborationIdentifier($msg), $msg['userId']);

        $users = array_map(
            function (Collaborator $collaborator) {
                return [
                    'id' => $collaborator->getUser()->getId(),
                    'username' => $collaborator->getUser()->getUsername(),
                    'fullName' => $collaborator->getUser()->getFullName(),
                ];
            },
            $this->collaborators[$identifier]
        );

        $message = [
            'handler' => 'sulu_content.collaboration',
            'message' => [
                'command' => 'update',
                'id' => $msg['id'],
                'userId' => $msg['userId'],
                'users' => array_values($users)
            ]
        ];

        foreach ($this->collaborators[$identifier] as $user) {
            $user->getConnection()->send(json_encode($message));
        }
    }

    private function addCollaborator(ConnectionInterface $conn, $identifier, UserInterface $user)
    {
        if (!array_key_exists($identifier, $this->collaborators)) {
            $this->collaborators[$identifier] = [];
        }

        if (!array_key_exists($user->getId(), $this->collaborators[$identifier])) {
            $this->collaborators[$identifier][$user->getId()] = new Collaborator($user, $conn);
        }
    }

    private function removeCollaborator($identifier, $userId)
    {
        if (array_key_exists($userId, $this->collaborators[$identifier])) {
            unset($this->collaborators[$identifier][$userId]);
        }
    }

    private function getUniqueCollaborationIdentifier($msg)
    {
        return $msg['type'] . '_' . $msg['id'];
    }
}

<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Collaboration;

use Prophecy\Argument;
use Ratchet\ConnectionInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Websocket\MessageDispatcher\MessageBuilderInterface;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;

class CollaborationMessageHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var ConnectionInterface
     */
    private $connection1;

    /**
     * @var UserInterface
     */
    private $user1;

    /**
     * @var ConnectionInterface
     */
    private $connection2;

    /**
     * @var UserInterface
     */
    private $user2;

    /**
     * @var MessageHandlerContext
     */
    private $context;

    /**
     * @var MessageBuilderInterface
     */
    private $messageBuilder;

    /**
     * @var CollaborationMessageHandler
     */
    private $collaborationMessageHandler;

    public function setUp()
    {
        parent::setUp();

        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);

        $this->connection1 = $this->prophesize(ConnectionInterface::class);
        $this->user1 = $this->prophesize(UserInterface::class);
        $this->user1->getId()->willReturn(1);
        $this->user1->getUsername()->willReturn('max');
        $this->user1->getFullName()->willReturn('Max Mustermann');

        $this->connection2 = $this->prophesize(ConnectionInterface::class);
        $this->user2 = $this->prophesize(UserInterface::class);
        $this->user2->getId()->willReturn(2);
        $this->user2->getUsername()->willReturn('john');
        $this->user2->getFullName()->willReturn('John Doe');

        $this->context = $this->prophesize(MessageHandlerContext::class);

        $this->userRepository->findUserById(1)->willReturn($this->user1->reveal());
        $this->userRepository->findUserById(2)->willReturn($this->user2->reveal());

        $this->messageBuilder = $this->prophesize(MessageBuilderInterface::class);
        $this->messageBuilder->build(Argument::any(), Argument::any(), Argument::any(), Argument::any())->will(
            function ($arguments) {
                return json_encode(
                    [
                        'handler' => $arguments[0],
                        'message' => $arguments[1],
                        'options' => $arguments[2],
                        'error' => false,
                    ]
                );
            }
        );

        $this->collaborationMessageHandler = new CollaborationMessageHandler(
            $this->messageBuilder->reveal(),
            $this->userRepository->reveal()
        );
    }

    public function testHandleEnterAndLeave()
    {
        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":2,"username":"john","fullName":"John Doe"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $this->connection2->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":2,"username":"john","fullName":"John Doe"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection2->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 2,
                'type' => 'page',
            ],
            $this->context->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection2->reveal(),
            [
                'command' => 'leave',
                'id' => 'a',
                'userId' => 2,
                'type' => 'page',
            ],
            $this->context->reveal()
        );
    }

    public function testClose()
    {
        $connection3 = $this->prophesize(ConnectionInterface::class);
        $user3 = $this->prophesize(UserInterface::class);
        $user3->getId()->willReturn(3);
        $user3->getUsername()->willReturn('erika');
        $user3->getFullName()->willReturn('Erika Mustermann');

        $this->userRepository->findUserById(3)->willReturn($user3->reveal());

        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"b","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'b',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":2,"username":"john","fullName":"John Doe"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $this->connection2->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":2,"username":"john","fullName":"John Doe"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection2->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 2,
                'type' => 'page',
            ],
            $this->context->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"b","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":3,"username":"erika","fullName":"Erika Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $connection3->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"b","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":3,"username":"erika","fullName":"Erika Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $this->collaborationMessageHandler->handle(
            $connection3->reveal(),
            [
                'command' => 'enter',
                'id' => 'b',
                'userId' => 3,
                'type' => 'page',
            ],
            $this->context->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","type":"page","id":"b","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $this->collaborationMessageHandler->onClose($connection3->reveal(), $this->context->reveal());
    }
}

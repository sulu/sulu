<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Collaboration\Tests\Unit;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Prophecy\Argument;
use Ratchet\ConnectionInterface;
use Sulu\Component\Collaboration\CollaborationMessageHandler;
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
     * @var MessageHandlerContext
     */
    private $context1;

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
    private $context2;

    /**
     * @var MessageBuilderInterface
     */
    private $messageBuilder;

    /**
     * @var CollaborationMessageHandler
     */
    private $collaborationMessageHandler;

    /**
     * @var Cache
     */
    private $collaborationsEntityCache;

    /**
     * @var Cache
     */
    private $collaborationsConnectionCache;

    public function setUp()
    {
        parent::setUp();

        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);

        $this->connection1 = $this->prophesize(ConnectionInterface::class);
        $this->user1 = $this->prophesize(UserInterface::class);
        $this->user1->getId()->willReturn(1);
        $this->user1->getUsername()->willReturn('max');
        $this->user1->getFullName()->willReturn('Max Mustermann');
        $this->context1 = $this->prophesize(MessageHandlerContext::class);
        $this->context1->getId()->willReturn(1);

        $this->connection2 = $this->prophesize(ConnectionInterface::class);
        $this->user2 = $this->prophesize(UserInterface::class);
        $this->user2->getId()->willReturn(2);
        $this->user2->getUsername()->willReturn('john');
        $this->user2->getFullName()->willReturn('John Doe');
        $this->context2 = $this->prophesize(MessageHandlerContext::class);
        $this->context2->getId()->willReturn(2);

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

        $this->collaborationsEntityCache = new ArrayCache();
        $this->collaborationsConnectionCache = new ArrayCache();
        $this->collaborationMessageHandler = new CollaborationMessageHandler(
            $this->messageBuilder->reveal(),
            $this->userRepository->reveal(),
            $this->collaborationsEntityCache,
            $this->collaborationsConnectionCache,
            10,
            1
        );
    }

    public function testHandleEnterAndLeave()
    {
        $this->connection1->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context1->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":2,"username":"john","fullName":"John Doe"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $this->connection2->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":2,"username":"john","fullName":"John Doe"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection2->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 2,
                'type' => 'page',
            ],
            $this->context2->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection2->reveal(),
            [
                'command' => 'leave',
                'id' => 'a',
                'userId' => 2,
                'type' => 'page',
            ],
            $this->context2->reveal()
        );
    }

    public function testClose()
    {
        $connection3 = $this->prophesize(ConnectionInterface::class);
        $user3 = $this->prophesize(UserInterface::class);
        $user3->getId()->willReturn(3);
        $user3->getUsername()->willReturn('erika');
        $user3->getFullName()->willReturn('Erika Mustermann');
        $context3 = $this->prophesize(MessageHandlerContext::class);
        $context3->getId()->willReturn(3);

        $this->userRepository->findUserById(3)->willReturn($user3->reveal());

        $this->connection1->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context1->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"b","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'b',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context1->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":2,"username":"john","fullName":"John Doe"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $this->connection2->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":2,"username":"john","fullName":"John Doe"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection2->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 2,
                'type' => 'page',
            ],
            $this->context2->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"b","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":3,"username":"erika","fullName":"Erika Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $connection3->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"b","users":[{"id":1,"username":"max","fullName":"Max Mustermann"},{"id":3,"username":"erika","fullName":"Erika Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $this->collaborationMessageHandler->handle(
            $connection3->reveal(),
            [
                'command' => 'enter',
                'id' => 'b',
                'userId' => 3,
                'type' => 'page',
            ],
            $context3->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"b","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $this->collaborationMessageHandler->onClose($connection3->reveal(), $context3->reveal());
    }

    public function testHandleEnterAndLeaveClearCache()
    {
        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context1->reveal()
        );

        $this->collaborationMessageHandler->handle(
            $this->connection2->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 2,
                'type' => 'page',
            ],
            $this->context2->reveal()
        );

        $this->assertTrue($this->collaborationsConnectionCache->contains(1));
        $this->assertTrue($this->collaborationsConnectionCache->contains(2));
        $this->assertTrue($this->collaborationsEntityCache->contains('page_a'));

        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'leave',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context1->reveal()
        );

        $this->collaborationMessageHandler->handle(
            $this->connection2->reveal(),
            [
                'command' => 'leave',
                'id' => 'a',
                'userId' => 2,
                'type' => 'page',
            ],
            $this->context2->reveal()
        );

        $this->assertFalse($this->collaborationsConnectionCache->contains(1));
        $this->assertFalse($this->collaborationsConnectionCache->contains(2));
        $this->assertFalse($this->collaborationsEntityCache->contains('page_a'));
    }

    public function testHandleChangedTime()
    {
        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context1->reveal()
        );

        /** @var Collaboration $oldCollaboration1 */
        $oldCollaboration1 = $this->collaborationsConnectionCache->fetch(1)['page_a'];
        /** @var Collaboration $oldCollaboration2 */
        $oldCollaboration2 = $this->collaborationsEntityCache->fetch('page_a')[1];

        // Required to test if the changed date has really changed
        sleep(1);

        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'keep',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context1->reveal()
        );

        /** @var Collaboration $collaboration1 */
        $collaboration1 = $this->collaborationsConnectionCache->fetch(1)['page_a'];
        /** @var Collaboration $collaboration2 */
        $collaboration2 = $this->collaborationsEntityCache->fetch('page_a')[1];

        $this->assertGreaterThan($oldCollaboration1->getChanged(), $collaboration1->getChanged());
        $this->assertGreaterThan($oldCollaboration2->getChanged(), $collaboration2->getChanged());
    }

    public function testHandleEnterWithOutdatedCollaborations()
    {
        $this->connection1->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":1,"username":"max","fullName":"Max Mustermann"}]},"options":[],"error":false}'
        )->shouldBeCalled();
        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context1->reveal()
        );

        /** @var Collaboration[] $connectionCollaborations */
        $connectionCollaborations = $this->collaborationsConnectionCache->fetch(1);
        $connectionCollaborations['page_a']->setChanged(time() - 20);
        $this->collaborationsConnectionCache->save(1, $connectionCollaborations);

        /** @var Collaboration[] $entityCollaborations */
        $entityCollaborations = $this->collaborationsEntityCache->fetch('page_a');
        $entityCollaborations[1]->setChanged(time() - 20);
        $this->collaborationsEntityCache->save(1, $entityCollaborations);

        $this->connection2->send(
            '{"handler":"sulu_collaboration","message":{"command":"update","type":"page","id":"a","users":[{"id":2,"username":"john","fullName":"John Doe"}]},"options":[],"error":false}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection2->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 2,
                'type' => 'page',
            ],
            $this->context2->reveal()
        );

        $this->assertFalse($this->collaborationsConnectionCache->contains(1));
        $this->assertArrayNotHasKey(1, $this->collaborationsEntityCache->fetch('page_a'));
    }

    public function testHandleEnterAndLeaveAndClose()
    {
        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context1->reveal()
        );
        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'leave',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page',
            ],
            $this->context1->reveal()
        );
        $this->collaborationMessageHandler->onClose($this->connection1->reveal(), $this->context1->reveal());

        $connectionsReflection = new \ReflectionProperty(CollaborationMessageHandler::class, 'connections');
        $connectionsReflection->setAccessible(true);
        $this->assertEmpty($connectionsReflection->getValue($this->collaborationMessageHandler));
    }
}

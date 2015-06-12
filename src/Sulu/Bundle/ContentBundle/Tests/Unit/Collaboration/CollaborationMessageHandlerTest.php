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

use Ratchet\ConnectionInterface;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;

class CollaborationMessageHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConnectionInterface
     */
    private $connection1;

    /**
     * @var ConnectionInterface
     */
    private $connection2;

    /**
     * @var MessageHandlerContext
     */
    private $context;

    /**
     * @var CollaborationMessageHandler
     */
    private $collaborationMessageHandler;

    public function setUp()
    {
        parent::setUp();

        $this->connection1 = $this->prophesize(ConnectionInterface::class);
        $this->connection2 = $this->prophesize(ConnectionInterface::class);
        $this->context = $this->prophesize(MessageHandlerContext::class);

        $this->collaborationMessageHandler = new CollaborationMessageHandler();
    }

    public function testHandleEnterAndLeave()
    {
        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","id":"a","userId":1,"users":[1]}}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection1->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 1,
                'type' => 'page'
            ],
            $this->context->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","id":"a","userId":2,"users":[1,2]}}'
        )->shouldBeCalled();
        $this->connection2->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","id":"a","userId":2,"users":[1,2]}}'
        )->shouldBeCalled();

        $this->collaborationMessageHandler->handle(
            $this->connection2->reveal(),
            [
                'command' => 'enter',
                'id' => 'a',
                'userId' => 2,
                'type' => 'page'
            ],
            $this->context->reveal()
        );

        $this->connection1->send(
            '{"handler":"sulu_content.collaboration","message":{"command":"update","id":"a","userId":2,"users":[1]}}'
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
}

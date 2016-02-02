<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\Tests\Unit\MessageDispatcher;

use Prophecy\Argument;
use Ratchet\ConnectionInterface;
use Sulu\Component\Websocket\ConnectionContext\ConnectionContextInterface;
use Sulu\Component\Websocket\MessageDispatcher\MessageBuilderInterface;
use Sulu\Component\Websocket\MessageDispatcher\MessageDispatcher;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerException;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface;

class MessageDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageBuilderInterface
     */
    private $messageBuilder;

    /**
     * @var MessageDispatcherInterface
     */
    private $messageDispatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->messageBuilder = $this->prophesize(MessageBuilderInterface::class);
        $this->messageBuilder->build(Argument::any(), Argument::any(), Argument::any(), Argument::any())->will(
            function ($arguments) {
                return json_encode(
                    [
                        'handler' => $arguments[0],
                        'message' => $arguments[1],
                        'options' => $arguments[2],
                        'error' => $arguments[3],
                    ]
                );
            }
        );

        $this->messageDispatcher = new MessageDispatcher($this->messageBuilder->reveal());
    }

    public function testAdd()
    {
        $handler = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface');

        $reflectionClass = new \ReflectionClass($this->messageDispatcher);
        $reflectionProp = $reflectionClass->getProperty('handlers');
        $reflectionProp->setAccessible(true);

        $handlers = $reflectionProp->getValue($this->messageDispatcher);
        $this->assertEmpty($handlers);

        $this->messageDispatcher->add('test', $handler->reveal());

        $handlers = $reflectionProp->getValue($this->messageDispatcher);

        $this->assertNotEmpty($handlers);
        $this->assertArrayHasKey('test', $handlers);
        $this->assertEquals($handler->reveal(), $handlers['test']);
    }

    public function testDispatch()
    {
        $context = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext');
        $conn = $this->prophesize('Ratchet\ConnectionInterface');
        $message = ['test' => '1'];

        $handler = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface');
        $handler->handle($conn->reveal(), $message, $context->reveal())->willReturn(['test' => 2]);

        $this->messageDispatcher->add('test', $handler->reveal());

        $result = json_decode(
            $this->messageDispatcher->dispatch(
                $conn->reveal(),
                'test',
                $message,
                ['id' => 'test'],
                $context->reveal()
            ),
            true
        );

        $this->assertEquals(
            [
                'handler' => 'test',
                'message' => [
                    'test' => 2,
                ],
                'options' => [
                    'id' => 'test',
                ],
                'error' => false,
            ],
            $result
        );
    }

    public function testDispatchNonResult()
    {
        $context = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext');
        $conn = $this->prophesize('Ratchet\ConnectionInterface');
        $message = ['test' => '1'];

        $handler = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface');
        $handler->handle($conn->reveal(), $message, $context->reveal())->willReturn(['test' => 2]);

        $this->messageDispatcher->add('test', $handler->reveal());

        $result = json_decode(
            $this->messageDispatcher->dispatch($conn->reveal(), 'test', $message, ['id' => 'test'], $context->reveal()),
            true
        );

        $this->assertEquals(
            [
                'handler' => 'test',
                'message' => ['test' => 2],
                'options' => [
                    'id' => 'test',
                ],
                'error' => false,
            ],
            $result
        );
    }

    public function testDispatchMessageHandlerException()
    {
        $context = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext');
        $conn = $this->prophesize('Ratchet\ConnectionInterface');
        $message = ['test' => '1'];

        $ex = new \Exception('Thats my message', 4211);

        $handler = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface');
        $handler->handle($conn->reveal(), $message, $context->reveal())->willThrow(new MessageHandlerException($ex));

        $this->messageDispatcher->add('test', $handler->reveal());

        $result = json_decode(
            $this->messageDispatcher->dispatch(
                $conn->reveal(),
                'test',
                $message,
                ['id' => 'test'],
                $context->reveal()
            ),
            true
        );

        $this->assertEquals(
            [
                'handler' => 'test',
                'message' => [
                    'code' => 4211,
                    'message' => 'Thats my message',
                    'type' => 'Exception',
                ],
                'options' => [
                    'id' => 'test',
                ],
                'error' => true,
            ],
            $result
        );
    }

    public function testDispatchNonHandler()
    {
        $this->setExpectedException(
            'Sulu\Component\Websocket\Exception\HandlerNotFoundException',
            'Handler "test" not found'
        );

        $context = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext');
        $conn = $this->prophesize('Ratchet\ConnectionInterface');
        $message = ['test' => '1'];

        $this->messageDispatcher->dispatch($conn->reveal(), 'test', $message, ['id' => 'test'], $context->reveal());
    }

    public function testDispatchWrongHandler()
    {
        $this->setExpectedException(
            'Sulu\Component\Websocket\Exception\HandlerNotFoundException',
            'Handler "test-2" not found'
        );

        $context = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext');
        $conn = $this->prophesize('Ratchet\ConnectionInterface');
        $message = ['test' => '1'];

        $handler = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface');
        $handler->handle($conn->reveal(), $message, $context->reveal());

        $this->messageDispatcher->add('test', $handler->reveal());

        $result = $this->messageDispatcher->dispatch(
            $conn->reveal(),
            'test-2',
            $message,
            ['id' => 'test'],
            $context->reveal()
        );

        $this->assertEquals(null, $result);
    }

    public function testOnClose()
    {
        $context = $this->prophesize(ConnectionContextInterface::class);
        $conn = $this->prophesize(ConnectionInterface::class);

        $handler1 = $this->prophesize(MessageHandlerInterface::class);
        $handler1->onClose($conn->reveal(), new MessageHandlerContext($context->reveal(), 'test-1'))->shouldBeCalled();

        $handler2 = $this->prophesize(MessageHandlerInterface::class);
        $handler2->onClose($conn->reveal(), new MessageHandlerContext($context->reveal(), 'test-2'))->shouldBeCalled();

        $this->messageDispatcher->add('test-1', $handler1->reveal());
        $this->messageDispatcher->add('test-2', $handler2->reveal());

        $this->messageDispatcher->onClose($conn->reveal(), $context->reveal());
    }
}

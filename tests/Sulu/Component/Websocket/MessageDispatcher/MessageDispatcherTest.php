<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\MessageDispatcher;

use Ratchet\ConnectionInterface;

class MessageDispatcherTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function testAdd()
    {
        $handler = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface');

        $dispatcher = new MessageDispatcher();

        $reflectionClass = new \ReflectionClass($dispatcher);
        $reflectionProp = $reflectionClass->getProperty('handler');
        $reflectionProp->setAccessible(true);

        $handlers = $reflectionProp->getValue($dispatcher);
        $this->assertEmpty($handlers);

        $dispatcher->add('test', $handler->reveal());

        $handlers = $reflectionProp->getValue($dispatcher);

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

        $dispatcher = new MessageDispatcher();
        $dispatcher->add('test', $handler->reveal());

        $result = $dispatcher->dispatch($conn->reveal(), 'test', $message, ['id' => 'test'], $context->reveal());

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
        $handler->handle($conn->reveal(), $message, $context->reveal());

        $dispatcher = new MessageDispatcher();
        $dispatcher->add('test', $handler->reveal());

        $result = $dispatcher->dispatch($conn->reveal(), 'test', $message, ['id' => 'test'], $context->reveal());

        $this->assertEquals(
            [
                'handler' => 'test',
                'message' => null,
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

        $dispatcher = new MessageDispatcher();
        $dispatcher->add('test', $handler->reveal());

        $result = $dispatcher->dispatch($conn->reveal(), 'test', $message, ['id' => 'test'], $context->reveal());

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

        $dispatcher = new MessageDispatcher();

        $dispatcher->dispatch($conn->reveal(), 'test', $message, ['id' => 'test'], $context->reveal());
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

        $dispatcher = new MessageDispatcher();
        $dispatcher->add('test', $handler->reveal());

        $result = $dispatcher->dispatch($conn->reveal(), 'test-2', $message, ['id' => 'test'], $context->reveal());

        $this->assertEquals(null, $result);
    }

    public function testOnClose()
    {
        $context = $this->prophesize(MessageHandlerContext::class);
        $conn = $this->prophesize(ConnectionInterface::class);

        $handler1 = $this->prophesize(MessageHandlerInterface::class);
        $handler1->onClose($conn->reveal(), $context->reveal())->shouldBeCalled();

        $handler2 = $this->prophesize(MessageHandlerInterface::class);
        $handler2->onClose($conn->reveal(), $context->reveal())->shouldBeCalled();

        $dispatcher = new MessageDispatcher();
        $dispatcher->add('test-1', $handler1->reveal());
        $dispatcher->add('test-2', $handler2->reveal());

        $dispatcher->onClose($conn->reveal(), $context->reveal());
    }
}

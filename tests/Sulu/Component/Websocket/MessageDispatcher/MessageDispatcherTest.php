<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\MessageDispatcher;


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
        $message = array('test' => '1');

        $handler = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface');
        $handler->handle($conn->reveal(), $message, $context->reveal())->willReturn(array('test' => 2));

        $dispatcher = new MessageDispatcher();
        $dispatcher->add('test', $handler->reveal());

        $result = $dispatcher->dispatch($conn->reveal(), 'test', $message, array('id' => 'test'), $context->reveal());

        $this->assertEquals(
            array(
                'handler' => 'test',
                'message' => array(
                    'test' => 2
                ),
                'options' => array(
                    'id' => 'test'
                ),
                'error' => false
            ),
            $result
        );
    }

    public function testDispatchNonResult()
    {
        $context = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext');
        $conn = $this->prophesize('Ratchet\ConnectionInterface');
        $message = array('test' => '1');

        $handler = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface');
        $handler->handle($conn->reveal(), $message, $context->reveal());

        $dispatcher = new MessageDispatcher();
        $dispatcher->add('test', $handler->reveal());

        $result = $dispatcher->dispatch($conn->reveal(), 'test', $message, array('id' => 'test'), $context->reveal());

        $this->assertEquals(
            array(
                'handler' => 'test',
                'message' => null,
                'options' => array(
                    'id' => 'test'
                ),
                'error' => false
            ),
            $result
        );
    }

    public function testDispatchMessageHandlerException()
    {
        $context = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext');
        $conn = $this->prophesize('Ratchet\ConnectionInterface');
        $message = array('test' => '1');

        $ex = new \Exception('Thats my message', 4211);

        $handler = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface');
        $handler->handle($conn->reveal(), $message, $context->reveal())->wilLThrow(new MessageHandlerException($ex));

        $dispatcher = new MessageDispatcher();
        $dispatcher->add('test', $handler->reveal());

        $result = $dispatcher->dispatch($conn->reveal(), 'test', $message, array('id' => 'test'), $context->reveal());

        $this->assertEquals(
            array(
                'handler' => 'test',
                'message' => array(
                    'code' => 4211,
                    'message' => 'Thats my message',
                    'type' => 'Exception'
                ),
                'options' => array(
                    'id' => 'test'
                ),
                'error' => true
            ),
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
        $message = array('test' => '1');

        $dispatcher = new MessageDispatcher();

        $dispatcher->dispatch($conn->reveal(), 'test', $message, array('id' => 'test'), $context->reveal());
    }

    public function testDispatchWrongHandler()
    {
        $this->setExpectedException(
            'Sulu\Component\Websocket\Exception\HandlerNotFoundException',
            'Handler "test-2" not found'
        );

        $context = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext');
        $conn = $this->prophesize('Ratchet\ConnectionInterface');
        $message = array('test' => '1');

        $handler = $this->prophesize('Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface');
        $handler->handle($conn->reveal(), $message, $context->reveal());

        $dispatcher = new MessageDispatcher();
        $dispatcher->add('test', $handler->reveal());

        $result = $dispatcher->dispatch($conn->reveal(), 'test-2', $message, array('id' => 'test'), $context->reveal());

        $this->assertEquals(null, $result);
    }
}

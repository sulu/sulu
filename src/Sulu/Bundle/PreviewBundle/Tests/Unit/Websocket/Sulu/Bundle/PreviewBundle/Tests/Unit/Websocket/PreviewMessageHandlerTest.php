<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Websocket;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Ratchet\ConnectionInterface;
use Sulu\Bundle\PreviewBundle\Preview\Exception\WebspaceNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\PreviewInterface;
use Sulu\Bundle\PreviewBundle\Websocket\PreviewMessageHandler;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerException;

class PreviewMessageHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @var PreviewMessageHandler
     */
    private $previewMessageHandler;

    public function setUp()
    {
        $this->entityManager = $this->prophesize(EntityManager::class);
        $this->connection = $this->prophesize(Connection::class);
        $this->preview = $this->prophesize(PreviewInterface::class);

        $this->entityManager->getConnection()->willReturn($this->connection->reveal());

        $this->previewMessageHandler = new PreviewMessageHandler(
            $this->entityManager->reveal(),
            $this->preview->reveal()
        );
    }

    public function testHandleStart()
    {
        $connection = $this->prophesize(ConnectionInterface::class);
        $context = $this->prophesize(MessageHandlerContext::class);

        $this->preview->start(self::class, '123-123-123', 1, 'sulu_io', 'de', ['test' => 'asdf'])->willReturn('token');

        $context->has('previewToken')->willReturn(false);
        $context->set('previewToken', 'token')->shouldBeCalled();
        $context->set('locale', 'de')->shouldBeCalled();

        $this->preview->render('token', 'sulu_io', 'de')->willReturn('<h1>Hello World</h1>');

        $result = $this->previewMessageHandler->handle(
            $connection->reveal(),
            [
                'command' => 'start',
                'class' => self::class,
                'id' => '123-123-123',
                'user' => 1,
                'webspaceKey' => 'sulu_io',
                'locale' => 'de',
                'data' => ['test' => 'asdf'],
            ],
            $context->reveal()
        );

        $this->assertEquals(
            ['command' => 'start', 'token' => 'token', 'response' => '<h1>Hello World</h1>', 'msg' => 'OK'],
            $result
        );
    }

    public function testHandleStartException()
    {
        $this->setExpectedException(MessageHandlerException::class);

        $connection = $this->prophesize(ConnectionInterface::class);
        $context = $this->prophesize(MessageHandlerContext::class);

        $this->preview->start(self::class, '123-123-123', 1, 'sulu_io', 'de', ['test' => 'asdf'])->willReturn('token');

        $context->has('previewToken')->willReturn(false);
        $context->set('previewToken', 'token')->shouldBeCalled();
        $context->set('locale', 'de')->shouldBeCalled();

        $this->preview->render('token', 'sulu_io', 'de')->willThrow(
            new WebspaceNotFoundException(self::class, '123-123-123', 'sulu_io', 'de')
        );

        $this->previewMessageHandler->handle(
            $connection->reveal(),
            [
                'command' => 'start',
                'class' => self::class,
                'id' => '123-123-123',
                'user' => 1,
                'webspaceKey' => 'sulu_io',
                'locale' => 'de',
                'data' => ['test' => 'asdf'],
            ],
            $context->reveal()
        );
    }

    public function testHandleUpdate()
    {
        $connection = $this->prophesize(ConnectionInterface::class);
        $context = $this->prophesize(MessageHandlerContext::class);
        $context->get('previewToken')->willReturn('token');
        $context->get('locale')->willReturn('de');

        $this->previewMessageHandler->handle(
            $connection->reveal(),
            [
                'command' => 'update',
                'webspaceKey' => 'sulu_io',
                'data' => ['test' => 'asdf'],
            ],
            $context->reveal()
        );

        $this->preview->update('token', 'sulu_io', 'de', ['test' => 'asdf'], null)->shouldBeCalled();
    }

    public function testHandleUpdateWithTargetGroup()
    {
        $connection = $this->prophesize(ConnectionInterface::class);
        $context = $this->prophesize(MessageHandlerContext::class);
        $context->get('previewToken')->willReturn('token');
        $context->get('locale')->willReturn('de');

        $this->previewMessageHandler->handle(
            $connection->reveal(),
            [
                'command' => 'update',
                'webspaceKey' => 'sulu_io',
                'data' => ['test' => 'asdf'],
                'targetGroupId' => 2,
            ],
            $context->reveal()
        );

        $this->preview->update('token', 'sulu_io', 'de', ['test' => 'asdf'], 2)->shouldBeCalled();
    }

    public function testHandleUpdateContext()
    {
        $connection = $this->prophesize(ConnectionInterface::class);
        $context = $this->prophesize(MessageHandlerContext::class);
        $context->get('previewToken')->willReturn('token');
        $context->get('locale')->willReturn('de');

        $this->previewMessageHandler->handle(
            $connection->reveal(),
            [
                'command' => 'update-context',
                'webspaceKey' => 'sulu_io',
                'context' => ['context1' => 'value1'],
                'data' => ['test' => 'asdf'],
            ],
            $context->reveal()
        );

        $this->preview->updateContext(
            'token',
            'sulu_io',
            'de',
            ['context1' => 'value1'],
            ['test' => 'asdf'],
            null
        )->shouldBeCalled();
    }

    public function testHandleUpdateContextWithTargetGroup()
    {
        $connection = $this->prophesize(ConnectionInterface::class);
        $context = $this->prophesize(MessageHandlerContext::class);
        $context->get('previewToken')->willReturn('token');
        $context->get('locale')->willReturn('de');

        $this->previewMessageHandler->handle(
            $connection->reveal(),
            [
                'command' => 'update-context',
                'webspaceKey' => 'sulu_io',
                'context' => ['context1' => 'value1'],
                'data' => ['test' => 'asdf'],
                'targetGroupId' => 2,
            ],
            $context->reveal()
        );

        $this->preview->updateContext(
            'token',
            'sulu_io',
            'de',
            ['context1' => 'value1'],
            ['test' => 'asdf'],
            2
        )->shouldBeCalled();
    }

    public function testHandleRender()
    {
        $connection = $this->prophesize(ConnectionInterface::class);
        $context = $this->prophesize(MessageHandlerContext::class);
        $context->get('previewToken')->willReturn('token');
        $context->get('locale')->willReturn('de');

        $this->previewMessageHandler->handle(
            $connection->reveal(),
            [
                'command' => 'render',
                'webspaceKey' => 'sulu_io',
            ],
            $context->reveal()
        );

        $this->preview->render(
            'token',
            'sulu_io',
            'de',
            null
        )->shouldBeCalled();
    }

    public function testHandleRenderWithTargetGroup()
    {
        $connection = $this->prophesize(ConnectionInterface::class);
        $context = $this->prophesize(MessageHandlerContext::class);
        $context->get('previewToken')->willReturn('token');
        $context->get('locale')->willReturn('de');

        $this->previewMessageHandler->handle(
            $connection->reveal(),
            [
                'command' => 'render',
                'webspaceKey' => 'sulu_io',
                'targetGroupId' => 2,
            ],
            $context->reveal()
        );

        $this->preview->render(
            'token',
            'sulu_io',
            'de',
            2
        )->shouldBeCalled();
    }

    public function testOnClose()
    {
        $connection = $this->prophesize(ConnectionInterface::class);
        $context = $this->prophesize(MessageHandlerContext::class);
        $context->get('previewToken')->willReturn('token');
        $this->preview->stop('token')->shouldBeCalled();

        $context->clear()->shouldBeCalled();

        $this->previewMessageHandler->onClose($connection->reveal(), $context->reveal());
    }
}

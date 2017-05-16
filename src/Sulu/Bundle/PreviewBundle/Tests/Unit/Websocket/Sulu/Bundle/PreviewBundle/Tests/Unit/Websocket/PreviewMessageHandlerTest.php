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
use Sulu\Bundle\PreviewBundle\Preview\PreviewInterface;
use Sulu\Bundle\PreviewBundle\Websocket\PreviewMessageHandler;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;

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

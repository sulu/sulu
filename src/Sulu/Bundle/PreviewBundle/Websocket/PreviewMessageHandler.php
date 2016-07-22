<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Websocket;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Ratchet\ConnectionInterface;
use Sulu\Bundle\PreviewBundle\Preview\Exception\PreviewException;
use Sulu\Bundle\PreviewBundle\Preview\PreviewInterface;
use Sulu\Component\Websocket\Exception\MissingParameterException;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerException;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface;

/**
 * Entry-point for preview websocket message handler.
 */
class PreviewMessageHandler implements MessageHandlerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @param EntityManagerInterface $entityManager
     * @param PreviewInterface $preview
     */
    public function __construct(EntityManagerInterface $entityManager, PreviewInterface $preview)
    {
        $this->entityManager = $entityManager;
        $this->preview = $preview;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ConnectionInterface $conn, array $message, MessageHandlerContext $context)
    {
        // reconnect mysql
        $this->reconnect();

        try {
            return $this->execute($context, $message);
        } catch (PreviewException $ex) {
            throw new MessageHandlerException($ex);
        }
    }

    protected function execute(MessageHandlerContext $context, $message)
    {
        if (!array_key_exists('command', $message)) {
            throw new MissingParameterException('command');
        }

        $command = $message['command'];
        $result = null;

        switch ($command) {
            case 'start':
                $result = $this->start($context, $message);
                break;
            case 'stop':
                $result = $this->stop($context);
                break;
            case 'update':
                $result = $this->update($context, $message);
                break;
            case 'update-context':
                $result = $this->updateContext($context, $message);
                break;
            case 'render':
                $result = $this->render($context, $message);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Command "%s" not known', $command));
                break;
        }

        return $result;
    }

    /**
     * Reconnect to mysql.
     */
    private function reconnect()
    {
        $connection = $this->entityManager->getConnection();

        try {
            $connection->executeQuery('SELECT 1;');
        } catch (DBALException $exc) {
            $connection->close();
            $connection->connect();
        }
    }

    /**
     * Start preview with given parameters.
     *
     * @param MessageHandlerContext $context
     * @param array $message
     *
     * @return array
     */
    private function start(MessageHandlerContext $context, $message)
    {
        if ($context->has('previewToken') && $this->preview->exists($context->get('previewToken'))) {
            $this->preview->stop($context->get('previewToken'));
        }

        $token = $this->preview->start(
            $message['class'],
            $message['id'],
            $message['user'],
            $message['webspaceKey'],
            $message['locale'],
            $message['data'] ?: []
        );
        $response = $this->preview->render($token, $message['webspaceKey'], $message['locale']);

        $context->set('previewToken', $token);
        $context->set('locale', $message['locale']);

        return ['command' => 'start', 'token' => $token, 'response' => $response, 'msg' => 'OK'];
    }

    /**
     * Stop preview for given context.
     *
     * @param MessageHandlerContext $context
     *
     * @return array
     */
    private function stop(MessageHandlerContext $context)
    {
        $this->preview->stop($context->get('previewToken'));

        return ['command' => 'stop', 'msg' => 'OK'];
    }

    /**
     * Update preview with given parameter.
     *
     * @param MessageHandlerContext $context
     * @param array $message
     *
     * @return array
     */
    private function update(MessageHandlerContext $context, $message)
    {
        $changes = $this->preview->update(
            $context->get('previewToken'),
            $message['webspaceKey'],
            $context->get('locale'),
            $message['data']
        );

        return ['command' => 'update', 'data' => $changes, 'msg' => 'OK'];
    }

    /**
     * Update preview-context with given parameter.
     *
     * @param MessageHandlerContext $context
     * @param array $message
     *
     * @return array
     */
    private function updateContext($context, $message)
    {
        $response = $this->preview->updateContext(
            $context->get('previewToken'),
            $message['webspaceKey'],
            $context->get('locale'),
            $message['context'],
            $message['data']
        );

        return ['command' => 'update-context', 'response' => $response, 'msg' => 'OK'];
    }

    private function render(MessageHandlerContext $context, $message)
    {
        $response = $this->preview->render(
            $context->get('previewToken'),
            $message['webspaceKey'],
            $context->get('locale')
        );

        return ['command' => 'render', 'response' => $response, 'msg' => 'OK'];
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn, MessageHandlerContext $context)
    {
        $this->preview->stop($context->get('previewToken'));

        $context->clear();
    }
}

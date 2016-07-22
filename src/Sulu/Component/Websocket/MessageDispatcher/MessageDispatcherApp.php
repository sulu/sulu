<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\MessageDispatcher;

use Doctrine\Common\Cache\Cache;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Sulu\Component\Websocket\AbstractWebsocketApp;
use Sulu\Component\Websocket\ConnectionContext\ConnectionContextInterface;
use Sulu\Component\Websocket\Exception\MissingParameterException;

/**
 * Class MessageDispatcherApp.
 */
class MessageDispatcherApp extends AbstractWebsocketApp implements MessageComponentInterface
{
    /**
     * {@inheritdoc}
     */
    protected $name;

    /**
     * @var MessageDispatcherInterface
     */
    protected $messageDispatcher;

    public function __construct($appName, MessageDispatcherInterface $messageDispatcher, Cache $contextsCache)
    {
        parent::__construct($contextsCache);

        $this->name = $appName;
        $this->messageDispatcher = $messageDispatcher;
    }

    /**
     * Triggered when a client sends data through the socket.
     *
     * @param \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param string                       $msg  The message received
     *
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $context = $this->getContext($from);

        $msg = json_decode($msg, true);

        try {
            $result = $this->dispatch($from, $context, $msg);

            if ($result !== null) {
                $from->send($result);
            }
        } catch (\Exception $e) {
            // send fail message
            $from->send(
                json_encode(
                    [
                        'type' => 'error',
                        'exception' => get_class($e),
                        'code' => $e->getCode(),
                        'trace' => $e->getTrace(),
                        'msg' => $e->getMessage(),
                        'parentMsg' => $msg,
                    ]
                )
            );
        }

        $this->saveContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $connection)
    {
        $context = $this->getContext($connection);

        $this->messageDispatcher->onClose($connection, $context);

        parent::onClose($connection);
    }

    /**
     * Dispatches message to handler with dispatcher service.
     *
     * @param ConnectionInterface        $conn
     * @param ConnectionContextInterface $context
     * @param array                      $msg
     *
     * @throws MissingParameterException
     */
    private function dispatch(ConnectionInterface $conn, ConnectionContextInterface $context, array $msg)
    {
        if (!array_key_exists('handler', $msg)) {
            throw new MissingParameterException('handler');
        }
        if (!array_key_exists('message', $msg)) {
            throw new MissingParameterException('message');
        }

        return $this->messageDispatcher->dispatch(
            $conn,
            $msg['handler'],
            $msg['message'],
            $msg['options'] ?: [],
            $this->createMessageHandlerContext($context, $msg['handler'])
        );
    }

    /**
     * Create a message handler context.
     *
     * @param ConnectionContextInterface $context
     * @param string                     $handlerName
     *
     * @return MessageHandlerContext
     */
    protected function createMessageHandlerContext(ConnectionContextInterface $context, $handlerName)
    {
        return new MessageHandlerContext($context, $handlerName);
    }
}

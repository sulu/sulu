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

use Ratchet\ConnectionInterface;
use Sulu\Component\Websocket\ConnectionContext\ConnectionContextInterface;
use Sulu\Component\Websocket\Exception\HandlerNotFoundException;

/**
 * Responsible for taking messages and meta information from a MessageComponentInterface, passing this data to a
 * MessageHandler, and returning the new message to the MessageComponentInterface.
 */
class MessageDispatcher implements MessageDispatcherInterface
{
    /**
     * @var MessageBuilderInterface
     */
    private $messageBuilder;

    /**
     * @var MessageHandlerInterface[]
     */
    private $handlers = [];

    public function __construct(MessageBuilderInterface $messageBuilder)
    {
        $this->messageBuilder = $messageBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function add($name, MessageHandlerInterface $handler)
    {
        $this->handlers[$name] = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(
        ConnectionInterface $conn,
        $name,
        array $message,
        array $options,
        ConnectionContextInterface $context
    ) {
        if (!array_key_exists($name, $this->handlers)) {
            throw new HandlerNotFoundException($name);
        }

        $error = false;
        try {
            $message = $this->handlers[$name]->handle($conn, $message, $context);
        } catch (MessageHandlerException $ex) {
            $message = $ex->getResponseMessage();
            $error = true;
        }

        return $this->messageBuilder->build($name, $message, $options, $error);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(
        ConnectionInterface $conn,
        ConnectionContextInterface $context
    ) {
        foreach ($this->handlers as $name => $handler) {
            $handler->onClose($conn, new MessageHandlerContext($context, $name));
        }
    }
}

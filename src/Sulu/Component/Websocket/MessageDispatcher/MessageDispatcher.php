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

use Ratchet\ConnectionInterface;
use Sulu\Component\Websocket\ConnectionContext\ConnectionContextInterface;
use Sulu\Component\Websocket\Exception\HandlerNotFoundException;

/**
 * Class MessageDispatcher
 */
class MessageDispatcher implements MessageDispatcherInterface
{
    /**
     * @var MessageHandlerInterface[]
     */
    private $handler = array();

    /**
     * {@inheritdoc}
     */
    public function add($name, MessageHandlerInterface $handler)
    {
        $this->handler[$name] = $handler;
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
        if (!array_key_exists($name, $this->handler)) {
            throw new HandlerNotFoundException($name);
        }

        $error = false;
        try {
            $message = $this->handler[$name]->handle($conn, $message, $context);
        } catch (MessageHandlerException $ex) {
            $message = $ex->getResponseMessage();
            $error = true;
        }

        return array(
            'handler' => $name,
            'message' => $message,
            'options' => $options,
            'error' => $error
        );
    }
}

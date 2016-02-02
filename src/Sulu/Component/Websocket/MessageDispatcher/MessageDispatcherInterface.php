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

/**
 * Interface MessageDispatcherInterface.
 */
interface MessageDispatcherInterface
{
    /**
     * @param string $name Message name
     * @param MessageHandlerInterface $handler Handler which process messages with given name
     */
    public function add($name, MessageHandlerInterface $handler);

    /**
     * Dispatch event to handler with given name.
     *
     * @param ConnectionInterface $conn
     * @param string $name Message name
     * @param array $message
     * @param array $options
     * @param ConnectionContextInterface $context
     */
    public function dispatch(
        ConnectionInterface $conn,
        $name,
        array $message,
        array $options,
        ConnectionContextInterface $context
    );

    /**
     * All message handler get the message that the connection is losed.
     *
     * @param ConnectionInterface $conn
     * @param ConnectionContextInterface $context
     */
    public function onClose(ConnectionInterface $conn, ConnectionContextInterface $context);
}

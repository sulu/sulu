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

use Sulu\Component\Websocket\ConnectionContext\ConnectionContextInterface;

/**
 * Interface MessageDispatcherInterface
 */
interface MessageDispatcherInterface
{

    /**
     * @param string $name Message name
     * @param MessageHandlerInterface $handler Handler which process messages with given name
     */
    public function add($name, MessageHandlerInterface $handler);

    /**
     * Dispatch event to handler with given name
     * @param string $name Message name
     * @param array $message
     * @param ConnectionContextInterface $context
     */
    public function dispatch($name, array $message, ConnectionContextInterface $context);
}

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

/**
 * Interface MessageHandlerInterface
 */
interface MessageHandlerInterface
{
    /**
     * Processes given message
     * @param ConnectionInterface $conn
     * @param array $message
     * @param ConnectionContextInterface $context
     * @return
     */
    public function handle(ConnectionInterface $conn, array $message, ConnectionContextInterface $context);
}

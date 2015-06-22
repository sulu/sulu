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

/**
 * Interface MessageHandlerInterface.
 */
interface MessageHandlerInterface
{
    /**
     * Processes given message.
     *
     * @param ConnectionInterface $conn
     * @param array $message
     * @param MessageHandlerContext $context
     */
    public function handle(ConnectionInterface $conn, array $message, MessageHandlerContext $context);
}

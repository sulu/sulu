<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket;

use Ratchet\MessageComponentInterface;

/**
 * Interface for a websocket app.
 */
interface WebsocketAppInterface extends MessageComponentInterface
{
    /**
     * Returns name of app.
     *
     * @return string
     */
    public function getName();
}

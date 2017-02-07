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

/**
 * Defines methods to build websocket messages from the given data.
 */
interface MessageBuilderInterface
{
    /**
     * @param $handlerName
     * @param array $message
     * @param array $options
     * @param bool $error
     *
     * @return string
     */
    public function build($handlerName, array $message, array $options, $error = false);
}

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
 * Creates messages for websockets.
 */
class MessageBuilder implements MessageBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function build($handlerName, array $message, array $options, $error = false)
    {
        return json_encode(
            [
                'handler' => $handlerName,
                'message' => $message,
                'options' => $options,
                'error' => $error,
            ]
        );
    }
}

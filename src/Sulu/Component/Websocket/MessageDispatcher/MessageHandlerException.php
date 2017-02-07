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
 * Class MessageHandlerException.
 */
class MessageHandlerException extends \Exception
{
    /**
     * MessageHandlerException constructor.
     *
     * @param \Exception $previous
     */
    public function __construct(\Exception $previous)
    {
        parent::__construct('', 0, $previous);
    }

    /**
     * @return array
     */
    public function getResponseMessage()
    {
        return [
            'code' => $this->getPrevious()->getCode(),
            'message' => $this->getPrevious()->getMessage(),
            'type' => get_class($this->getPrevious()),
        ];
    }
}

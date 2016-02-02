<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

class StateNotFoundException extends StateException
{
    /**
     * @var int
     */
    private $state;

    public function __construct($state, $message = null, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->state = $state;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }
}

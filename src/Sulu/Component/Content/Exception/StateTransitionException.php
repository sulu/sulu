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

class StateTransitionException extends StateException
{
    /**
     * @var int
     */
    private $from;
    /**
     * @var int
     */
    private $to;

    public function __construct($from, $to, $message = null, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return int
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return int
     */
    public function getTo()
    {
        return $this->to;
    }
}

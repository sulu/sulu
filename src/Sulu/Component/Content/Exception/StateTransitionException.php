<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

class StateTransitionException extends StateException
{
    /**
     * @param int $from
     * @param int $to
     * @param ?string $message
     * @param ?int $code
     * @param ?\Throwable $previous
     */
    public function __construct(
        private $from,
        private $to,
        $message = null,
        $code = null,
        $previous = null
    ) {
        parent::__construct($message ?? '', $code ?? 0, $previous);
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

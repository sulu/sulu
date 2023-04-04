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

class StateNotFoundException extends StateException
{
    /**
     * @param int $state
     * @param ?string $message
     * @param ?int $code
     * @param ?\Throwable $previous
     */
    public function __construct(private $state, $message = null, $code = null, $previous = null)
    {
        parent::__construct($message ?? '', $code ?? 0, $previous);
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }
}

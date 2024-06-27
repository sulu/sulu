<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Exception;

/**
 * Indicates missing token.
 */
class TokenNotFoundException extends PreviewException
{
    /**
     * @param string $token
     */
    public function __construct(private $token)
    {
        parent::__construct(\sprintf('Token "%s" not found', $token));
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}

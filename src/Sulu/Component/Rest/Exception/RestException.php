<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

class RestException extends \Exception implements RestExceptionInterface
{
    /**
     * @return array{code: int, message: string}
     */
    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}

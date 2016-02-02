<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category\Exception;

use Sulu\Component\Rest\Exception\RestException;

class KeyNotUniqueException extends RestException
{
    public function toArray()
    {
        return [
            'code' => 1,
            'message' => 'A category-key has to be unique',
        ];
    }
}

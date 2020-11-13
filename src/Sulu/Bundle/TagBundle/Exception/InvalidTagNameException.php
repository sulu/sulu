<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Exception;

class InvalidTagNameException extends \Exception
{
    public function __construct(string $tagName)
    {
        parent::__construct(
            \sprintf('"%s" is not a valid name for tags!', $tagName)
        );
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Domain\Exception;

class MissingRequestContextParameterException extends \LogicException
{
    public function __construct(string $parameter)
    {
        parent::__construct(\sprintf('Missing request context parameter "%s".', $parameter));
    }
}

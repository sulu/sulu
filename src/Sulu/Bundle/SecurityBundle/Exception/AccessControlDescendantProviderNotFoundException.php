<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Exception;

class AccessControlDescendantProviderNotFoundException extends \Exception
{
    public function __construct($type, $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            \sprintf('AccessControlDescendantProvider not found for type "%s".', $type),
            $code,
            $previous
        );
    }
}

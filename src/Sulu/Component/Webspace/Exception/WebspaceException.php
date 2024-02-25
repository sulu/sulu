<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Exception;

use Sulu\Component\Webspace\Webspace;

class WebspaceException extends \Exception
{
    protected Webspace $webspace;

    public function getWebspace(): Webspace
    {
        return $this->webspace;
    }
}

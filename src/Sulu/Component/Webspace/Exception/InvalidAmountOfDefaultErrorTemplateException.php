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

class InvalidAmountOfDefaultErrorTemplateException extends WebspaceException
{
    public function __construct(Webspace $webspace)
    {
        parent::__construct(\sprintf('One or no error template in webspace "%s" has to defined as default.', $webspace->getKey()));

        $this->webspace = $webspace;
    }
}

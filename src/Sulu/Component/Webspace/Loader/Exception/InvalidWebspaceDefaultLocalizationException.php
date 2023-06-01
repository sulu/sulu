<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Loader\Exception;

use Sulu\Component\Webspace\Webspace;

class InvalidWebspaceDefaultLocalizationException extends WebspaceException
{
    public function __construct(Webspace $webspace, ?\Throwable $previous = null)
    {
        $this->webspace = $webspace;
        $message = 'The webspace definition for "' . $webspace->getKey() . '" has has multiple default localization';
        parent::__construct($message, 0, $previous);
    }
}

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

class InvalidUrlDefinitionException extends WebspaceException
{
    public function __construct(
        Webspace $webspace,
        private string $urlPattern
    ) {
        $this->webspace = $webspace;
        $message = 'The url pattern "' . $urlPattern . '" in the webspace definition "' . $webspace->getKey() . '" ' .
            'has not specified the required attributes (either with xml attributes or as placeholders in the pattern)';

        parent::__construct($message, 0);
    }

    public function getUrlPattern(): string
    {
        return $this->urlPattern;
    }
}

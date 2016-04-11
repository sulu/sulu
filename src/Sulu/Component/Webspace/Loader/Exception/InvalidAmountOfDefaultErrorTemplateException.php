<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Loader\Exception;

/**
 * This error represents a wrong number of default error templates.
 */
class InvalidAmountOfDefaultErrorTemplateException extends WebspaceException
{
    /**
     * InvalidAmountOfDefaultErrorTemplateException constructor.
     */
    public function __construct($webspace)
    {
        parent::__construct(sprintf('One or no error template in webspace "%s" has to defined as default.', $webspace));

        $this->webspace = $webspace;
    }
}

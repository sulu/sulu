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
 * This error represents a wrong number of default error templates
 */
class InvalidAmountOfDefaultErrorTemplateException extends WebspaceException
{
    /**
     * InvalidAmountOfDefaultErrorTemplateException constructor.
     */
    public function __construct()
    {
        parent::__construct('One or no error template has to defined as default');
    }
}

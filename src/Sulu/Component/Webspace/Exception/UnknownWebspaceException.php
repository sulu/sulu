<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Exception;

/**
 * This exception is thrown when an environment in a webspace does not exist.
 */
class UnknownWebspaceException extends \InvalidArgumentException
{
    public function __construct($name)
    {
        parent::__construct(
            sprintf('Webspace "%s" is not known.', $name)
        );
    }
}

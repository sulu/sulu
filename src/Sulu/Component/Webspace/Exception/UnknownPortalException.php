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
 * This exception is thrown when an environment in a portal does not exist.
 */
class UnknownPortalException extends PortalException
{
    public function __construct($portalName)
    {
        parent::__construct(
            sprintf('Portal "%s" is not known.', $portalName)
        );
    }
}

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

use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;

class PortalDefaultLocalizationNotFoundException extends WebspaceException
{
    public function __construct(Webspace $webspace, private Portal $portal, ?\Throwable $previous = null)
    {
        $this->webspace = $webspace;
        $message = 'The portal "' . $this->portal->getKey() . '" in the webspace definition "' . $webspace->getKey() . '" ' .
            'has not specified the required attributes (a default localization)';
        parent::__construct($message, 0, $previous);
    }

    /**
     * Returns the webspace in which the error occured.
     *
     * @return Webspace
     */
    public function getWebspace()
    {
        return $this->webspace;
    }
}

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

use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;

class InvalidPortalDefaultLocalizationException extends WebspaceException
{
    /**
     * The webspace in which the error occured.
     *
     * @var Webspace
     */
    private $portal;

    /**
     * @param Webspace $webspace
     * @param Portal   $portal
     */
    public function __construct(Webspace $webspace, Portal $portal)
    {
        $this->webspace = $webspace;
        $this->portal = $portal;
        $message = 'The portal "' . $portal->getKey() . '" in the webspace definition "' . $webspace->getKey() . '" ' .
            'has multiple default localizations';
        parent::__construct($message, 0);
    }

    /**
     * Returns the webspace in which the error occured.
     *
     * @return Webspace
     */
    public function getPortal()
    {
        return $this->portal;
    }
}

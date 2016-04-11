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

use Sulu\Component\Webspace\Webspace;

/**
 * Thrown if some webspace locales are not used in any portal.
 */
class WebspaceLocalizationNotUsedException extends WebspaceException
{
    /**
     * @param Webspace $webspace
     */
    public function __construct(Webspace $webspace)
    {
        parent::__construct(
            'The webspace definition for "' . $webspace->getKey() . '" has locales which are not used in any portal'
        );

        $this->webspace = $webspace;
    }
}

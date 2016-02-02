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

class WebspaceException extends \Exception
{
    /**
     * The webspace of this exception.
     *
     * @var Webspace
     */
    protected $webspace;

    /**
     * Returns the webspace of this exception.
     *
     * @return Webspace
     */
    public function getWebspace()
    {
        return $this->webspace;
    }
}

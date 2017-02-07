<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Exception;

use Sulu\Component\Webspace\Portal;

/**
 * General class for all webspace exceptions.
 */
class PortalException extends \Exception
{
    /**
     * @var Portal
     */
    protected $portal;

    /**
     * @return Portal
     */
    public function getPortal()
    {
        return $this->portal;
    }
}

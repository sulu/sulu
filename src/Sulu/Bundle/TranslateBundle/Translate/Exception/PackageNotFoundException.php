<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Translate\Exception;

use Exception;

/**
 * This exception is thrown when the Import can't find the package that should
 * be overriden.
 */
class PackageNotFoundException extends Exception
{
    private $packageId;

    public function __construct($packageId)
    {
        $this->packageId = $packageId;
        parent::__construct();
    }

    public function getPackageId()
    {
        return $this->packageId;
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

/**
 * Represents a not started preview.
 */
class PreviewNotStartedException extends \Exception
{
    /**
     * Code that is used for this exception.
     */
    const EXCEPTION_CODE = 3001;

    public function __construct()
    {
        parent::__construct('Preview not started.', self::EXCEPTION_CODE);
    }
}

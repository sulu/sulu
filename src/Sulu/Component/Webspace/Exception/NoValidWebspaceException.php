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

/**
 * Runtime exception if no valid webspaces are found.
 */
class NoValidWebspaceException extends \RuntimeException
{
    /**
     * @param string $path Path of webspace files
     */
    public function __construct($path)
    {
        parent::__construct(sprintf(
            'No valid webspaces found in "%s".',
            $path
        ));
    }
}

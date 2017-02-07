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
 * Will be thrown for invalid custom-url.
 */
class InvalidCustomUrlException extends WebspaceException
{
    /**
     * @var string
     */
    private $customUrl;

    /**
     * @param Webspace $webspace
     */
    public function __construct(Webspace $webspace, $customUrl)
    {
        parent::__construct(
            'The custom-url "' . $customUrl . '" for "' . $webspace->getKey() . '" has no placeholder'
        );

        $this->customUrl = $customUrl;
        $this->webspace = $webspace;
    }

    /**
     * @return string
     */
    public function getCustomUrl()
    {
        return $this->customUrl;
    }
}

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

use Sulu\Component\Webspace\Webspace;

class InvalidUrlDefinitionException extends WebspaceException
{
    /**
     * @param string $urlPattern
     */
    public function __construct(Webspace $portal, private $urlPattern)
    {
        $this->webspace = $portal;
        $message = 'The url pattern "' . $this->urlPattern . '" in the webspace definition "' . $portal->getKey() . '" ' .
            'has not specified the required attributes (either with xml attributes or as placeholders in the pattern)';
        parent::__construct($message, 0);
    }

    /**
     * Returns the url pattern.
     *
     * @return string
     */
    public function getUrlPattern()
    {
        return $this->urlPattern;
    }
}

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

class InvalidUrlDefinitionException extends WebspaceException
{
    /**
     * The pattern which was invalid.
     *
     * @var string
     */
    private $urlPattern;

    /**
     * @param Webspace $portal
     * @param string   $urlPattern
     */
    public function __construct(Webspace $portal, $urlPattern)
    {
        $this->webspace = $portal;
        $this->urlPattern = $urlPattern;
        $message = 'The url pattern "' . $urlPattern . '" in the webspace definition "' . $portal->getKey() . '" ' .
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

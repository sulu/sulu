<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Loader\Exception;


use Sulu\Component\Webspace\Webspace;

class InvalidUrlDefinitionException extends \Exception
{
    /**
     * The webspace in which the error occured
     * @var Webspace
     */
    private $webspace;

    /**
     * The pattern which was invalid
     * @var string
     */
    private $urlPattern;

    /**
     * @param Webspace $webspace
     * @param string $urlPattern
     */
    public function __construct(Webspace $webspace, $urlPattern)
    {
        $this->webspace = $webspace;
        $this->urlPattern = $urlPattern;
        $message = 'The url pattern "' . $urlPattern . '" in the webspace definition "' . $webspace->getKey() . '" ' .
            'has not specified the required attributes (either with xml attributes or as placeholders in the pattern)';
        parent::__construct($message, 0);
    }

    /**
     * Returns the webspace in which the error occured
     * @return Webspace
     */
    public function getWebspace()
    {
        return $this->webspace;
    }

    /**
     * Returns the url pattern
     * @return string
     */
    public function getUrlPattern()
    {
        return $this->urlPattern;
    }
}

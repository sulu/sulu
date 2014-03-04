<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace\Loader\Exception;


use Sulu\Component\Workspace\Workspace;

class InvalidUrlDefinitionException extends \Exception
{
    /**
     * The workspace in which the error occured
     * @var Workspace
     */
    private $workspace;

    /**
     * The pattern which was invalid
     * @var string
     */
    private $urlPattern;

    /**
     * @param Workspace $workspace
     * @param string $urlPattern
     */
    public function __construct(Workspace $workspace, $urlPattern)
    {
        $this->workspace = $workspace;
        $this->urlPattern = $urlPattern;
        $message = 'The url pattern "' . $urlPattern . '" in the workspace definition "' . $workspace->getKey() . '" ' .
            'has not specified the required attributes (either with xml attributes or as placeholders in the pattern)';
        parent::__construct($message, 0);
    }

    /**
     * Returns the workspace in which the error occured
     * @return Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
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

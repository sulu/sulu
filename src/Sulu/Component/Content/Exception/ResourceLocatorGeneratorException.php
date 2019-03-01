<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

use Exception;

class ResourceLocatorGeneratorException extends Exception
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $parentPath;

    public function __construct($title, $parentPath)
    {
        parent::__construct(sprintf("Could not generate ResourceLocator for given title '%s'", $title));

        $this->title = $title;
        $this->parentPath = $parentPath;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getParentPath()
    {
        return $this->parentPath;
    }
}

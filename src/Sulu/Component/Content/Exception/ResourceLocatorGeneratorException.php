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

class ResourceLocatorGeneratorException extends \Exception
{
    /**
     * @param string $title
     * @param string $parentPath
     */
    public function __construct(private $title, private $parentPath)
    {
        parent::__construct(\sprintf("Could not generate ResourceLocator for given title '%s'", $title));
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

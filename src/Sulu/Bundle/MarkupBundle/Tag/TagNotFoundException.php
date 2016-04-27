<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tag;

/**
 * This exception will be raised when a not existing tag was requested.
 */
class TagNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $tagName;

    /**
     * @param string $tagName
     */
    public function __construct($tagName)
    {
        parent::__construct(sprintf('Tag "%s" not found', $tagName));

        $this->tagName = $tagName;
    }

    /**
     * Returns tag-name.
     *
     * @return string
     */
    public function getTagName()
    {
        return $this->tagName;
    }
}

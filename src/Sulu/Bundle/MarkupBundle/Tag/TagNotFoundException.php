<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string $namespace
     * @param string $tagName
     * @param string $type
     */
    public function __construct(
        private $namespace,
        private $tagName,
        private $type
    ) {
        parent::__construct(
            \sprintf('Tag "%s:%s" for type "%s" not found', $this->namespace, $this->tagName, $this->type)
        );
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

    /**
     * Returns type of content.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}

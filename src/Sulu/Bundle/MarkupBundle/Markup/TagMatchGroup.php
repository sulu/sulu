<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup;

/**
 * Represents a group of tag-matches with the same namespace and name.
 */
class TagMatchGroup
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $tagName;

    /**
     * @var array
     */
    private $tags;

    /**
     * @param string $namespace
     * @param string $tagName
     * @param array $tags
     */
    public function __construct($namespace, $tagName, array $tags = [])
    {
        $this->namespace = $namespace;
        $this->tagName = $tagName;
        $this->tags = $tags;
    }

    /**
     * Returns namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Returns tagName.
     *
     * @return string
     */
    public function getTagName()
    {
        return $this->tagName;
    }

    /**
     * Returns tags.
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Add a new tag to group.
     *
     * @param string $tag
     * @param array $tagAttributes
     *
     * @return $this
     */
    public function addTag($tag, array $tagAttributes)
    {
        $this->tags[$tag] = $tagAttributes;

        return $this;
    }
}

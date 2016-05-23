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
 * Container for all tags.
 */
class TagRegistry implements TagRegistryInterface
{
    /**
     * @var TagInterface[]
     */
    private $tags;

    /**
     * @param TagInterface[] $tags
     */
    public function __construct(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * {@inheritdoc}
     */
    public function getTag($name, $type)
    {
        if (!array_key_exists($type, $this->tags) || !array_key_exists($name, $this->tags[$type])) {
            throw new TagNotFoundException($name, $type);
        }

        return $this->tags[$type][$name];
    }
}

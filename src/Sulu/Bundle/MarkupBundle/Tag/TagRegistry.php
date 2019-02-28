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
    public function getTag($name, $type, $namespace = 'sulu')
    {
        if (!array_key_exists($type, $this->tags)
            || !array_key_exists($namespace, $this->tags[$type])
            || !array_key_exists($name, $this->tags[$type][$namespace])
        ) {
            throw new TagNotFoundException($namespace, $name, $type);
        }

        return $this->tags[$type][$namespace][$name];
    }
}

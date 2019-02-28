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
interface TagRegistryInterface
{
    /**
     * Returns tag by name.
     *
     * @param string $name
     * @param string $type
     * @param string $namespace
     *
     * @return TagInterface
     *
     * @throws TagNotFoundException
     */
    public function getTag($name, $type, $namespace = 'sulu');
}

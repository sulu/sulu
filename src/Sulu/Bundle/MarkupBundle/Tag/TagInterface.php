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
 * Represents a single tag which returns new tag for attributes.
 */
interface TagInterface
{
    /**
     * Returns new tag with given attributes.
     *
     * @param array $attributesByTag attributes array of each tag occurrence.
     *
     * @return array Tag array to replace all occurrences.
     */
    public function parseAll($attributesByTag);
}

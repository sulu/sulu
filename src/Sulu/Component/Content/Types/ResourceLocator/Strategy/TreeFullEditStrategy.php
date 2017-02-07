<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Strategy;

/**
 * Implements RLP Strategy "tree-full-edit".
 *
 * The generator uses the whole tree.
 * The children will not be updated.
 * Only the full resource-locator is editable.
 */
class TreeFullEditStrategy extends ResourceLocatorStrategy implements ResourceLocatorStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getChildPart($resourceSegment)
    {
        return ltrim($resourceSegment, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        return self::INPUT_TYPE_FULL;
    }
}

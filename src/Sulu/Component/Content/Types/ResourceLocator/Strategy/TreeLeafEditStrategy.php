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
 * Implements RLP Strategy "tree_leaf_edit".
 */
class TreeLeafEditStrategy extends ResourceLocatorStrategy
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'tree_leaf_edit';

    /**
     * {@inheritdoc}
     */
    public function getChildPart($resourceSegment)
    {
        $divider = strrpos($resourceSegment, '/');

        if ($divider === false) {
            return $resourceSegment;
        }

        return substr($resourceSegment, $divider + 1);
    }

    /**
     * {@inheritdoc}
     */
    protected function generatePath($title, $parentPath = null)
    {
        // if parent has no resource create a new tree
        if ($parentPath == null) {
            return '/' . $title;
        }

        // concat parentPath and title to whole tree path
        return $parentPath . '/' . $title;
    }
}

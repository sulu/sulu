<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Document;

/**
 * Represents a home page document.
 *
 * The HomeDocument is the immediate child of the webspace node and
 * contains ALL of the webspace pages within its subtree.
 */
class HomeDocument extends BasePageDocument
{
    /**
     * {@inheritdoc}
     */
    public function getResourceSegment()
    {
        return '/';
    }
}

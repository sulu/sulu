<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\NodeTypes\Content;

/**
 * Node type for representing sulu pages in the PHPCR.
 */
class PageNodeType extends ContentNodeType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu:page';
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaredSupertypeNames()
    {
        return [
            'sulu:content',
        ];
    }
}

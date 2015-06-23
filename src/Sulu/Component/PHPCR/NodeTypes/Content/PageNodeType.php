<?php
/*
 * This file is part of the Sulu CMS.
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
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'sulu:page';
    }

    /**
     * {@inheritDoc}
     */
    public function getDeclaredSupertypeNames()
    {
        return array(
            'sulu:content',
        );
    }
}

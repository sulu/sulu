<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Strategy;

/**
 * implements RLP Strategy "as short as possible"
 */
class TreeStrategy extends RLPStrategy
{

    /**
     * @param string $contentPath base path of content in PHPCR
     * @param $routePath
     */
    public function __construct($contentPath, $routePath)
    {

    }

    /**
     * returns whole path for given ContentNode
     * @param string $title title of new node
     * @param string $parentPath parent path of new contentNode
     * @param string $portal key of portal
     * @return string whole path
     */
    public function generate($title, $parentPath, $portal)
    {
        // TODO: Implement generate() method.
    }
}

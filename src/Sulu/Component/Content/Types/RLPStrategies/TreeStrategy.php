<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\RLPStrategies;

use PHPCR\NodeInterface;

/**
 * implements RLP Strategy "as short as possible"
 */
class TreeStrategy extends RLPStrategy {

    /**
     * returns whole path for given ContentNode
     *
     * @param NodeInterface $node ContentNode to generate RLP
     * @return string whole path
     */
    public function generate(NodeInterface $node)
    {
        // TODO: Implement generate() method.
    }
}

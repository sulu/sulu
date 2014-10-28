<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use PHPCR\NodeInterface;

/**
 * Utility class for PHPCR nodes.
 */
class NodeHelper
{
    /**
     * @param NodeInterface $node
     * @param string $mixin Mixin
     * @return bool
     */
    public static function hasMixin(NodeInterface $node, $mixin)
    {
        $mixinNodeTypes = $node->getPropertyValueWithDefault('jcr:mixinTypes', array());

        return in_array($mixin, $mixinNodeTypes);
    }
}

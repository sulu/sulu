<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Query;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;

/**
 * Utility class to reduce code duplication.
 */
class QueryBuilderUtil
{
    /**
     * Add the children of a SuluNode to the "Doctrine" version of the same node.
     *
     * The doctrine version is required when using the base query builder converter visitor; it is not
     * able to accept Sulu classes.
     *
     * @param AbstractNode $suluNode
     * @param AbstractNode $doctrineNode
     *
     * @return AbstractNode
     */
    public static function addNodeChildren(AbstractNode $suluNode, AbstractNode $doctrineNode)
    {
        foreach ($suluNode->getChildren() as $childNode) {
            $doctrineNode->addChild($childNode);
        }

        return $doctrineNode;
    }
}

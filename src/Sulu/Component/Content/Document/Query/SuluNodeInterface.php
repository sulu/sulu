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
 * All Sulu query builder nodes implement this interface.
 */
interface SuluNodeInterface
{
    /**
     * Return a the Doctrine PHPCR-ODM instance of the node.
     *
     * The parent of the Sulu query builder converter requires the native
     * PHPCR-ODM query builder nodes.
     *
     * @see Sulu\Component\Content\Document\Query\StructureQueryBuilderConverter
     *
     * @return AbstractNode
     */
    public function getDoctrineInstance();
}

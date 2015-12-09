<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder as Doctrine;
use Sulu\Component\Content\Document\Query\QueryBuilderUtil;
use Sulu\Component\Content\Document\Query\SuluNodeInterface;

/**
 * @see Doctrine\ODM\PHPCR\Query\Builder\ConstraintOrx
 */
class ConstraintOrX extends ConstraintFactory implements SuluNodeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCardinalityMap()
    {
        return [
            self::NT_CONSTRAINT => [1, null],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineInstance()
    {
        return QueryBuilderUtil::addNodeChildren($this, new Doctrine\ConstraintOrx($this->getParent()));
    }
}

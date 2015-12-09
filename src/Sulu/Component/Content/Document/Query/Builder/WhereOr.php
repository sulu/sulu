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

/**
 * @see Sulu\Component\Content\Document\Query\Builder\WhereOr
 */
class WhereOr extends Where
{
    /**
     * {@inheritdoc}
     */
    public function getDoctrineInstance()
    {
        return QueryBuilderUtil::addNodeChildren($this, new Doctrine\WhereOr($this->getParent()));
    }
}

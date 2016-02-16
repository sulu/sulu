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

use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder as BaseQueryBuilder;
use Sulu\Component\Content\Document\Query\Builder\Where;

/**
 * Sulu version of the PHPCR-ODM query builder.
 *
 * Introduces the methods which apply to Sulu Structure objects.
 *
 * NOTE: The PHPCR-ODM query builder is a "fluid" query builder which supports,
 *       to a degree, IDE completetion. For this reason it is not easy to
 *       extend. We have therefore needed to extend classes in order that they
 *       instantiate objects which inturn extend our factories.
 *
 *       This is the reason that there are lots of seemingly pointless classes
 *       in the Builder\\ directory.
 */
class QueryBuilder extends BaseQueryBuilder
{
    const DEFAULT_DOCUMENT_ALIAS = '__default__';

    /**
     * @var array
     */
    private $structureMap;

    /**
     * Assign a structure to a document alias.
     *
     * This allows you to use structure fields in criteria as follows:
     *
     * ````
     * $qb->from()->document('foo', 'p');
     * $qb->useStructure('overview');
     * $qb->where()->eq()->structureField('p.field1')->literal('barbar');
     * ````
     *
     * @param string $structureName Name of the structure to register.
     * @param string $documentAlias Document alias to register structure to.
     *
     * @throws \InvalidArgumentException If a document with the given alias is already registered.
     *
     * @return self
     */
    public function useStructure($structureName, $documentAlias = self::DEFAULT_DOCUMENT_ALIAS)
    {
        if (isset($this->structureMap[$documentAlias])) {
            throw new \InvalidArgumentException(sprintf(
                'Structure "%s" has previously been assigned to document alias "%s" when assigning "%s"',
                $this->structureMap[$documentAlias],
                $documentAlias,
                $structureName
            ));
        }

        $this->structureMap[$documentAlias] = $structureName;

        return $this;
    }

    /**
     * Return the document alias to structure name map.
     *
     * @return array
     */
    public function getStructureMap()
    {
        return $this->structureMap;
    }

    /**
     * {@inheritdoc}
     */
    public function where($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);

        return $this->setChild(new Builder\Where($this));
    }

    /**
     * {@inheritdoc}
     */
    public function andWhere($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);

        return $this->addChild(new Builder\WhereAnd($this));
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);

        return $this->addChild(new Builder\WhereOr($this));
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);

        return $this->setChild(new Builder\OrderBy($this));
    }

    /**
     * {@inheritdoc}
     */
    public function addOrderBy($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);

        return $this->addChild(new Builder\OrderByAdd($this));
    }
}

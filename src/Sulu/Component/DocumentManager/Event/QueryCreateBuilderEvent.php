<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\Query\QueryBuilder;

class QueryCreateBuilderEvent extends AbstractEvent
{
    /**
     * @var
     */
    private $queryBuilder;

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return mixed
     *
     * @throws DocumentManagerException
     */
    public function getQueryBuilder()
    {
        if (!$this->queryBuilder) {
            throw new DocumentManagerException(
                'No query builder has been set in listener. A listener should have set the query'
            );
        }

        return $this->queryBuilder;
    }
}

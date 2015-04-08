<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use Symfony\Component\EventDispatcher\Event;
use PHPCR\Query\QueryInterface;
use Sulu\Component\DocumentManager\Query\Query;
use Sulu\Component\DocumentManager\Query\ResultCollection;

class QueryExecuteEvent extends Event
{
    private $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function getQuery() 
    {
        return $this->query;
    }

    public function setResult(ResultCollection $collection)
    {
        $this->result = $collection;
    }

    public function getResult()
    {
        return $this->result;
    }
}

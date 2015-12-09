<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Query;

use Sulu\Component\Content\Document\Query\QueryBuilder;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    private $queryBuilder;

    public function setUp()
    {
        $this->queryBuilder = new QueryBuilder();
    }

    /**
     * Is should assign a structure name to a document alias
     * It should get the document alias to structure name map.
     */
    public function testStructure()
    {
        $this->queryBuilder->useStructure('p', 'overview');
        $this->assertEquals([
            'p' => 'overview',
        ], $this->queryBuilder->getStructureMap());
    }

    /**
     * It should throw an exception if the structure has already been assigned.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Structure "overview" has previously been assigned to document alias "p" when assigning "foobar"
     */
    public function testStructureAlreadyAssigned()
    {
        $this->queryBuilder->useStructure('p', 'overview');
        $this->queryBuilder->useStructure('p', 'foobar');
    }
}

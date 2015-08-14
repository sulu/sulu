<?php

namespace Sulu\Component\Content\Document\Query;

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
}

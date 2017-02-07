<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\Listing;

use Sulu\Component\Rest\Listing\ListQueryBuilder;

class ListQueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testFind()
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'SuluCoreBundle:Example',
            [],
            [],
            [],
            []
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM SuluCoreBundle:Example u', $dql);
    }

    public function testFindWithFields()
    {
        $builder = new ListQueryBuilder(
            [],
            ['field1', 'field2', 'field3'],
            'SuluCoreBundle:Example',
            ['field1', 'field2', 'field3'],
            [],
            [],
            []
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u.field1, u.field2, u.field3 FROM SuluCoreBundle:Example u', $dql);
    }

    public function testFindWithSorting()
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'SuluCoreBundle:Example',
            [],
            ['sortField' => 'ASC'],
            [],
            []
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM SuluCoreBundle:Example u ORDER BY u.sortField ASC', $dql);
    }

    public function testFindWithWhere()
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'SuluCoreBundle:Example',
            [],
            [],
            ['field1' => 1, 'field2' => 2],
            []
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM SuluCoreBundle:Example u WHERE u.field1 = 1 AND u.field2 = 2', $dql);
    }

    public function testFindWithSearch()
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'SuluCoreBundle:Example',
            [],
            [],
            [],
            ['field']
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM SuluCoreBundle:Example u WHERE (u.field LIKE :search)', $dql);
    }

    public function testFindWithWhereAndSearch()
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'SuluCoreBundle:Example',
            [],
            [],
            ['field1' => 1, 'field2' => 2],
            ['field']
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals(
            'SELECT u FROM SuluCoreBundle:Example u WHERE u.field1 = 1 AND u.field2 = 2 AND (u.field LIKE :search)',
            $dql
        );
    }

    public function testFindWithWhereAndNumericSearch()
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'SuluCoreBundle:Example',
            [],
            [],
            ['field1' => 1, 'field2' => 2],
            ['field1'],
            ['field2', 'field3']
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals(
            'SELECT u FROM SuluCoreBundle:Example u WHERE u.field1 = 1 AND u.field2 = 2 AND (u.field1 LIKE :search OR u.field2 = :strictSearch OR u.field3 = :strictSearch)',
            $dql
        );
    }

    public function testFindWithJoins()
    {
        $builder = new ListQueryBuilder(
            ['object', 'otherobject'],
            [],
            'SuluCoreBundle:Example',
            ['object_field1', 'object_field2', 'otherobject_field3'],
            [],
            [],
            []
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals(
            'SELECT object.field1 object_field1, object.field2 object_field2, otherobject.field3 otherobject_field3 ' .
            'FROM SuluCoreBundle:Example u LEFT JOIN u.object object LEFT JOIN u.otherobject otherobject',
            $dql
        );
    }

    public function testCounting()
    {
        $builder = new ListQueryBuilder(
            [],
            [],
            'SuluCoreBundle:Example',
            [],
            [],
            [],
            []
        );

        $builder->justCount('u.id', 'total');

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT COUNT(u.id) as total FROM SuluCoreBundle:Example u', $dql);
    }
}

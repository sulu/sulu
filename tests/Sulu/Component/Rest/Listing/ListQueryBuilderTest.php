<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Listing;

class ListQueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testFind()
    {
        $builder = new ListQueryBuilder(
            array(),
            array(),
            'SuluCoreBundle:Example',
            array(),
            array(),
            array(),
            array()
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM SuluCoreBundle:Example u', $dql);
    }

    public function testFindWithFields()
    {
        $builder = new ListQueryBuilder(
            array(),
            array('field1', 'field2', 'field3'),
            'SuluCoreBundle:Example',
            array('field1', 'field2', 'field3'),
            array(),
            array(),
            array()
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u.field1, u.field2, u.field3 FROM SuluCoreBundle:Example u', $dql);
    }

    public function testFindWithSorting()
    {
        $builder = new ListQueryBuilder(
            array(),
            array(),
            'SuluCoreBundle:Example',
            array(),
            array('sortField' => 'ASC'),
            array(),
            array()
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM SuluCoreBundle:Example u ORDER BY u.sortField ASC', $dql);
    }

    public function testFindWithWhere()
    {
        $builder = new ListQueryBuilder(
            array(),
            array(),
            'SuluCoreBundle:Example',
            array(),
            array(),
            array('field1' => 1, 'field2' => 2),
            array()
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM SuluCoreBundle:Example u WHERE u.field1 = 1 AND u.field2 = 2', $dql);
    }

    public function testFindWithSearch()
    {
        $builder = new ListQueryBuilder(
            array(),
            array(),
            'SuluCoreBundle:Example',
            array(),
            array(),
            array(),
            array('field')
        );

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT u FROM SuluCoreBundle:Example u WHERE (u.field LIKE :search)', $dql);
    }

    public function testFindWithWhereAndSearch()
    {
        $builder = new ListQueryBuilder(
            array(),
            array(),
            'SuluCoreBundle:Example',
            array(),
            array(),
            array('field1' => 1, 'field2' => 2),
            array('field')
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
            array(),
            array(),
            'SuluCoreBundle:Example',
            array(),
            array(),
            array('field1' => 1, 'field2' => 2),
            array('field1'),
            array('field2', 'field3')
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
            array('object', 'otherobject'),
            array(),
            'SuluCoreBundle:Example',
            array('object_field1', 'object_field2', 'otherobject_field3'),
            array(),
            array(),
            array()
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
            array(),
            array(),
            'SuluCoreBundle:Example',
            array(),
            array(),
            array(),
            array()
        );

        $builder->justCount('u.id', 'total');

        $dql = str_replace(' ,', ',', trim(preg_replace('/\s+/', ' ', $builder->find())));

        $this->assertEquals('SELECT COUNT(u.id) as total FROM SuluCoreBundle:Example u', $dql);
    }
}

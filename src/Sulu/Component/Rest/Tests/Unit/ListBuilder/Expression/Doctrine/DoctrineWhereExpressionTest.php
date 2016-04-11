<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Prophecy\Argument;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineWhereExpression;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class DoctrineWhereExpressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private static $entityName = 'SuluCoreBundle:Example';

    /**
     * http://php.net/manual/en/function.uniqid.php
     * With an empty prefix, the returned string will be 13 characters long. If more_entropy is TRUE,
     * it will be 23 characters.
     *
     * @var int
     */
    private $uniqueIdLength = 23;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    public function setUp()
    {
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
        $this->queryBuilder->setParameter(Argument::any(), Argument::any())->willReturn($this->queryBuilder->reveal());
    }

    public function testGetStatement()
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $value = 'test';
        $whereExpression = new DoctrineWhereExpression($fieldDescriptor, $value);

        // parameter names will be generated (combined with unique ids with length of 23 characters)
        $statement = $whereExpression->getStatement($this->queryBuilder->reveal());
        $result = preg_match(
            '/^SuluCoreBundle:Example\.name = :name[\S]{' . $this->uniqueIdLength . '}/',
            $statement
        );
        $this->assertEquals(1, $result);
    }

    public function nullProvider()
    {
        return [
            [ListBuilderInterface::WHERE_COMPARATOR_EQUAL, 'IS NULL'],
            [ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL, 'IS NOT NULL'],
        ];
    }

    /**
     * @dataProvider nullProvider
     */
    public function testGetStatementNullValue($comparator, $expected)
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $whereExpression = new DoctrineWhereExpression($fieldDescriptor, null, $comparator);

        $this->assertEquals(
            'SuluCoreBundle:Example.name ' . $expected,
            $whereExpression->getStatement($this->queryBuilder->reveal())
        );
    }

    public function testGetStatementLike()
    {
        $value = 'test';
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $whereExpression = new DoctrineWhereExpression($fieldDescriptor, $value, 'LIKE');

        $this->queryBuilder->setParameter(Argument::containingString('name'), '%' . $value . '%');

        // parameter names will be generated (combined with unique ids with length of 23 characters)
        $statement = $whereExpression->getStatement($this->queryBuilder->reveal());
        $result = preg_match(
            '/^SuluCoreBundle:Example\.name LIKE :name[\S]{' . $this->uniqueIdLength . '}/',
            $statement
        );
        $this->assertEquals(1, $result);
    }

    public function andOrProvider()
    {
        return [
            ['and'],
            ['or'],
        ];
    }

    /**
     * @dataProvider andOrProvider
     */
    public function testGetStatementAndOr($comparator)
    {
        $value = [1, 2, 3];
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $whereExpression = new DoctrineWhereExpression($fieldDescriptor, $value, $comparator);

        // parameter names will be generated (combined with unique ids with length of 23 characters)
        $statement = $whereExpression->getStatement($this->queryBuilder->reveal());
        $result = preg_match(
            '/^(SuluCoreBundle:Example\.name = :name[\S]{' . $this->uniqueIdLength . '}[\S]{1}( ' . $comparator . ' )?){3}/',
            $statement
        );
        $this->assertEquals(1, $result);
    }
}

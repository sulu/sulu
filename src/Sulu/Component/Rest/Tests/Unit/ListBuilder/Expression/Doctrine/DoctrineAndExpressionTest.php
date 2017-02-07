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
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineAndExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineWhereExpression;

class DoctrineAndExpressionTest extends \PHPUnit_Framework_TestCase
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
        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder->expects($this->any())->method('setParameter')->willReturnSelf();
    }

    public function testGetStatement()
    {
        $fieldDescriptor1 = new DoctrineFieldDescriptor('name1', 'name1', self::$entityName);
        $value1 = 'test1';
        $fieldDescriptor2 = new DoctrineFieldDescriptor('name2', 'name2', self::$entityName);
        $value2 = 'test2';
        $whereExpression1 = new DoctrineWhereExpression($fieldDescriptor1, $value1);
        $whereExpression2 = new DoctrineWhereExpression($fieldDescriptor2, $value2);
        $andExpression = new DoctrineAndExpression([$whereExpression1, $whereExpression2]);

        $statement = $andExpression->getStatement($this->queryBuilder);
        $result = preg_match(
            '/^SuluCoreBundle:Example\.name1 = :name1[\S]{' . $this->uniqueIdLength .
            '} AND SuluCoreBundle:Example\.name2 = :name2[\S]{' . $this->uniqueIdLength . '}/',
            $statement
        );

        $this->assertEquals(1, $result);
    }
}

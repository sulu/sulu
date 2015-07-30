<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;

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
        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder->expects($this->any())->method('setParameter')->willReturnSelf();
    }

    public function testGetStatement()
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $value = 'test';
        $whereExpression = new DoctrineWhereExpression($fieldDescriptor, $value);

        // parameter names will be generated (combined with unique ids with length of 23 characters)
        $statement = $whereExpression->getStatement($this->queryBuilder);
        $result = preg_match(
            '/^SuluCoreBundle:Example\.name = :name[\S]{' . $this->uniqueIdLength . '}/',
            $statement
        );
        $this->assertEquals(1, $result);
    }
}

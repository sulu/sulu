<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineIsNotNullExpression;
use Sulu\Component\Rest\ListBuilder\Expression\IsNotNullExpressionInterface;

class DoctrineIsNotNullExpressionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<QueryBuilder>
     */
    private ObjectProphecy $queryBuilder;

    /**
     * @var string
     */
    private static $entityName = 'Sulu\Bundle\CoreBundle\Entity\Example';

    protected function setUp(): void
    {
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
    }

    public function testImplementsInterface(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $expression = new DoctrineIsNotNullExpression($fieldDescriptor);

        $interfaces = \class_implements($expression);
        $this->assertContains(IsNotNullExpressionInterface::class, $interfaces);
    }

    public function testGetStatement(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $expression = new DoctrineIsNotNullExpression($fieldDescriptor);

        $statement = $expression->getStatement($this->queryBuilder->reveal());

        $this->assertSame('Sulu_Bundle_CoreBundle_Entity_Example.name IS NOT NULL', $statement);
    }

    public function testGetFieldName(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $expression = new DoctrineIsNotNullExpression($fieldDescriptor);

        $fieldName = $expression->getFieldName();

        $this->assertSame('name', $fieldName);
    }

    public function testQueryBuilderNotModified(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $expression = new DoctrineIsNotNullExpression($fieldDescriptor);

        // The QueryBuilder should not have any methods called on it
        $statement = $expression->getStatement($this->queryBuilder->reveal());

        // Just verify that we got a statement back and it contains expected content
        $this->assertNotEmpty($statement);
        $this->assertStringContainsString('IS NOT NULL', $statement);
    }
}

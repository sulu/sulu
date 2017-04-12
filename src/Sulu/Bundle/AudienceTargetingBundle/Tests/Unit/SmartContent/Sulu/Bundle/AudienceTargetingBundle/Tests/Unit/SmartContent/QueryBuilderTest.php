<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\SmartContent;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\SmartContent\QueryBuilder;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentQueryBuilderInterface
     */
    private $innerQueryBuilder;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    public function setUp()
    {
        $this->innerQueryBuilder = $this->prophesize(ContentQueryBuilderInterface::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->targetGroupEvaluator = $this->prophesize(TargetGroupEvaluatorInterface::class);

        $this->queryBuilder = new QueryBuilder(
            $this->innerQueryBuilder->reveal(),
            $this->structureManager->reveal(),
            $this->targetGroupEvaluator->reveal(),
            'i18n'
        );
    }

    public function testBuild()
    {
        $this->innerQueryBuilder->build('sulu_io', ['en', 'de'])
            ->willReturn(['SELECT * FROM [nt:unstructured] AS page WHERE page.test = "test"', ['test']]);

        $targetGroup = $this->prophesize(TargetGroupInterface::class);
        $targetGroup->getId()->willReturn(1);
        $this->targetGroupEvaluator->evaluate()->willReturn($targetGroup->reveal());

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('audience_targeting_groups');
        $property->getMultilingual()->willReturn(true);
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getProperty('audience_targeting_groups')->willReturn($property->reveal());
        $this->structureManager->getStructure('excerpt')->willReturn($structure->reveal());

        $result = $this->queryBuilder->build('sulu_io', ['en', 'de']);

        $this->assertEquals(
            $result[0],
            'SELECT * FROM [nt:unstructured] AS page WHERE page.[i18n:de-excerpt-audience_targeting_groups] = 1 '
            . 'AND page.[i18n:en-excerpt-audience_targeting_groups] = 1 AND page.test = "test"'
        );
        $this->assertEquals($result[1], ['test']);
    }

    public function testBuildWithoutTargetGroups()
    {
        $this->innerQueryBuilder->build('sulu_io', ['en', 'de'])
            ->willReturn(['SELECT * FROM [nt:unstructured] AS page WHERE page.test = "test"', ['test']]);

        $result = $this->queryBuilder->build('sulu_io', ['en', 'de']);

        $this->assertEquals(
            $result[0],
            'SELECT * FROM [nt:unstructured] AS page WHERE page.test = "test"'
        );
        $this->assertEquals($result[1], ['test']);
    }

    public function testInit()
    {
        $this->innerQueryBuilder->init(['test' => 123])->shouldBeCalled();
        $this->queryBuilder->init(['test' => 123]);
    }

    public function testGetPublished()
    {
        $this->innerQueryBuilder->getPublished()->shouldBeCalled();
        $this->queryBuilder->getPublished();
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\SmartContent;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\SmartContent\QueryBuilder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

#[CoversClass(QueryBuilder::class)]
final class QueryBuilderTest extends TestCase
{
    use ProphecyTrait;

    public function testBuildCategoriesWhereCastsIdsToIntAndStripsInjection(): void
    {
        $queryBuilder = $this->createQueryBuilder('categories');

        // a non-numeric payload becomes 0, a digit-leading payload keeps only the leading int
        $where = $queryBuilder->exposeBuildCategoriesWhere(["abc') OR 1=1 --", '5] UNION SELECT'], 'or', 'en');

        $this->assertSame('(page.[excerpt-categories] = 0 OR page.[excerpt-categories] = 5)', $where);
        $this->assertStringNotContainsString('OR 1=1', $where);
        $this->assertStringNotContainsString('UNION', $where);
    }

    public function testBuildTagsWhereCastsIdsToIntAndStripsInjection(): void
    {
        $queryBuilder = $this->createQueryBuilder('tags');

        $where = $queryBuilder->exposeBuildTagsWhere(['3] OR 1=1 --'], 'or', 'en');

        $this->assertSame('(page.[excerpt-tags] = 3)', $where);
        $this->assertStringNotContainsString('OR 1=1', $where);
    }

    private function createQueryBuilder(string $propertyName): TestQueryBuilder
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn($propertyName);
        $property->getMultilingual()->willReturn(false);

        $structure = $this->prophesize(StructureInterface::class);
        $structure->hasProperty($propertyName)->willReturn(true);
        $structure->getProperty($propertyName)->willReturn($property->reveal());

        $structureManager = $this->prophesize(StructureManagerInterface::class);
        $structureManager->getStructure('excerpt')->willReturn($structure->reveal());

        return new TestQueryBuilder(
            $structureManager->reveal(),
            $this->prophesize(ExtensionManagerInterface::class)->reveal(),
            $this->prophesize(SessionManagerInterface::class)->reveal(),
            'i18n'
        );
    }
}

/**
 * Exposes the protected where-clause builders of the smart content QueryBuilder for testing.
 */
class TestQueryBuilder extends QueryBuilder
{
    /**
     * @param array<string> $categories
     */
    public function exposeBuildCategoriesWhere(array $categories, string $operator, string $languageCode): string
    {
        return $this->buildCategoriesWhere($categories, $operator, $languageCode);
    }

    /**
     * @param array<string> $tags
     */
    public function exposeBuildTagsWhere(array $tags, string $operator, string $languageCode): string
    {
        return $this->buildTagsWhere($tags, $operator, $languageCode);
    }
}

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

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
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

    public function testBuildCategoriesWhereRejectsInjectedOperator(): void
    {
        $queryBuilder = $this->createQueryBuilder('categories');

        $where = $queryBuilder->exposeBuildCategoriesWhere(['1', '2'], 'OR page.[i18n:en-template] IS NOT NULL OR', 'en');

        $this->assertSame('(page.[excerpt-categories] = 1 OR page.[excerpt-categories] = 2)', $where);
        $this->assertStringNotContainsString('IS NOT NULL', $where);
    }

    public function testBuildTagsWherePreservesAndOperator(): void
    {
        $queryBuilder = $this->createQueryBuilder('tags');

        $where = $queryBuilder->exposeBuildTagsWhere(['1', '2'], 'and', 'en');

        $this->assertSame('(page.[excerpt-tags] = 1 AND page.[excerpt-tags] = 2)', $where);
    }

    public function testBuildTypesWhereEscapesTheTypeLiteral(): void
    {
        $queryBuilder = $this->createQueryBuilder('any');

        $where = $queryBuilder->exposeBuildTypesWhere(['default', "foo' OR 'a'='a"], 'en');

        $this->assertSame(
            "(page.[i18n:en-template] = 'default' or page.[i18n:en-template] = 'foo'' OR ''a''=''a')",
            $where
        );
        $this->assertStringNotContainsString("= 'foo' OR", $where);
    }

    public function testBuildWhereUsesTheLocaleOfMultilingualProperties(): void
    {
        $queryBuilder = $this->createQueryBuilder('tags', true);

        $where = $queryBuilder->exposeBuildWhere(['tags' => ['1', '2']], 'sulu_io', 'en');

        $this->assertStringContainsString(
            '(page.[i18n:en-excerpt-tags] = 1 OR page.[i18n:en-excerpt-tags] = 2)',
            $where
        );
    }

    public function testBuildWhereRejectsInvalidLocale(): void
    {
        $queryBuilder = $this->createQueryBuilder('tags', true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid locale');
        $queryBuilder->exposeBuildWhere(['tags' => ['1']], 'sulu_io', 'en]');
    }

    public function testBuildWhereAcceptsNullWebspaceWithDataSource(): void
    {
        $queryBuilder = $this->createQueryBuilder('tags', true, 'some-uuid');

        $where = $queryBuilder->exposeBuildWhere(['dataSource' => 'some-uuid', 'tags' => ['1']], null, 'en');

        $this->assertSame(
            "ISCHILDNODE(page, '/cmf/sulu_io/contents') AND (page.[i18n:en-excerpt-tags] = 1)",
            $where
        );
    }

    public function testBuildWhereRejectsInvalidWebspaceKeyWithoutDataSource(): void
    {
        $queryBuilder = $this->createQueryBuilder('tags', true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid webspace');
        $queryBuilder->exposeBuildWhere(['tags' => ['1']], 'sulu_io]', 'en');
    }

    public function testBuildWhereRejectsInvalidWebspaceKeyForSegment(): void
    {
        $queryBuilder = $this->createQueryBuilder('segments', true, 'some-uuid');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid webspace');
        $queryBuilder->exposeBuildWhere(['dataSource' => 'some-uuid', 'segmentKey' => 'w'], 'sulu_io]', 'en');
    }

    public function testBuildSegmentKeyEscapesTheSegmentLiteral(): void
    {
        $queryBuilder = $this->createQueryBuilder('segments');

        $this->assertStringContainsString(
            "= 'a''b'",
            (string) $queryBuilder->exposeBuildSegmentKey('website', "a'b", 'en')
        );

        $doubleQuote = (string) $queryBuilder->exposeBuildSegmentKey('website', 'x" OR "a"="a', 'en');
        $this->assertStringContainsString('= \'x" OR "a"="a\'', $doubleQuote);
        $this->assertStringNotContainsString('= "', $doubleQuote);
    }

    public function testBuildOrderDropsInvalidSortBy(): void
    {
        $queryBuilder = $this->createQueryBuilder('any');

        $order = $queryBuilder->exposeBuildOrder(['sortBy' => 'title] DESC, page.[x'], 'en');

        $this->assertSame('page.[sulu:order] ASC', $order);
        $this->assertStringNotContainsString('title]', $order);
    }

    public function testBuildOrderKeepsValidSortBy(): void
    {
        $queryBuilder = $this->createQueryBuilder('any');

        $this->assertSame(
            'lower(page.[i18n:en-title] )ASC',
            $queryBuilder->exposeBuildOrder(['sortBy' => 'title'], 'en')
        );
    }

    public function testBuildOrderRejectsInvalidLocale(): void
    {
        $queryBuilder = $this->createQueryBuilder('any');

        $this->expectException(\InvalidArgumentException::class);
        $queryBuilder->exposeBuildOrder(['sortBy' => 'title'], 'en] OR [x');
    }

    private function createQueryBuilder(
        string $propertyName,
        bool $multilingual = false,
        ?string $dataSource = null,
    ): TestQueryBuilder {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn($propertyName);
        $property->getMultilingual()->willReturn($multilingual);

        $structure = $this->prophesize(StructureInterface::class);
        $structure->hasProperty($propertyName)->willReturn(true);
        $structure->getProperty($propertyName)->willReturn($property->reveal());

        $structureManager = $this->prophesize(StructureManagerInterface::class);
        $structureManager->getStructure('excerpt')->willReturn($structure->reveal());

        $sessionManager = $this->prophesize(SessionManagerInterface::class);
        if (null !== $dataSource) {
            $node = $this->prophesize(NodeInterface::class);
            $node->getPath()->willReturn('/cmf/sulu_io/contents');
            $session = $this->prophesize(SessionInterface::class);
            $session->getNodeByIdentifier($dataSource)->willReturn($node->reveal());
            $sessionManager->getSession()->willReturn($session->reveal());
        }

        return new TestQueryBuilder(
            $structureManager->reveal(),
            $this->prophesize(ExtensionManagerInterface::class)->reveal(),
            $sessionManager->reveal(),
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

    /**
     * @param array<string> $types
     */
    public function exposeBuildTypesWhere(array $types, string $languageCode): string
    {
        return $this->buildTypesWhere($types, $languageCode);
    }

    public function exposeBuildSegmentKey(string $webspaceKey, string $segmentKey, string $locale): ?string
    {
        return $this->buildSegmentKey($webspaceKey, $segmentKey, $locale);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function exposeBuildWhere(array $config, ?string $webspaceKey, string $locale): string
    {
        $this->init(['config' => $config]);

        return $this->buildWhere($webspaceKey, $locale);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function exposeBuildOrder(array $config, string $locale): string
    {
        $this->init(['config' => $config]);

        return $this->buildOrder('website', $locale);
    }
}

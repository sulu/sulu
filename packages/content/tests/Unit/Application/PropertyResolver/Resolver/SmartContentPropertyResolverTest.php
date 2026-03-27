<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Application\PropertyResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Sulu\Content\Application\PropertyResolver\Resolver\SmartContentPropertyResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SmartContentPropertyResolverTest extends TestCase
{
    private SmartContentPropertyResolver $resolver;

    protected function setUp(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/'));
        $this->resolver = new SmartContentPropertyResolver($requestStack, []);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function resolveFilters(array $data, array $params): array
    {
        $result = $this->resolver->resolve($data, 'en', $params); // @phpstan-ignore argument.type
        /** @var SmartResolvable $resolvable */
        $resolvable = $result->getContent();
        /** @var array{filters: array<string, mixed>} $resolvableData */
        $resolvableData = $resolvable->getData();

        return $resolvableData['filters'];
    }

    public function testTypesFromSavedData(): void
    {
        $filters = $this->resolveFilters(
            ['types' => ['news', 'press']],
            ['provider' => 'articles'],
        );

        $this->assertSame(['news', 'press'], $filters['types']);
    }

    public function testNoTypesWhenNothingProvided(): void
    {
        $filters = $this->resolveFilters([], ['provider' => 'articles']);

        $this->assertSame([], $filters['types']);
    }

    public function testBasicFiltersResolved(): void
    {
        $filters = $this->resolveFilters(
            [
                'categories' => [1, 2],
                'tags' => ['tag1'],
                'categoryOperator' => 'AND',
                'tagOperator' => 'OR',
                'dataSource' => 'some-uuid',
                'limitResult' => 10,
                'includeSubFolders' => true,
            ],
            ['provider' => 'pages'],
        );

        $this->assertSame([1, 2], $filters['categories']);
        $this->assertSame(['tag1'], $filters['tags']);
        $this->assertSame('AND', $filters['categoryOperator']);
        $this->assertSame('OR', $filters['tagOperator']);
        $this->assertSame('some-uuid', $filters['dataSource']);
        $this->assertSame(10, $filters['limit']);
        $this->assertTrue($filters['includeSubFolders']);
    }

    public function testEmptyDataReturnsDefaults(): void
    {
        $filters = $this->resolveFilters([], ['provider' => 'pages']);

        $this->assertSame([], $filters['categories']);
        $this->assertSame([], $filters['tags']);
        $this->assertSame('OR', $filters['categoryOperator']);
        $this->assertSame('OR', $filters['tagOperator']);
        $this->assertSame([], $filters['types']);
        $this->assertNull($filters['dataSource']);
        $this->assertNull($filters['limit']);
        $this->assertFalse($filters['includeSubFolders']);
    }

    public function testNoTemplateKeysInFilters(): void
    {
        $filters = $this->resolveFilters([], ['provider' => 'articles']);

        $this->assertArrayNotHasKey('templateKeys', $filters);
    }
}

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

namespace Sulu\Content\Tests\Unit\Content\Application\SmartResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfiguration;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Sulu\Content\Application\SmartResolver\Resolver\SmartContentSmartResolver;
use Sulu\Content\Application\ContentResolver\ContentDeduplicationTracker;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SmartContentSmartResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SmartContentProviderInterface>
     */
    private ObjectProphecy $smartContentProvider;

    /**
     * @var ObjectProphecy<ServiceLocator<SmartContentProviderInterface>>
     */
    private ObjectProphecy $serviceLocator;

    private SmartContentSmartResolver $resolver;

    protected function setUp(): void
    {
        $this->smartContentProvider = $this->prophesize(SmartContentProviderInterface::class);
        $this->smartContentProvider->getResourceLoaderKey()->willReturn('test_resource_loader');
        $this->smartContentProvider->getType()->willReturn('test_provider');
        $this->smartContentProvider->getConfiguration()->willReturn(new ProviderConfiguration());

        $this->serviceLocator = $this->prophesize(ServiceLocator::class); // @phpstan-ignore assign.propertyType
        $this->serviceLocator->getProvidedServices()->willReturn(['test_provider' => 'service']);
        $this->serviceLocator->has('test_provider')->willReturn(true);
        $this->serviceLocator->get('test_provider')->willReturn($this->smartContentProvider->reveal());

        $this->resolver = new SmartContentSmartResolver(
            $this->serviceLocator->reveal(),
            new ContentDeduplicationTracker(),
        );
    }

    public function testResolveUsingMaxPerPage(): void
    {
        $data = [
            'value' => ['presentAs' => 'grid'],
            'filters' => [
                'dataSource' => '',
                'includeSubFolders' => true,
                'excludeDuplicates' => false,
                'categories' => [1, 2],
                'categoryOperator' => 'OR',
                'tags' => [],
                'tagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'typesOperator' => 'OR',
                'limit' => null,
                'maxPerPage' => 12,
                'page' => 1,
            ],
            'sortBys' => null,
            'parameters' => ['provider' => 'test_provider'],
        ];

        $mockResults = \array_fill(0, 12, ['id' => 'uuid-123', 'title' => 'Test Article']);
        $this->smartContentProvider->findFlatBy(Argument::cetera())->willReturn($mockResults);
        $this->smartContentProvider->countBy(Argument::cetera())->willReturn(14);

        $resolvable = new SmartResolvable($data, 'smart_content', 100);

        $result = $this->resolver->resolve($resolvable, 'en');
        $view = $result->getView();

        $this->assertTrue($view['hasNextPage']);
        $this->assertTrue($view['paginated']);
        $this->assertSame(2, $view['maxPage']);
        $this->assertSame(12, $view['maxPerPage']);
        $this->assertSame(1, $view['page']);
        $this->assertSame(14, $view['total']);
        $this->assertNull($view['limit']);
    }

    public function testResolveWithPaginationOnLastPage(): void
    {
        $data = [
            'value' => ['presentAs' => 'grid'],
            'filters' => [
                'dataSource' => '',
                'includeSubFolders' => true,
                'excludeDuplicates' => false,
                'categories' => [],
                'categoryOperator' => 'OR',
                'tags' => [],
                'tagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'typesOperator' => 'OR',
                'limit' => null,
                'maxPerPage' => 12,
                'page' => 2,
            ],
            'sortBys' => null,
            'parameters' => ['provider' => 'test_provider'],
        ];

        $mockResults = \array_fill(0, 2, ['id' => 'uuid-456', 'title' => 'Test Article']);
        $this->smartContentProvider->findFlatBy(Argument::cetera())->willReturn($mockResults);
        $this->smartContentProvider->countBy(Argument::cetera())->willReturn(14);

        $resolvable = new SmartResolvable($data, 'smart_content', 100);

        $result = $this->resolver->resolve($resolvable, 'en');

        $view = $result->getView();

        $this->assertFalse($view['hasNextPage']);
        $this->assertTrue($view['paginated']);
        $this->assertSame(2, $view['maxPage']);
        $this->assertSame(2, $view['page']);
        $this->assertSame(14, $view['total']);
    }

    public function testResolveWithLimitAndMaxPerPage(): void
    {
        $data = [
            'value' => ['presentAs' => 'list'],
            'filters' => [
                'dataSource' => '',
                'includeSubFolders' => false,
                'excludeDuplicates' => false,
                'categories' => [],
                'categoryOperator' => 'OR',
                'tags' => [],
                'tagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'typesOperator' => 'OR',
                'limit' => 50,
                'maxPerPage' => 12,
                'page' => 1,
            ],
            'sortBys' => null,
            'parameters' => ['provider' => 'test_provider'],
        ];

        $mockResults = \array_fill(0, 12, ['id' => 'uuid-789', 'title' => 'Test Article']);
        $this->smartContentProvider->findFlatBy(Argument::cetera())->willReturn($mockResults);
        $this->smartContentProvider->countBy(Argument::cetera())->willReturn(100);

        $resolvable = new SmartResolvable($data, 'smart_content', 100);

        $result = $this->resolver->resolve($resolvable, 'en');

        $view = $result->getView();

        $this->assertTrue($view['hasNextPage']);
        $this->assertTrue($view['paginated']);
        $this->assertSame(50, $view['limit']);
        $this->assertSame(12, $view['maxPerPage']);
        $this->assertSame(50, $view['total']);
        $this->assertSame(5, $view['maxPage']);
    }

    public function testResolveWithoutPagination(): void
    {
        $data = [
            'value' => ['presentAs' => 'list'],
            'filters' => [
                'dataSource' => '',
                'includeSubFolders' => false,
                'excludeDuplicates' => false,
                'categories' => [],
                'categoryOperator' => 'OR',
                'tags' => [],
                'tagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'typesOperator' => 'OR',
                'limit' => 20,
                'maxPerPage' => null,
                'page' => 1,
            ],
            'sortBys' => null,
            'parameters' => ['provider' => 'test_provider'],
        ];

        $mockResults = \array_fill(0, 15, ['id' => 'uuid-101', 'title' => 'Test Article']);
        $this->smartContentProvider->findFlatBy(Argument::cetera())->willReturn($mockResults);
        $this->smartContentProvider->countBy(Argument::cetera())->willReturn(15);

        $resolvable = new SmartResolvable($data, 'smart_content', 100);

        $result = $this->resolver->resolve($resolvable, 'en');

        $view = $result->getView();

        $this->assertFalse($view['hasNextPage']);
        $this->assertFalse($view['paginated']);
        $this->assertNull($view['maxPage']);
        $this->assertNull($view['maxPerPage']);
        $this->assertSame(20, $view['limit']);
        $this->assertSame(15, $view['total']);
    }

    public function testResolveWithInvalidMaxPerPage(): void
    {
        $data = [
            'value' => ['presentAs' => 'grid'],
            'filters' => [
                'dataSource' => '',
                'includeSubFolders' => false,
                'excludeDuplicates' => false,
                'categories' => [],
                'categoryOperator' => 'OR',
                'tags' => [],
                'tagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'typesOperator' => 'OR',
                'limit' => null,
                'maxPerPage' => 0,
                'page' => 1,
            ],
            'sortBys' => null,
            'parameters' => ['provider' => 'test_provider'],
        ];

        $resolvable = new SmartResolvable($data, 'smart_content', 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "maxPerPage" parameter must be a positive integer.');

        $this->resolver->resolve($resolvable, 'en');
    }

    public function testResolveWithInvalidProvider(): void
    {
        $serviceLocator = $this->prophesize(ServiceLocator::class);
        $serviceLocator->getProvidedServices()->willReturn([]);
        $serviceLocator->has('invalid_provider')->willReturn(false);

        $resolver = new SmartContentSmartResolver(
            $serviceLocator->reveal(),
            new ContentDeduplicationTracker(),
        );

        $data = [
            'value' => ['presentAs' => 'grid'],
            'filters' => [
                'dataSource' => '',
                'includeSubFolders' => false,
                'excludeDuplicates' => false,
                'categories' => [],
                'categoryOperator' => 'OR',
                'tags' => [],
                'tagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'typesOperator' => 'OR',
                'limit' => null,
                'maxPerPage' => 12,
                'page' => 1,
            ],
            'sortBys' => null,
            'parameters' => ['provider' => 'invalid_provider'],
        ];

        $resolvable = new SmartResolvable($data, 'smart_content', 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No smart content provider found for key "invalid_provider"');

        $resolver->resolve($resolvable, 'en');
    }

    public function testResolveWithLimitSmallerThanMaxPerPage(): void
    {
        $data = [
            'value' => ['presentAs' => 'list'],
            'filters' => [
                'dataSource' => '',
                'includeSubFolders' => false,
                'excludeDuplicates' => false,
                'categories' => [],
                'categoryOperator' => 'OR',
                'tags' => [],
                'tagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'typesOperator' => 'OR',
                'limit' => 5,
                'maxPerPage' => 12,
                'page' => 1,
            ],
            'sortBys' => null,
            'parameters' => ['provider' => 'test_provider'],
        ];

        $mockResults = \array_fill(0, 5, ['id' => 'uuid-limit', 'title' => 'Test Article']);
        $this->smartContentProvider->findFlatBy(Argument::cetera())->willReturn($mockResults);
        $this->smartContentProvider->countBy(Argument::cetera())->willReturn(100);

        $resolvable = new SmartResolvable($data, 'smart_content', 100);

        $result = $this->resolver->resolve($resolvable, 'en');

        $view = $result->getView();

        $this->assertFalse($view['hasNextPage']);
        $this->assertTrue($view['paginated']);
        $this->assertSame(1, $view['maxPage']);
        $this->assertSame(5, $view['total']);
        $this->assertSame(5, $view['limit']);
        $this->assertSame(12, $view['maxPerPage']);
    }
}

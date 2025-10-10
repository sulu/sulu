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

namespace Sulu\Content\Tests\Unit\Application\SmartResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Sulu\Content\Application\SmartResolver\Resolver\SmartContentSmartResolver;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SmartContentSmartResolverTest extends TestCase
{
    use ProphecyTrait;

    private SmartContentSmartResolver $smartResolver;
    /** @var ObjectProphecy<ServiceLocator<SmartContentProviderInterface>> */
    private ObjectProphecy $serviceLocator;

    protected function setUp(): void
    {
        /** @var ObjectProphecy<ServiceLocator<SmartContentProviderInterface>> $serviceLocator */
        $serviceLocator = $this->prophesize(ServiceLocator::class);
        $this->serviceLocator = $serviceLocator;

        $this->smartResolver = new SmartContentSmartResolver(
            $this->serviceLocator->reveal(),
        );
    }

    public function testGetType(): void
    {
        $this->assertSame('smart_content', SmartContentSmartResolver::getType());
    }

    public function testResolveBasic(): void
    {
        $smartResolvable = $this->prophesize(SmartResolvable::class);
        $smartContentProvider = $this->prophesize(SmartContentProviderInterface::class);

        $data = [
            'value' => ['tags' => ['tag1'], 'presentAs' => 'grid'],
            'filters' => [
                'dataSource' => 'root',
                'includeSubFolders' => true,
                'categories' => [1, 2],
                'categoryOperator' => 'AND',
                'tagOperator' => 'OR',
                'types' => [],
                'typesOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'limit' => 5,
                'page' => 1,
            ],
            'sortBys' => ['title' => 'asc'],
            'parameters' => ['provider' => 'pages', 'categoryRoot' => null],
        ];

        $smartResolvable->getData()->willReturn($data);

        $this->serviceLocator->has('pages')->willReturn(true);
        $this->serviceLocator->get('pages')->willReturn($smartContentProvider->reveal());
        $this->serviceLocator->getProvidedServices()->willReturn(['pages' => true]);

        // Prepare expected filters - the resolver modifies them before passing to provider
        $expectedFilters = $data['filters'];
        unset($expectedFilters['page']);
        unset($expectedFilters['limit']);
        $expectedFilters['offset'] = 0; // page 1 with offset 0
        $expectedFilters['limit'] = 5; // from original limit

        $countByFilters = $expectedFilters;
        unset($countByFilters['offset']);

        $smartContentProvider->findFlatBy($expectedFilters, $data['sortBys'], ['value' => $data['value'], ...$data['parameters']])
            ->willReturn([['id' => 1], ['id' => 2]]);
        $smartContentProvider->countBy($countByFilters, ['value' => $data['value'], ...$data['parameters']])
            ->willReturn(10);
        $smartContentProvider->getResourceLoaderKey()->willReturn('pages');

        $result = $this->smartResolver->resolve($smartResolvable->reveal(), 'en');

        /** @var array<int, ResolvableResource> $content */
        $content = $result->getContent();
        $this->assertCount(2, $content);
        $this->assertSame(1, $content[0]->getId());
        $this->assertSame(2, $content[1]->getId());
        $this->assertSame('pages', $content[0]->getResourceLoaderKey());

        $view = $result->getView();
        $this->assertArrayHasKey('total', $view);
        $this->assertSame(5, $view['total']); // min(limit (5), fullTotal (10)) = 5
    }

    public function testResolveWithInvalidProvider(): void
    {
        $smartResolvable = $this->prophesize(SmartResolvable::class);

        $data = [
            'value' => [],
            'filters' => ['page' => 1],
            'sortBys' => [],
            'parameters' => ['provider' => 123], // Invalid provider type
        ];

        $smartResolvable->getData()->willReturn($data);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "provider" must be a string, integer given.');

        $this->smartResolver->resolve($smartResolvable->reveal(), 'en');
    }

    public function testResolveWithNonExistentProvider(): void
    {
        $smartResolvable = $this->prophesize(SmartResolvable::class);

        $data = [
            'value' => [],
            'filters' => ['page' => 1],
            'sortBys' => [],
            'parameters' => ['provider' => 'nonexistent'],
        ];

        $smartResolvable->getData()->willReturn($data);

        $this->serviceLocator->has('nonexistent')->willReturn(false);
        $this->serviceLocator->getProvidedServices()->willReturn(['pages' => true, 'articles' => true]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No smart content provider found for key "nonexistent". Existing keys: pages, articles');

        $this->smartResolver->resolve($smartResolvable->reveal(), 'en');
    }

    public function testResolveWithLimitCalculation(): void
    {
        $smartResolvable = $this->prophesize(SmartResolvable::class);
        $smartContentProvider = $this->prophesize(SmartContentProviderInterface::class);

        $data = [
            'value' => [],
            'filters' => [
                'limit' => 3,
                'page' => 1,
                'dataSource' => 'root',
                'includeSubFolders' => true,
                'categories' => [],
                'categoryOperator' => 'OR',
                'tagOperator' => 'OR',
                'types' => [],
                'typesOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
            ],
            'sortBys' => [],
            'parameters' => ['provider' => 'pages'],
        ];

        $smartResolvable->getData()->willReturn($data);

        $this->serviceLocator->has('pages')->willReturn(true);
        $this->serviceLocator->get('pages')->willReturn($smartContentProvider->reveal());

        // Prepare expected filters
        $expectedFilters = $data['filters'];
        unset($expectedFilters['page']);
        unset($expectedFilters['limit']);
        $expectedFilters['offset'] = 0;
        $expectedFilters['limit'] = 3; // from original limit

        $countByFilters = $expectedFilters;
        unset($countByFilters['offset']);

        // Return exactly the limit amount to test the count calculation
        $smartContentProvider->findFlatBy($expectedFilters, $data['sortBys'], ['value' => $data['value'], ...$data['parameters']])
            ->willReturn([['id' => 1], ['id' => 2], ['id' => 3]]); // Exactly the limit

        // countBy is always called in the current implementation
        $smartContentProvider->countBy($countByFilters, ['value' => $data['value'], ...$data['parameters']])
            ->willReturn(3);
        $smartContentProvider->getResourceLoaderKey()->willReturn('pages');

        $result = $this->smartResolver->resolve($smartResolvable->reveal(), 'en');

        /** @var array<int, ResolvableResource> $content */
        $content = $result->getContent();
        $this->assertCount(3, $content);
        $this->assertSame(1, $content[0]->getId());
        $this->assertSame(2, $content[1]->getId());
        $this->assertSame(3, $content[2]->getId());

        $view = $result->getView();
        $this->assertArrayHasKey('total', $view);
        $this->assertSame(3, $view['total']); // Since result count equals limit, total equals count
    }
}

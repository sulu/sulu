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
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
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
    /** @var ObjectProphecy<TargetGroupStoreInterface> */
    private ObjectProphecy $targetGroupStore;

    protected function setUp(): void
    {
        /** @var ObjectProphecy<ServiceLocator<SmartContentProviderInterface>> $serviceLocator */
        $serviceLocator = $this->prophesize(ServiceLocator::class);
        $this->serviceLocator = $serviceLocator;
        $this->targetGroupStore = $this->prophesize(TargetGroupStoreInterface::class);

        $this->smartResolver = new SmartContentSmartResolver(
            $this->serviceLocator->reveal(),
            $this->targetGroupStore->reveal(),
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

        $smartContentProvider->findFlatBy($data['filters'], $data['sortBys'], ['value' => $data['value'], ...$data['parameters']])
            ->willReturn([['id' => 1], ['id' => 2]]);
        $smartContentProvider->countBy($data['filters'], ['value' => $data['value'], ...$data['parameters']])
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
        $this->assertSame(2, $view['total']); // Since result count (2) <= limit (5), total equals result count
    }

    public function testResolveWithAudienceTargeting(): void
    {
        $smartResolvable = $this->prophesize(SmartResolvable::class);
        $smartContentProvider = $this->prophesize(SmartContentProviderInterface::class);

        $data = [
            'value' => ['tags' => ['tag1']],
            'filters' => [
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
                'audienceTargeting' => true, // Enable audience targeting
                'limit' => null,
                'page' => 1,
            ],
            'sortBys' => [],
            'parameters' => ['provider' => 'pages'],
        ];

        $smartResolvable->getData()->willReturn($data);

        // Mock target group store behavior
        $this->targetGroupStore->getTargetGroupId(true)->willReturn('123');

        $expectedFilters = $data['filters'];
        $expectedFilters['targetGroupId'] = '123'; // Should be added by audience targeting logic

        $this->serviceLocator->has('pages')->willReturn(true);
        $this->serviceLocator->get('pages')->willReturn($smartContentProvider->reveal());
        $this->serviceLocator->getProvidedServices()->willReturn(['pages' => true]);

        $smartContentProvider->findFlatBy($expectedFilters, $data['sortBys'], ['value' => $data['value'], ...$data['parameters']])
            ->willReturn([['id' => 1]]);
        $smartContentProvider->countBy($expectedFilters, ['value' => $data['value'], ...$data['parameters']])
            ->willReturn(1);
        $smartContentProvider->getResourceLoaderKey()->willReturn('pages');

        $result = $this->smartResolver->resolve($smartResolvable->reveal(), 'en');

        /** @var array<int, ResolvableResource> $content */
        $content = $result->getContent();
        $this->assertCount(1, $content);
        $this->assertSame(1, $content[0]->getId());
        $this->assertSame('pages', $content[0]->getResourceLoaderKey());

        $view = $result->getView();
        $this->assertArrayHasKey('total', $view);
        $this->assertSame(1, $view['total']);
    }

    public function testResolveWithAudienceTargetingNoTargetGroup(): void
    {
        $smartResolvable = $this->prophesize(SmartResolvable::class);
        $smartContentProvider = $this->prophesize(SmartContentProviderInterface::class);

        $data = [
            'value' => ['tags' => ['tag1']],
            'filters' => [
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
                'audienceTargeting' => true,
                'limit' => null,
                'page' => 1,
            ],
            'sortBys' => [],
            'parameters' => ['provider' => 'pages'],
        ];

        $smartResolvable->getData()->willReturn($data);

        // No target group available
        $this->targetGroupStore->getTargetGroupId(true)->willReturn(null);

        // Filters should remain unchanged
        $expectedFilters = $data['filters'];

        $this->serviceLocator->has('pages')->willReturn(true);
        $this->serviceLocator->get('pages')->willReturn($smartContentProvider->reveal());

        $smartContentProvider->findFlatBy($expectedFilters, $data['sortBys'], ['value' => $data['value'], ...$data['parameters']])
            ->willReturn([['id' => 1]]);
        $smartContentProvider->countBy($expectedFilters, ['value' => $data['value'], ...$data['parameters']])
            ->willReturn(1);
        $smartContentProvider->getResourceLoaderKey()->willReturn('pages');

        $result = $this->smartResolver->resolve($smartResolvable->reveal(), 'en');

        /** @var array<int, ResolvableResource> $content */
        $content = $result->getContent();
        $this->assertCount(1, $content);
        $this->assertSame(1, $content[0]->getId());
        $this->assertSame('pages', $content[0]->getResourceLoaderKey());

        $view = $result->getView();
        $this->assertArrayHasKey('total', $view);
        $this->assertSame(1, $view['total']);
    }

    public function testResolveWithAudienceTargetingExcludedTargetGroup(): void
    {
        $smartResolvable = $this->prophesize(SmartResolvable::class);
        $smartContentProvider = $this->prophesize(SmartContentProviderInterface::class);

        $data = [
            'value' => [],
            'filters' => [
                'audienceTargeting' => true,
                'limit' => null,
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

        // Target group is -1 (excluded)
        $this->targetGroupStore->getTargetGroupId(true)->willReturn('-1');

        // Filters should remain unchanged (no targetGroupId should be added)
        $expectedFilters = $data['filters'];

        $this->serviceLocator->has('pages')->willReturn(true);
        $this->serviceLocator->get('pages')->willReturn($smartContentProvider->reveal());

        $smartContentProvider->findFlatBy($expectedFilters, $data['sortBys'], ['value' => $data['value'], ...$data['parameters']])
            ->willReturn([]);
        $smartContentProvider->countBy($expectedFilters, ['value' => $data['value'], ...$data['parameters']])
            ->willReturn(0);
        $smartContentProvider->getResourceLoaderKey()->willReturn('pages');

        $result = $this->smartResolver->resolve($smartResolvable->reveal(), 'en');

        /** @var array<int, ResolvableResource> $content */
        $content = $result->getContent();
        $this->assertCount(0, $content); // This test expects no results

        $view = $result->getView();
        $this->assertArrayHasKey('total', $view);
        $this->assertSame(0, $view['total']);
    }

    public function testResolveWithoutTargetGroupStore(): void
    {
        $resolver = new SmartContentSmartResolver($this->serviceLocator->reveal(), null);

        $smartResolvable = $this->prophesize(SmartResolvable::class);
        $smartContentProvider = $this->prophesize(SmartContentProviderInterface::class);

        $data = [
            'value' => [],
            'filters' => [
                'audienceTargeting' => true,
                'limit' => null,
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

        // No target group store available, so audience targeting should be ignored
        $expectedFilters = $data['filters'];

        $this->serviceLocator->has('pages')->willReturn(true);
        $this->serviceLocator->get('pages')->willReturn($smartContentProvider->reveal());

        $smartContentProvider->findFlatBy($expectedFilters, $data['sortBys'], ['value' => $data['value'], ...$data['parameters']])
            ->willReturn([]);
        $smartContentProvider->countBy($expectedFilters, ['value' => $data['value'], ...$data['parameters']])
            ->willReturn(0);
        $smartContentProvider->getResourceLoaderKey()->willReturn('pages');

        $result = $resolver->resolve($smartResolvable->reveal(), 'en');

        /** @var array<int, ResolvableResource> $content */
        $content = $result->getContent();
        $this->assertCount(0, $content); // This test expects no results

        $view = $result->getView();
        $this->assertArrayHasKey('total', $view);
        $this->assertSame(0, $view['total']);
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

        // Return exactly the limit amount to test the count calculation
        $smartContentProvider->findFlatBy($data['filters'], $data['sortBys'], ['value' => $data['value'], ...$data['parameters']])
            ->willReturn([['id' => 1], ['id' => 2], ['id' => 3]]); // Exactly the limit

        // Should not call countBy since result count equals limit
        $smartContentProvider->countBy()->shouldNotBeCalled();
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

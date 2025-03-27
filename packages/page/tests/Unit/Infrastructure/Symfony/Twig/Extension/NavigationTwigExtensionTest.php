<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\Page\Tests\Unit\Infrastructure\Symfony\Twig\Extension;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Symfony\Twig\Extension\NavigationTwigExtension;
use Twig\TwigFunction;

class NavigationTwigExtensionTest extends TestCase
{
    use SetGetPrivatePropertyTrait;
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PageRepositoryInterface>
     */
    private ObjectProphecy $pageRepository;

    /**
     * @var ObjectProphecy<ContentAggregatorInterface>
     */
    private ObjectProphecy $contentAggregator;

    /**
     * @var ObjectProphecy<ContentResolverInterface>
     */
    private ObjectProphecy $contentResolver;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private ObjectProphecy $requestAnalyzer;

    private NavigationTwigExtension $extension;

    protected function setUp(): void
    {
        $this->pageRepository = $this->prophesize(PageRepositoryInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->contentResolver = $this->prophesize(ContentResolverInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $webspace = new Webspace();
        $webspace->setKey('test-webspace');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $localization = new Localization('en');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $this->extension = new NavigationTwigExtension(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->contentResolver->reveal(),
            $this->requestAnalyzer->reveal()
        );
    }

    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(2, $functions);
        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $functions);

        $functionNames = \array_map(function(TwigFunction $function) {
            return $function->getName();
        }, $functions);

        $this->assertContains('sulu_navigation_root_flat', $functionNames);
        $this->assertContains('sulu_navigation_root_tree', $functionNames);
    }

    public function testFlatRootNavigationFunctionWithEmptyPages(): void
    {
        $this->pageRepository->findBy([
            'navigationContexts' => ['main'],
            'depth' => 1,
            'webspaceKey' => 'test-webspace',
        ])->willReturn([]);

        $result = $this->extension->flatRootNavigationFunction('main');

        $this->assertSame([], $result);
    }

    public function testFlatRootNavigationFunction(): void
    {
        $page1 = new Page();
        $dimensionContent1 = new PageDimensionContent($page1);
        $page2 = new Page();
        $dimensionContent2 = new PageDimensionContent($page2);

        $this->pageRepository->findBy([
            'navigationContexts' => ['main'],
            'depth' => 1,
            'webspaceKey' => 'test-webspace',
        ])->willReturn([$page1, $page2]);

        $content1 = [
            'resource' => $page1,
            'content' => ['title' => 'Page 1', 'url' => '/page-1'],
            'view' => [],
            'extension' => ['excerpt' => ['title' => 'Excerpt 1']],
        ];

        $content2 = [
            'resource' => $page2,
            'content' => ['title' => 'Page 2', 'url' => '/page-2'],
            'view' => [],
            'extension' => ['excerpt' => ['title' => 'Excerpt 2']],
        ];

        $this->contentAggregator->aggregate($page1, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->willReturn($dimensionContent1);

        $this->contentResolver->resolve($dimensionContent1)->willReturn($content1);

        $this->contentAggregator->aggregate($page2, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->willReturn($dimensionContent2);

        $this->contentResolver->resolve($dimensionContent2)->willReturn($content2);

        $result = $this->extension->flatRootNavigationFunction('main');

        $this->assertCount(2, $result);
        $this->assertEquals('Page 1', $result[0]['title']);
        $this->assertEquals('/page-1', $result[0]['url']);
        $this->assertEquals('Page 2', $result[1]['title']);
        $this->assertEquals('/page-2', $result[1]['url']);
    }

    public function testFlatRootNavigationFunctionWithExcerpt(): void
    {
        $page = new Page();
        $dimensionContent = new PageDimensionContent($page);

        $this->pageRepository->findBy([
            'navigationContexts' => ['main'],
            'depth' => 1,
            'webspaceKey' => 'test-webspace',
        ])->willReturn([$page]);

        $content = [
            'resource' => $page,
            'content' => ['title' => 'Page 1', 'url' => '/page-1'],
            'view' => [],
            'extension' => ['excerpt' => ['title' => 'Excerpt 1']],
        ];

        $this->contentAggregator->aggregate($page, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->willReturn($dimensionContent);

        $this->contentResolver->resolve($dimensionContent)->willReturn($content);

        /**
         * @var array{
         *     title: string,
         *     url: string,
         *     excerpt?: array{
         *        title: string,
         *     }
         * }[] $result
         */
        $result = $this->extension->flatRootNavigationFunction('main', 1, true);

        $this->assertCount(1, $result);
        $this->assertEquals('Page 1', $result[0]['title']);
        $this->assertArrayHasKey('excerpt', $result[0]);
        $this->assertEquals('Excerpt 1', $result[0]['excerpt']['title']);
    }

    public function testTreeRootNavigationFunctionWithEmptyPages(): void
    {
        $this->pageRepository->findByAsTree([
            'navigationContexts' => ['main'],
            'depth' => 2,
            'webspaceKey' => 'test-webspace',
        ])->willReturn([]);

        $result = $this->extension->treeRootNavigationFunction('main', 2);

        $this->assertSame([], $result);
    }

    public function testTreeRootNavigationFunction(): void
    {
        $page1 = new Page();
        $this->setPrivateProperty($page1, 'uuid', 'page-1-id');
        $page1->setLft(1);

        $page2 = new Page();
        $this->setPrivateProperty($page2, 'uuid', 'page-2-id');
        $page2->setLft(2);
        $page2->setParent($page1);

        $dimensionContent1 = new PageDimensionContent($page1);
        $dimensionContent2 = new PageDimensionContent($page2);

        $treeStructure = [$page1];
        $page1->addChild($page2);

        $this->pageRepository->findByAsTree([
            'navigationContexts' => ['main'],
            'depth' => 2,
            'webspaceKey' => 'test-webspace',
        ])->willReturn($treeStructure);

        $content1 = [
            'resource' => $page1,
            'content' => ['title' => 'Page 1', 'url' => '/page-1'],
            'view' => [],
            'extension' => ['excerpt' => ['title' => 'Excerpt 1']],
        ];

        $content2 = [
            'resource' => $page2,
            'content' => ['title' => 'Page 2', 'url' => '/page-2'],
            'view' => [],
            'extension' => ['excerpt' => ['title' => 'Excerpt 2']],
        ];

        $this->contentAggregator->aggregate($page1, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->willReturn($dimensionContent1);

        $this->contentResolver->resolve($dimensionContent1)->willReturn($content1);

        $this->contentAggregator->aggregate($page2, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->willReturn($dimensionContent2);

        $this->contentResolver->resolve($dimensionContent2)->willReturn($content2);

        /**
         * @var array{
         *     title: string,
         *     url: string,
         *     excerpt?: array{
         *        title: string,
         *     },
         *     children: array{
         *       title: string,
         *       url: string,
         *       excerpt?: array{
         *           title: string,
         *       }
         *   }[]
         * }[] $result
         */
        $result = $this->extension->treeRootNavigationFunction('main', 2);

        $this->assertCount(1, $result);
        $this->assertEquals('Page 1', $result[0]['title']);
        $this->assertEquals('/page-1', $result[0]['url']);
        $this->assertCount(1, $result[0]['children']);
        $this->assertEquals('Page 2', $result[0]['children'][0]['title']);
        $this->assertEquals('/page-2', $result[0]['children'][0]['url']);
    }

    public function testTreeRootNavigationFunctionWithComplexTree(): void
    {
        $rootPage = new Page();
        $this->setPrivateProperty($rootPage, 'uuid', 'root-id');
        $rootPage->setLft(1);

        $child1 = new Page();
        $this->setPrivateProperty($child1, 'uuid', 'child1-id');
        $child1->setLft(2);
        $child1->setParent($rootPage);

        $child2 = new Page();
        $this->setPrivateProperty($child2, 'uuid', 'child2-id');
        $child2->setLft(3);
        $child2->setParent($rootPage);

        $grandchild = new Page();
        $this->setPrivateProperty($grandchild, 'uuid', 'grandchild-id');
        $grandchild->setLft(5);
        $grandchild->setParent($child1);

        $dimensionRoot = new PageDimensionContent($rootPage);
        $dimensionChild1 = new PageDimensionContent($child1);
        $dimensionChild2 = new PageDimensionContent($child2);
        $dimensionGrandchild = new PageDimensionContent($grandchild);

        $child1->addChild($grandchild);
        $rootPage->addChild($child1);
        $rootPage->addChild($child2);
        $treeStructure = [$rootPage];
        $this->pageRepository->findByAsTree([
            'navigationContexts' => ['main'],
            'depth' => 3,
            'webspaceKey' => 'test-webspace',
        ])->willReturn($treeStructure);

        $contents = [
            'root' => [
                'resource' => $rootPage,
                'content' => ['title' => 'Root', 'url' => '/'],
                'view' => [],
                'extension' => ['excerpt' => []],
            ],
            'child1' => [
                'resource' => $child1,
                'content' => ['title' => 'Child 1', 'url' => '/child-1'],
                'view' => [],
                'extension' => ['excerpt' => []],
            ],
            'child2' => [
                'resource' => $child2,
                'content' => ['title' => 'Child 2', 'url' => '/child-2'],
                'view' => [],
                'extension' => ['excerpt' => []],
            ],
            'grandchild' => [
                'resource' => $grandchild,
                'content' => ['title' => 'Grandchild', 'url' => '/child-1/grandchild'],
                'view' => [],
                'extension' => ['excerpt' => []],
            ],
        ];

        $this->contentAggregator->aggregate($rootPage, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->willReturn($dimensionRoot);
        $this->contentResolver->resolve($dimensionRoot)->willReturn($contents['root']);

        $this->contentAggregator->aggregate($child1, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->willReturn($dimensionChild1);
        $this->contentResolver->resolve($dimensionChild1)->willReturn($contents['child1']);

        $this->contentAggregator->aggregate($child2, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->willReturn($dimensionChild2);
        $this->contentResolver->resolve($dimensionChild2)->willReturn($contents['child2']);

        $this->contentAggregator->aggregate($grandchild, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->willReturn($dimensionGrandchild);
        $this->contentResolver->resolve($dimensionGrandchild)->willReturn($contents['grandchild']);

        /**
         * @var array{
         *     title: string,
         *     url: string,
         *     excerpt?: array{
         *        title: string,
         *     },
         *     children: array{
         *       title: string,
         *       url: string,
         *       excerpt?: array{
         *           title: string,
         *       },
         *       children: array{
         *           title: string,
         *      }[]
         *   }[]
         * }[] $result
         */
        $result = $this->extension->treeRootNavigationFunction('main', 3);

        $this->assertCount(1, $result);
        $this->assertEquals('Root', $result[0]['title']);
        $this->assertCount(2, $result[0]['children']);
        $this->assertEquals('Child 1', $result[0]['children'][0]['title']);
        $this->assertEquals('Child 2', $result[0]['children'][1]['title']);
        $this->assertEquals('Grandchild', $result[0]['children'][0]['children'][0]['title']);
    }

    public function testTreeRootNavigationFunctionWithExcerpt(): void
    {
        $page = new Page();
        $this->setPrivateProperty($page, 'uuid', 'page-id');
        $page->setLft(1);

        $dimensionContent = new PageDimensionContent($page);

        $this->pageRepository->findByAsTree([
            'navigationContexts' => ['main'],
            'depth' => 1,
            'webspaceKey' => 'test-webspace',
        ])->willReturn([$page]);

        $content = [
            'resource' => $page,
            'content' => ['title' => 'Page', 'url' => '/page'],
            'view' => [],
            'extension' => ['excerpt' => ['title' => 'Excerpt Title', 'description' => 'Test description']],
        ];

        $this->contentAggregator->aggregate($page, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->willReturn($dimensionContent);

        $this->contentResolver->resolve($dimensionContent)->willReturn($content);

        /**
         * @var array{
         *     title: string,
         *     url: string,
         *     excerpt?: array{
         *        title: string,
         *     },
         *     children: array{
         *       title: string,
         *       url: string,
         *       excerpt?: array{
         *           title: string,
         *       }
         *   }[]
         * }[] $result
         */
        $result = $this->extension->treeRootNavigationFunction('main', 1, true);

        $this->assertCount(1, $result);
        $this->assertEquals('Page', $result[0]['title']);
        $this->assertArrayHasKey('excerpt', $result[0]);
        $this->assertEquals('Excerpt Title', $result[0]['excerpt']['title']);
    }
}

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

namespace Sulu\Page\Tests\Unit\Infrastructure\Symfony\Twig\Extension;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Sulu\Page\Infrastructure\Symfony\Twig\Extension\NavigationTwigExtension;

class NavigationTwigExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return array<string, string>
     */
    private function getDefaultProperties(): array
    {
        return [
            'title' => 'title',
            'url' => 'url',
            'linkProvider' => 'object.linkData[provider]',
        ];
    }

    /**
     * @return array<array{bool, string, string}>
     */
    public static function activeElementProvider(): array
    {
        return [
            [false, '/', '/news/item'],
            [true, '/news/item', '/news/item'],
            [true, '/news/item', '/news'],
            [false, '/news/item', '/'],
            [false, '/news/item-1', '/news/item'],
            [false, '/news', '/news/item'],
            [false, '/news', '/product/item'],
            [false, '/news', '/news-1'],
            [false, '/news', '/news-1/item'],
            [true, '/', '/'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('activeElementProvider')]
    public function testNavigationIsActiveFunction(bool $expected, string $requestPath, string $itemPath): void
    {
        $extension = new NavigationTwigExtension(
            $this->prophesize(NavigationRepositoryInterface::class)->reveal(),
            $this->prophesize(RequestAnalyzerInterface::class)->reveal()
        );

        $this->assertEquals($expected, $extension->navigationIsActiveFunction($requestPath, $itemPath));
    }

    public function testFlatRootNavigationFunction(): void
    {
        $navigationRepository = $this->prophesize(NavigationRepositoryInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $navigationRepository->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu-io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $localization = new Localization('en');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);
        $requestAnalyzer->getSegment()->willReturn(null);

        $navigationRepository->getNavigationFlat('main', 'en', 'sulu-io', null, 1, $this->getDefaultProperties())
            ->willReturn([['title' => 'Page 1']])
            ->shouldBeCalled();

        $result = $extension->flatRootNavigationFunction('main', 1);

        $this->assertEquals([['title' => 'Page 1']], $result);
    }

    public function testTreeRootNavigationFunction(): void
    {
        $navigationRepository = $this->prophesize(NavigationRepositoryInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $navigationRepository->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu-io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $localization = new Localization('en');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);
        $requestAnalyzer->getSegment()->willReturn(null);

        $navigationRepository->getNavigationTree('main', 'en', 'sulu-io', null, 2, $this->getDefaultProperties())
            ->willReturn([['title' => 'Page 1', 'children' => []]])
            ->shouldBeCalled();

        $result = $extension->treeRootNavigationFunction('main', 2);

        $this->assertEquals([['title' => 'Page 1', 'children' => []]], $result);
    }

    public function testFlatNavigationFunction(): void
    {
        $navigationRepository = $this->prophesize(NavigationRepositoryInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $navigationRepository->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu-io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $localization = new Localization('en');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationRepository->getNavigationFlatByUuid('parent-uuid', 'en', 'sulu-io', 2, 'main', $this->getDefaultProperties())
            ->willReturn([['title' => 'Child 1'], ['title' => 'Child 2']])
            ->shouldBeCalled();

        $result = $extension->flatNavigationFunction('parent-uuid', 'main', 2);

        $this->assertEquals([['title' => 'Child 1'], ['title' => 'Child 2']], $result);
    }

    public function testTreeNavigationFunction(): void
    {
        $navigationRepository = $this->prophesize(NavigationRepositoryInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $navigationRepository->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu-io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $localization = new Localization('en');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationRepository->getNavigationTreeByUuid('parent-uuid', 'en', 'sulu-io', 2, null, $this->getDefaultProperties())
            ->willReturn([['title' => 'Child 1', 'children' => [['title' => 'Grandchild 1']]]])
            ->shouldBeCalled();

        $result = $extension->treeNavigationFunction('parent-uuid', null, 2);

        $this->assertEquals([['title' => 'Child 1', 'children' => [['title' => 'Grandchild 1']]]], $result);
    }

    public function testBreadcrumbFunction(): void
    {
        $navigationRepository = $this->prophesize(NavigationRepositoryInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $navigationRepository->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu-io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $localization = new Localization('en');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationRepository->getBreadcrumb('child-uuid', 'en', 'sulu-io', $this->getDefaultProperties())
            ->willReturn([['uuid' => 'parent-uuid', 'title' => 'Parent'], ['uuid' => 'child-uuid', 'title' => 'Child']])
            ->shouldBeCalled();

        $result = $extension->breadcrumbFunction('child-uuid');

        $this->assertEquals([['uuid' => 'parent-uuid', 'title' => 'Parent'], ['uuid' => 'child-uuid', 'title' => 'Child']], $result);
    }

    public function testFlatNavigationFunctionWithLevel(): void
    {
        $navigationRepository = $this->prophesize(NavigationRepositoryInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $navigationRepository->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu-io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $localization = new Localization('en');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $breadcrumb = [
            ['uuid' => 'grandparent-uuid', 'title' => 'Grandparent'],
            ['uuid' => 'parent-uuid', 'title' => 'Parent'],
            ['uuid' => 'child-uuid', 'title' => 'Child'],
        ];

        $navigationRepository->getBreadcrumb('child-uuid', 'en', 'sulu-io', $this->getDefaultProperties())
            ->willReturn($breadcrumb)
            ->shouldBeCalled();

        $navigationRepository->getNavigationFlatByUuid('grandparent-uuid', 'en', 'sulu-io', 1, null, $this->getDefaultProperties())
            ->willReturn([['uuid' => 'parent-uuid', 'title' => 'Parent'], ['uuid' => 'uncle-uuid', 'title' => 'Uncle']])
            ->shouldBeCalled();

        $result = $extension->flatNavigationFunction('child-uuid', null, 1, 0);

        $this->assertEquals([['uuid' => 'parent-uuid', 'title' => 'Parent'], ['uuid' => 'uncle-uuid', 'title' => 'Uncle']], $result);
    }

    public function testFlatNavigationFunctionWithInvalidLevel(): void
    {
        $navigationRepository = $this->prophesize(NavigationRepositoryInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $navigationRepository->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu-io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $localization = new Localization('en');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $breadcrumb = [
            ['uuid' => 'parent-uuid', 'title' => 'Parent'],
        ];

        $navigationRepository->getBreadcrumb('child-uuid', 'en', 'sulu-io', $this->getDefaultProperties())
            ->willReturn($breadcrumb)
            ->shouldBeCalled();

        $result = $extension->flatNavigationFunction('child-uuid', null, 1, 5);

        $this->assertEquals([], $result);
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapperInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Navigation\NavigationTwigExtension;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class NavigationTwigExtensionTest extends TestCase
{
    public function activeElementProvider()
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

    /**
     * @dataProvider activeElementProvider
     */
    public function testActiveElement($expected, $requestPath, $itemPath)
    {
        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $this->prophesize(NavigationMapperInterface::class)->reveal()
        );

        $this->assertEquals($expected, $extension->navigationIsActiveFunction($requestPath, $itemPath));
    }

    public function testBreadcrumbFunctionDocumentNotFound()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getBreadcrumb('123-123-123', 'sulu_io', 'de')->willThrow(new DocumentNotFoundException());

        $this->assertEquals([], $extension->breadcrumbFunction('123-123-123'));
    }

    public function testFlatNavigationFunctionDocumentNotFound()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $requestAnalyzer->getSegment()->willReturn($segment);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getNavigation('123-123-123', 'sulu_io', 'de', 1, true, null, false, 's', null)
            ->willThrow(new DocumentNotFoundException());

        $this->assertEquals([], $extension->flatNavigationFunction('123-123-123'));
    }

    public function testTreeNavigationFunctionDocumentNotFound()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('w');
        $requestAnalyzer->getSegment()->willReturn($segment);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getNavigation('123-123-123', 'sulu_io', 'de', 1, false, null, false, 'w', null)
            ->willThrow(new DocumentNotFoundException());

        $this->assertEquals([], $extension->treeNavigationFunction('123-123-123'));
    }

    public function testFlatRootNavigationWithUser()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);

        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(UserInterface::class);
        $token->getUser()->willReturn($user->reveal());
        $tokenStorage->getToken()->willReturn($token->reveal());

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal(),
            $tokenStorage->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $requestAnalyzer->getSegment()->willReturn(null);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getRootNavigation('sulu_io', 'de', 1, true, 'main', false, null, $user->reveal())
            ->shouldBeCalled();

        $extension->flatRootNavigationFunction('main', 1, false);
    }

    public function testFlatRootNavigationWithoutToken()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);

        $tokenStorage->getToken()->willReturn(null);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal(),
            $tokenStorage->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $requestAnalyzer->getSegment()->willReturn(null);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getRootNavigation('sulu_io', 'de', 1, true, 'main', false, null, null)
            ->shouldBeCalled();

        $extension->flatRootNavigationFunction('main', 1, false);
    }

    public function testFlatRootNavigationWithoutUser()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);

        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn(null);
        $tokenStorage->getToken()->willReturn($token->reveal());

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal(),
            $tokenStorage->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $requestAnalyzer->getSegment()->willReturn(null);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getRootNavigation('sulu_io', 'de', 1, true, 'main', false, null, null)
            ->shouldBeCalled();

        $extension->flatRootNavigationFunction('main', 1, false);
    }

    public function testTreeRootNavigationWithUser()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);

        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(UserInterface::class);
        $token->getUser()->willReturn($user->reveal());
        $tokenStorage->getToken()->willReturn($token->reveal());

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal(),
            $tokenStorage->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $requestAnalyzer->getSegment()->willReturn(null);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getRootNavigation('sulu_io', 'de', 1, false, 'main', false, null, $user->reveal())
            ->shouldBeCalled();

        $extension->treeRootNavigationFunction('main', 1, false);
    }

    public function testFlatNavigationWithUser()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);

        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(UserInterface::class);
        $token->getUser()->willReturn($user->reveal());
        $tokenStorage->getToken()->willReturn($token->reveal());

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal(),
            $tokenStorage->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $requestAnalyzer->getSegment()->willReturn(null);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getNavigation('main', 'sulu_io', 'de', false, true, 1, false, null, $user->reveal())
            ->shouldBeCalled();

        $extension->flatNavigationFunction('main', 1, false);
    }

    public function testTreeNavigationWithUser()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);

        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(UserInterface::class);
        $token->getUser()->willReturn($user->reveal());
        $tokenStorage->getToken()->willReturn($token->reveal());

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal(),
            $tokenStorage->reveal()
        );

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $requestAnalyzer->getSegment()->willReturn(null);

        $localization = new Localization('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getNavigation('main', 'sulu_io', 'de', false, false, 1, false, null, $user->reveal())
            ->shouldBeCalled();

        $extension->treeNavigationFunction('main', 1, false);
    }
}

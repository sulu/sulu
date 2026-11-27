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

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Controller\AdminResourceViewUrlRedirectController;
use Sulu\Bundle\AdminBundle\Exception\ResourceViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdminResourceViewUrlRedirectControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private ObjectProphecy $webspaceManager;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private ObjectProphecy $routeRepository;

    /**
     * @var ObjectProphecy<ResourceViewUrlGeneratorInterface>
     */
    private ObjectProphecy $resourceViewUrlGenerator;

    private AdminResourceViewUrlRedirectController $controller;

    protected function setUp(): void
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->resourceViewUrlGenerator = $this->prophesize(ResourceViewUrlGeneratorInterface::class);

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('sulu_admin')->willReturn('/admin/');

        $this->controller = new AdminResourceViewUrlRedirectController(
            $this->webspaceManager->reveal(),
            $this->routeRepository->reveal(),
            $this->resourceViewUrlGenerator->reveal(),
            $urlGenerator->reveal(),
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideUrls(): iterable
    {
        yield 'page' => ['https://sulu.io/de/about', 'sulu.io/de/about', '/about'];
        yield 'homepage' => ['https://sulu.io/de', 'sulu.io/de', '/'];
        yield 'query and fragment' => ['https://sulu.io/de/about?page=2#top', 'sulu.io/de/about', '/about'];
        yield 'encoded' => ['https://sulu.io/de/%C3%BCber-uns', 'sulu.io/de/%C3%BCber-uns', '/über-uns'];
        yield 'port' => ['http://sulu.io:8000/de/about', 'sulu.io/de/about', '/about'];
    }

    #[DataProvider('provideUrls')]
    public function testRedirectsToDetailView(string $url, string $portalUrl, string $slug): void
    {
        $this->webspaceManager->findPortalInformationByUrl($portalUrl)
            ->willReturn($this->createPortalInformation('sulu.io/de', new Localization('de')));
        $this->routeRepository->findFirstBy(
            ['webspaceOrNull' => 'sulu_io', 'locale' => 'de', 'slug' => $slug],
            ['webspace' => 'desc'],
        )->willReturn(new Route('pages', '123-abc', 'de', $slug, 'sulu_io'));
        $this->resourceViewUrlGenerator->generate(
            'pages',
            'detail',
            ['id' => '123-abc', 'locale' => 'de', 'webspace' => 'sulu_io'],
        )->willReturn('/admin/#/webspaces/sulu_io/pages/de/123-abc/details');

        $response = $this->controller->redirectAction($this->createRequest($url));

        $this->assertSame('/admin/#/webspaces/sulu_io/pages/de/123-abc/details', $response->getTargetUrl());
    }

    public function testUsesDefaultLocalizationOfPortal(): void
    {
        $this->webspaceManager->findPortalInformationByUrl('sulu.io/blog/news')
            ->willReturn($this->createPortalInformation('sulu.io', null));
        $this->routeRepository->findFirstBy(
            ['webspaceOrNull' => 'sulu_io', 'locale' => 'en', 'slug' => '/blog/news'],
            ['webspace' => 'desc'],
        )->willReturn(new Route('articles', '456', 'en', '/blog/news'));
        $this->resourceViewUrlGenerator->generate('articles', 'detail', ['id' => '456', 'locale' => 'en'])
            ->willReturn('/admin/#/articles/en/456/details');

        $response = $this->controller->redirectAction($this->createRequest('https://sulu.io/blog/news'));

        $this->assertSame('/admin/#/articles/en/456/details', $response->getTargetUrl());
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideQueriesWithoutHost(): iterable
    {
        yield 'no url' => [[]];
        yield 'relative url' => [['url' => '/de/about']];
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('provideQueriesWithoutHost')]
    public function testRedirectsToStartPageWithoutHost(array $query): void
    {
        $this->webspaceManager->findPortalInformationByUrl(Argument::cetera())->shouldNotBeCalled();

        $response = $this->controller->redirectAction(Request::create('/admin/resource-view-url-redirect', 'GET', $query));

        $this->assertSame('/admin/', $response->getTargetUrl());
    }

    public function testRedirectsToStartPageForUnknownUrl(): void
    {
        $this->webspaceManager->findPortalInformationByUrl('unknown.example/about')->willReturn(null);
        $this->routeRepository->findFirstBy(Argument::cetera())->shouldNotBeCalled();

        $response = $this->controller->redirectAction($this->createRequest('https://unknown.example/about'));

        $this->assertSame('/admin/', $response->getTargetUrl());
    }

    public function testRedirectsToStartPageForRedirectPortal(): void
    {
        $this->webspaceManager->findPortalInformationByUrl('sulu.io/')->willReturn(
            new PortalInformation(RequestAnalyzerInterface::MATCH_TYPE_REDIRECT, $this->createWebspace(), null, null, 'sulu.io', 'sulu.io/de'),
        );
        $this->routeRepository->findFirstBy(Argument::cetera())->shouldNotBeCalled();

        $response = $this->controller->redirectAction($this->createRequest('https://sulu.io/'));

        $this->assertSame('/admin/', $response->getTargetUrl());
    }

    public function testRedirectsToStartPageWithoutRoute(): void
    {
        $this->webspaceManager->findPortalInformationByUrl('sulu.io/de/unknown')
            ->willReturn($this->createPortalInformation('sulu.io/de', new Localization('de')));
        $this->routeRepository->findFirstBy(Argument::cetera())->willReturn(null);
        $this->resourceViewUrlGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $response = $this->controller->redirectAction($this->createRequest('https://sulu.io/de/unknown'));

        $this->assertSame('/admin/', $response->getTargetUrl());
    }

    /**
     * @return iterable<string, array{\Exception}>
     */
    public static function provideGeneratorExceptions(): iterable
    {
        yield 'unknown resource' => [new ResourceViewNotFoundException('unknown', 'detail')];
        yield 'no access to view' => [new ViewNotFoundException('sulu_page.page_edit_form')];
        yield 'missing view parameter' => [new ViewParameterNotFoundException('webspace', 'sulu_page.page_edit_form')];
    }

    #[DataProvider('provideGeneratorExceptions')]
    public function testRedirectsToStartPageWhenViewCannotBeGenerated(\Exception $exception): void
    {
        $this->webspaceManager->findPortalInformationByUrl('sulu.io/de/about')
            ->willReturn($this->createPortalInformation('sulu.io/de', new Localization('de')));
        $this->routeRepository->findFirstBy(Argument::cetera())
            ->willReturn(new Route('pages', '123', 'de', '/about', 'sulu_io'));
        $this->resourceViewUrlGenerator->generate('pages', 'detail', Argument::any())->willThrow($exception);

        $response = $this->controller->redirectAction($this->createRequest('https://sulu.io/de/about'));

        $this->assertSame('/admin/', $response->getTargetUrl());
    }

    private function createRequest(string $url): Request
    {
        return Request::create('/admin/resource-view-url-redirect', 'GET', ['url' => $url]);
    }

    private function createPortalInformation(string $url, ?Localization $localization): PortalInformation
    {
        $defaultLocalization = new Localization('en');
        $defaultLocalization->setDefault(true);
        $portal = new Portal();
        $portal->addLocalization($defaultLocalization);

        return new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $this->createWebspace(),
            $portal,
            $localization,
            $url,
        );
    }

    private function createWebspace(): Webspace
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');

        return $webspace;
    }
}

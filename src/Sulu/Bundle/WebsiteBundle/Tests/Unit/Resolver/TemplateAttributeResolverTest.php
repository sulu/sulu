<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Resolver\RequestAnalyzerResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\RequestAnalyzerResolverInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolver;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class TemplateAttributeResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    protected $requestAnalyzer;

    /**
     * @var RequestAnalyzerResolverInterface
     */
    protected $requestAnalyzerResolver;

    /**
     * @var ObjectProphecy<RouterInterface>
     */
    protected $router;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    protected $requestStack;

    /**
     * @var PortalInformation[]
     */
    protected $portalInformations;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    protected $webspaceManager;

    /**
     * @var ObjectProphecy<Webspace>
     */
    protected $webspace;

    /**
     * @var ObjectProphecy<Portal>
     */
    protected $portal;

    /**
     * @var ObjectProphecy<Request>
     */
    protected $request;

    /**
     * @var TemplateAttributeResolver
     */
    protected $templateAttributeResolver;

    /**
     * @var string
     */
    protected $environment = 'test';

    public function setUp(): void
    {
        $webspacePortalKey = 'sulu_io';
        $webspacePortalName = 'Sulu';

        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->router = $this->prophesize(RouterInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->request = $this->prophesize(Request::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->portal = $this->prophesize(Portal::class);

        $portalInformationEn = $this->prophesize(PortalInformation::class);
        $portalInformationEn->getLocale()->willReturn('en');
        $portalInformationEn->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformationEn->getPrefix()->willReturn('/en');
        $portalInformationEn->getPortalKey()->willReturn($webspacePortalKey);

        $portalInformationDe = $this->prophesize(PortalInformation::class);
        $portalInformationDe->getLocale()->willReturn('de');
        $portalInformationDe->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformationDe->getPrefix()->willReturn('/de');
        $portalInformationDe->getPortalKey()->willReturn($webspacePortalKey);

        $this->portalInformations = [
            $portalInformationEn->reveal(),
            $portalInformationDe->reveal(),
        ];

        $this->webspaceManager->getPortalInformations($this->environment)->willReturn($this->portalInformations);

        $this->requestStack->getCurrentRequest()->willReturn($this->request);

        $this->portal->getKey()->willReturn($webspacePortalKey);
        $this->portal->getName()->willReturn($webspacePortalName);
        $this->portal->getDefaultLocalization()->willReturn(Localization::createFromString('en'));
        $this->webspace->getKey()->willReturn($webspacePortalKey);
        $this->webspace->getName()->willReturn($webspacePortalName);

        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');
        $this->requestAnalyzer->getResourceLocator()->willReturn('/test');
        $this->requestAnalyzer->getPortalUrl()->willReturn('sulu.io/de');
        $this->requestAnalyzer->getPortal()->willReturn($this->portal->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getSegment()->willReturn();

        $this->request->get('_route')->willReturn('test');
        $this->request->get('_route_params')->willReturn(['host' => 'sulu.io', 'prefix' => '/de']);

        $this->router->generate('test', ['host' => 'sulu.io', 'prefix' => '/de'], UrlGeneratorInterface::ABSOLUTE_URL)->willReturn('http://sulu.io/de/test');
        $this->router->generate('test', ['host' => 'sulu.io', 'prefix' => '/en'], UrlGeneratorInterface::ABSOLUTE_URL)->willReturn('http://sulu.io/en/test');

        $this->requestAnalyzerResolver = new RequestAnalyzerResolver(
            $this->webspaceManager->reveal(),
            $this->environment
        );
    }

    public function testResolve(): void
    {
        $templateAttributeResolver = $this->createTemplateAttributeResolver();

        $resolved = $templateAttributeResolver->resolve(['custom' => 'test']);

        $this->assertEquals([
            'extension' => [
                'seo' => [],
                'excerpt' => [],
            ],
            'content' => [],
            'view' => [],
            'shadowBaseLocale' => null,
            'custom' => 'test',
            'urls' => [
                'en' => 'http://sulu.io/en/test',
                'de' => 'http://sulu.io/de/test',
            ],
            'localizations' => [
                'en' => [
                    'locale' => 'en',
                    'url' => 'http://sulu.io/en/test',
                    'alternate' => true,
                ],
                'de' => [
                    'locale' => 'de',
                    'url' => 'http://sulu.io/de/test',
                    'alternate' => true,
                ],
            ],
            'request' => [
                'webspaceKey' => 'sulu_io',
                'webspaceName' => 'Sulu',
                'defaultLocale' => 'en',
                'portalKey' => 'sulu_io',
                'portalName' => 'Sulu',
                'portalUrl' => 'sulu.io/de',
                'resourceLocatorPrefix' => '/de',
                'resourceLocator' => '/test',
                'segmentKey' => null,
            ],
        ], $resolved);
    }

    public function testResolveStaticRoute(): void
    {
        $templateAttributeResolver = $this->createTemplateAttributeResolver();

        $this->request->get('_route')->willReturn('test_static')->shouldBeCalled();
        $this->request->get('_route_params')->willReturn(['host' => 'sulu.io', '_locale' => 'de']);

        $this->router->generate('test_static', ['host' => 'sulu.io', '_locale' => 'de'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://sulu.io/de/test')->shouldBeCalledTimes(1);
        $this->router->generate('test_static', ['host' => 'sulu.io', '_locale' => 'en'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://sulu.io/en/test')->shouldBeCalledTimes(1);

        $resolved = $templateAttributeResolver->resolve(['custom' => 'test']);

        $this->assertEquals([
            'extension' => [
                'seo' => [],
                'excerpt' => [],
            ],
            'content' => [],
            'view' => [],
            'shadowBaseLocale' => null,
            'custom' => 'test',
            'urls' => [
                'en' => 'http://sulu.io/en/test',
                'de' => 'http://sulu.io/de/test',
            ],
            'localizations' => [
                'en' => [
                    'locale' => 'en',
                    'url' => 'http://sulu.io/en/test',
                    'alternate' => true,
                ],
                'de' => [
                    'locale' => 'de',
                    'url' => 'http://sulu.io/de/test',
                    'alternate' => true,
                ],
            ],
            'request' => [
                'webspaceKey' => 'sulu_io',
                'webspaceName' => 'Sulu',
                'defaultLocale' => 'en',
                'portalKey' => 'sulu_io',
                'portalName' => 'Sulu',
                'portalUrl' => 'sulu.io/de',
                'resourceLocatorPrefix' => '/de',
                'resourceLocator' => '/test',
                'segmentKey' => null,
            ],
        ], $resolved);
    }

    public function testResolveStaticRouteWithoutUrls(): void
    {
        $this->request->get('_route')->willReturn('test_static')->shouldBeCalled();
        $this->request->get('_route_params')->willReturn(['host' => 'sulu.io', '_locale' => 'de']);

        $this->router->generate('test_static', ['host' => 'sulu.io', '_locale' => 'de'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://sulu.io/de/test')->shouldBeCalledTimes(1);
        $this->router->generate('test_static', ['host' => 'sulu.io', '_locale' => 'en'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://sulu.io/en/test')->shouldBeCalledTimes(1);

        $templateAttributeResolver = $this->createTemplateAttributeResolver(['urls' => false]);
        $resolved = $templateAttributeResolver->resolve(['custom' => 'test']);

        $this->assertEquals([
            'extension' => [
                'seo' => [],
                'excerpt' => [],
            ],
            'content' => [],
            'view' => [],
            'shadowBaseLocale' => null,
            'custom' => 'test',
            'localizations' => [
                'en' => [
                    'locale' => 'en',
                    'url' => 'http://sulu.io/en/test',
                    'alternate' => true,
                ],
                'de' => [
                    'locale' => 'de',
                    'url' => 'http://sulu.io/de/test',
                    'alternate' => true,
                ],
            ],
            'request' => [
                'webspaceKey' => 'sulu_io',
                'webspaceName' => 'Sulu',
                'defaultLocale' => 'en',
                'portalKey' => 'sulu_io',
                'portalName' => 'Sulu',
                'portalUrl' => 'sulu.io/de',
                'resourceLocatorPrefix' => '/de',
                'resourceLocator' => '/test',
                'segmentKey' => null,
            ],
        ], $resolved);
    }

    private function createTemplateAttributeResolver(array $enabledTwigAttributes = ['urls' => true])
    {
        return new TemplateAttributeResolver(
            $this->requestAnalyzer->reveal(),
            $this->requestAnalyzerResolver,
            $this->webspaceManager->reveal(),
            $this->router->reveal(),
            $this->requestStack->reveal(),
            $this->environment,
            $enabledTwigAttributes
        );
    }
}

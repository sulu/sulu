<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Bundle\WebsiteBundle\Resolver\RequestAnalyzerResolverInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolver;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * Testing the TemplateAttributeResolver class.
 */
class TemplateAttributeResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestAnalyzerInterface
     */
    protected $requestAnalyzer;

    /**
     * @var RequestAnalyzerResolverInterface
     */
    protected $requestAnalyzerResolver;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var PortalInformation[]
     */
    protected $portalInformations;

    /**
     * @var WebspaceManagerInterface
     */
    protected $webspaceManager;

    /**
     * @var Portal
     */
    protected $portal;

    /**
     * @var Request
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

    public function setUp()
    {
        $webspacePortalKey = 'sulu_io';

        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->requestAnalyzerResolver = $this->prophesize(RequestAnalyzerResolverInterface::class);
        $this->router = $this->prophesize(RouterInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->request = $this->prophesize(Request::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
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
        $this->requestAnalyzer->getPortal()->willReturn($this->portal->reveal());

        $this->request->get('_route')->willReturn('test');
        $this->request->get('_route_params')->willReturn(['host' => 'sulu.io', 'prefix' => '/de']);

        $this->router->generate('test', ['host' => 'sulu.io', 'prefix' => '/de'], true)->willReturn('http://sulu.io/de/test');
        $this->router->generate('test', ['host' => 'sulu.io', 'prefix' => '/en'], true)->willReturn('http://sulu.io/en/test');

        $this->requestAnalyzerResolver->resolve($this->requestAnalyzer)->willReturn(
            [
                'request' => [
                    'webspaceKey' => $webspacePortalKey,
                    'locale' => 'en',
                ],
            ]
        );

        $this->templateAttributeResolver = new TemplateAttributeResolver(
            $this->requestAnalyzer->reveal(),
            $this->requestAnalyzerResolver->reveal(),
            $this->webspaceManager->reveal(),
            $this->router->reveal(),
            $this->requestStack->reveal(),
            $this->environment
        );
    }

    public function testResolve()
    {
        $resolved = $this->templateAttributeResolver->resolve(['custom' => 'test']);

        $this->assertEquals($resolved, [
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
            'request' => [
                'webspaceKey' => 'sulu_io',
                'locale' => 'en',
            ],
        ]);
    }
}

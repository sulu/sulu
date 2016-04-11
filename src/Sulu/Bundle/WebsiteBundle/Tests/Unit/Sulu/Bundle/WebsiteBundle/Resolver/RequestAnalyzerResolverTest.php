<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\WebsiteBundle\Resolver\RequestAnalyzerResolver;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestAnalyzerResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestAnalyzerResolver
     */
    private $resolver;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    protected function setUp()
    {
        parent::setUp();

        $this->prepareWebspaceManager();

        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->resolver = new RequestAnalyzerResolver(
            $this->webspaceManager->reveal(),
            $this->requestStack->reveal(),
            'dev',
            ['analyticsKey' => 'UA-SULU-Test']
        );
    }

    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager === null) {
            $webspace = new Webspace();
            $en = new Localization();
            $en->setLanguage('en');
            $en_us = new Localization();
            $en_us->setLanguage('en');
            $en_us->setCountry('us');
            $en_us->setParent($en);
            $en->addChild($en_us);

            $de = new Localization();
            $de->setLanguage('de');
            $de_at = new Localization();
            $de_at->setLanguage('de');
            $de_at->setCountry('at');
            $de_at->setParent($de);
            $de->addChild($de_at);

            $es = new Localization();
            $es->setLanguage('es');

            $webspace->addLocalization($en);
            $webspace->addLocalization($de);
            $webspace->addLocalization($es);

            $this->webspaceManager = $this->prophesize('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
            $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace);
        }
    }

    public function testResolve()
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');

        $portal = new Portal();
        $locale = new Localization();
        $locale->setLanguage('de');
        $locale->setDefault(true);
        $portal->addLocalization($locale);

        $localization = new Localization();
        $localization->setLanguage('de');
        $localization->setCountry('at');

        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getHost()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('de_at');

        $requestAnalyzer = $this->prophesize(RequestAnalyzer::class);
        $requestAnalyzer->getWebspace()->willReturn($webspace);
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);
        $requestAnalyzer->getPortalUrl()->willReturn('sulu.io/de');
        $requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');
        $requestAnalyzer->getResourceLocator()->willReturn('/search');
        $requestAnalyzer->getGetParameters()->willReturn(['p' => 1]);
        $requestAnalyzer->getPostParameters()->willReturn([]);
        $requestAnalyzer->getPortal()->willReturn($portal);
        $requestAnalyzer->getAnalyticsKey()->willReturn('analyticsKey');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());

        $result = $this->resolver->resolve($requestAnalyzer->reveal());
        $this->assertEquals(
            [
                'request' => [
                    'webspaceKey' => 'sulu_io',
                    'locale' => 'de_at',
                    'defaultLocale' => 'de',
                    'portalUrl' => 'sulu.io/de',
                    'resourceLocatorPrefix' => '/de',
                    'resourceLocator' => '/search',
                    'get' => ['p' => 1],
                    'post' => [],
                    'analyticsKey' => 'analyticsKey',
                    'routeParameters' => [
                        'host' => 'sulu.lo',
                        'prefix' => 'de_at',
                    ],
                ],
            ],
            $result
        );
    }

    public function testResolveForPreview()
    {
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrl()->willReturn('sulu.io/de');
        $portalInformation->getHost()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', 'dev')
            ->willReturn(['sulu.io/de' => $portalInformation->reveal()]);

        $request = new \Symfony\Component\HttpFoundation\Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $result = $this->resolver->resolveForPreview('sulu_io', 'de');
        $this->assertEquals(
            [
                'request' => [
                    'webspaceKey' => 'sulu_io',
                    'locale' => 'de',
                    'defaultLocale' => 'de',
                    'portalUrl' => 'sulu.io/de',
                    'resourceLocatorPrefix' => '',
                    'resourceLocator' => '',
                    'get' => [],
                    'post' => [],
                    'analyticsKey' => 'UA-SULU-Test',
                    'routeParameters' => [
                        'host' => 'sulu.lo',
                        'prefix' => 'de',
                    ],
                ],
            ],
            $result
        );
    }

    public function testResolveForPreviewWithRequestParameter()
    {
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrl()->willReturn('sulu.io/de');
        $portalInformation->getHost()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', 'dev')
            ->willReturn(['sulu.io/de' => $portalInformation->reveal()]);

        $request = new \Symfony\Component\HttpFoundation\Request(['test' => 1], ['test' => 2]);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $result = $this->resolver->resolveForPreview('sulu_io', 'de');
        $this->assertEquals(
            [
                'request' => [
                    'webspaceKey' => 'sulu_io',
                    'locale' => 'de',
                    'defaultLocale' => 'de',
                    'portalUrl' => 'sulu.io/de',
                    'resourceLocatorPrefix' => '',
                    'resourceLocator' => '',
                    'get' => ['test' => 1],
                    'post' => ['test' => 2],
                    'analyticsKey' => 'UA-SULU-Test',
                    'routeParameters' => [
                        'host' => 'sulu.lo',
                        'prefix' => 'de',
                    ],
                ],
            ],
            $result
        );
    }
}

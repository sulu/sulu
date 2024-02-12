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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Resolver\RequestAnalyzerResolver;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestAnalyzerResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var RequestAnalyzerResolver
     */
    private $resolver;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareWebspaceManager();

        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->resolver = new RequestAnalyzerResolver(
            $this->webspaceManager->reveal(),
            $this->requestStack->reveal()
        );
    }

    protected function prepareWebspaceManager()
    {
        if (null === $this->webspaceManager) {
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

            $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
            $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace);
        }
    }

    public function testResolve(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $webspace->setName('Sulu');

        $portal = new Portal();
        $portal->setKey('sulu_io_portal');
        $portal->setName('Sulu Portal');
        $locale = new Localization();
        $locale->setLanguage('de');
        $locale->setDefault(true);
        $portal->addLocalization($locale);

        $localization = new Localization();
        $localization->setLanguage('de');
        $localization->setCountry('at');

        $segment = new Segment();
        $segment->setKey('s');

        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getHost()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('de_at');

        $requestAnalyzer = $this->prophesize(RequestAnalyzer::class);
        $requestAnalyzer->getWebspace()->willReturn($webspace);
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);
        $requestAnalyzer->getPortalUrl()->willReturn('sulu.io/de');
        $requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');
        $requestAnalyzer->getResourceLocator()->willReturn('/search');
        $requestAnalyzer->getPortal()->willReturn($portal);
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getSegment()->willReturn($segment);

        $result = $this->resolver->resolve($requestAnalyzer->reveal());
        $this->assertEquals(
            [
                'request' => [
                    'webspaceKey' => 'sulu_io',
                    'webspaceName' => 'Sulu',
                    'segmentKey' => 's',
                    'portalKey' => 'sulu_io_portal',
                    'portalName' => 'Sulu Portal',
                    'defaultLocale' => 'de',
                    'portalUrl' => 'sulu.io/de',
                    'resourceLocatorPrefix' => '/de',
                    'resourceLocator' => '/search',
                ],
            ],
            $result
        );
    }
}

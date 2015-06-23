<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\WebsiteBundle\Resolver\RequestAnalyzerResolver;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;

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

    protected function setUp()
    {
        parent::setUp();

        $this->prepareWebspaceManager();

        $this->resolver = new RequestAnalyzerResolver(
            $this->webspaceManager->reveal(),
            'dev',
            array('analyticsKey' => 'UA-SULU-Test')
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

        $requestAnalyzer = $this->prophesize('Sulu\Component\Webspace\Analyzer\WebsiteRequestAnalyzer');
        $requestAnalyzer->getWebspace()->willReturn($webspace);
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);
        $requestAnalyzer->getPortalUrl()->willReturn('sulu.io/de');
        $requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');
        $requestAnalyzer->getResourceLocator()->willReturn('/search');
        $requestAnalyzer->getGetParameters()->willReturn(array('p' => 1));
        $requestAnalyzer->getPostParameters()->willReturn(array());
        $requestAnalyzer->getPortal()->willReturn($portal);
        $requestAnalyzer->getAnalyticsKey()->willReturn('analyticsKey');

        $result = $this->resolver->resolve($requestAnalyzer->reveal());
        $this->assertEquals(
            array(
                'request' => array(
                    'webspaceKey' => 'sulu_io',
                    'locale' => 'de_at',
                    'defaultLocale' => 'de',
                    'portalUrl' => 'sulu.io/de',
                    'resourceLocatorPrefix' => '/de',
                    'resourceLocator' => '/search',
                    'get' => array('p' => 1),
                    'post' => array(),
                    'analyticsKey' => 'analyticsKey',
                ),
            ),
            $result
        );
    }

    public function testResolveForPreview()
    {
        $this->webspaceManager->getPortalInformations('dev')->willReturn(array('sulu.io/de' => array()));

        $result = $this->resolver->resolveForPreview('sulu_io', 'de');
        $this->assertEquals(
            array(
                'request' => array(
                    'webspaceKey' => 'sulu_io',
                    'locale' => 'de',
                    'defaultLocale' => 'de',
                    'portalUrl' => 'sulu.io/de',
                    'resourceLocatorPrefix' => '',
                    'resourceLocator' => '',
                    'get' => array(),
                    'post' => array(),
                    'analyticsKey' => 'UA-SULU-Test',
                ),
            ),
            $result
        );
    }
}

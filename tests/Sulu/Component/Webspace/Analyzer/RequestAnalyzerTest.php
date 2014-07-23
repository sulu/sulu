<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer;


use PHPUnit_Framework_MockObject_MockObject;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class RequestAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestAnalyzer
     */
    private $requestAnalyzer;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $webspaceManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $userRepository;

    public function setUp()
    {
        $this->webspaceManager = $this->getMockForAbstractClass(
            '\Sulu\Component\Webspace\Manager\WebspaceManagerInterface',
            array(),
            '',
            true,
            true,
            true,
            array('findPortalInformationByUrl')
        );

        $this->userRepository = $this->getMockForAbstractClass(
            '\Sulu\Component\Security\UserRepositoryInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setSystem')
        );

        $this->requestAnalyzer = new RequestAnalyzer($this->webspaceManager, $this->userRepository, 'prod');
    }

    /**
     * @param $portalInformation
     */
    protected function prepareWebspaceManager($portalInformation)
    {
        $this->webspaceManager->expects($this->any())->method('findPortalInformationByUrl')->will(
            $this->returnValue($portalInformation)
        );
    }

    public function testAnalyze()
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization();
        $localization->setCountry('at');
        $localization->setLanguage('de');

        $portalInformation = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo/test',
            null,
            null
        );

        $this->prepareWebspaceManager($portalInformation);

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getRequestUri')->will($this->returnValue('/test/path/to'));
        $request->expects($this->once())->method('setLocale')->with('de_at');
        $this->requestAnalyzer->analyze($request);

        $this->assertEquals('de_at', $this->requestAnalyzer->getCurrentLocalization()->getLocalization());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentWebspace()->getKey());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getCurrentSegment());
        $this->assertEquals('sulu.lo/test', $this->requestAnalyzer->getCurrentPortalUrl());
        $this->assertEquals('/path/to', $this->requestAnalyzer->getCurrentResourceLocator());
        $this->assertEquals('/test', $this->requestAnalyzer->getCurrentResourceLocatorPrefix());
    }

    public function testAnalyzePartialMatch()
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization();
        $localization->setCountry('at');
        $localization->setLanguage('de');

        $portalInformation = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo',
            null,
            'sulu.lo/test'
        );

        $this->prepareWebspaceManager($portalInformation);

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getRequestUri')->will($this->returnValue('/test/path/to'));
        $this->requestAnalyzer->analyze($request);

        $this->assertEquals('de_at', $this->requestAnalyzer->getCurrentLocalization()->getLocalization());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentWebspace()->getKey());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getCurrentSegment());
        $this->assertEquals('sulu.lo', $this->requestAnalyzer->getCurrentPortalUrl());
        $this->assertEquals('sulu.lo/test', $this->requestAnalyzer->getCurrentRedirect());
        $this->assertEquals('/test/path/to', $this->requestAnalyzer->getCurrentResourceLocator());
        $this->assertEquals('', $this->requestAnalyzer->getCurrentResourceLocatorPrefix());
    }

    /**
     * @expectedException \Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException
     */
    public function testAnalyzeNotExisting()
    {
        $this->webspaceManager->expects($this->any())->method('findPortalInformationByUrl')->will(
            $this->returnValue(null)
        );

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $this->requestAnalyzer->analyze($request);
    }
}

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

        $this->requestAnalyzer = new RequestAnalyzer($this->webspaceManager, 'prod');
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
            PortalInformation::TYPE_FULL_MATCH,
            $webspace,
            $portal,
            $localization,
            'sulu.lo/test',
            null,
            null
        );

        $this->webspaceManager->expects($this->any())->method('findPortalInformationByUrl')->will(
            $this->returnValue($portalInformation)
        );

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getRequestUri')->will($this->returnValue('/test/path/to'));
        $this->requestAnalyzer->analyze($request);

        $this->assertEquals('de_at', $this->requestAnalyzer->getCurrentLocalization()->getLocalization());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentWebspace()->getKey());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getCurrentSegment());
        $this->assertEquals('sulu.lo/test', $this->requestAnalyzer->getCurrentPortalUrl());
        $this->assertEquals('/path/to', $this->requestAnalyzer->getCurrentResourceLocator());
        $this->assertEquals('/test', $this->requestAnalyzer->getCurrentResourceLocatorPrefix());
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

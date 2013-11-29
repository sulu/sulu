<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace\Analyzer;


use PHPUnit_Framework_MockObject_MockObject;
use Sulu\Component\Workspace\Localization;
use Sulu\Component\Workspace\Manager\WorkspaceManager;
use Sulu\Component\Workspace\Portal;
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
    private $workspaceManager;

    public function setUp()
    {
        $this->workspaceManager = $this->getMockForAbstractClass(
            '\Sulu\Component\Workspace\Manager\WorkspaceManagerInterface',
            array(),
            '',
            true,
            true,
            true,
            array('findPortalInformationByUrl')
        );

        $this->requestAnalyzer = new RequestAnalyzer($this->workspaceManager, 'prod');
    }

    public function testAnalyze()
    {
        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization();
        $localization->setCountry('at');
        $localization->setLanguage('de');

        $portalInformation = array(
            'portal' => $portal,
            'localization' => $localization,
            'segment' => null,
            'url' => 'sulu.lo/test'
        );

        $this->workspaceManager->expects($this->any())->method('findPortalInformationByUrl')->will(
            $this->returnValue($portalInformation)
        );

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getRequestUri')->will($this->returnValue('/test/path/to'));
        $this->requestAnalyzer->analyze($request);

        $this->assertEquals('de-at', $this->requestAnalyzer->getCurrentLocalization()->getLocalization());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getCurrentSegment());
        $this->assertEquals('sulu.lo/test', $this->requestAnalyzer->getCurrentPortalUrl());
        $this->assertEquals('/path/to', $this->requestAnalyzer->getCurrentPath());
    }

    /**
     * @expectedException \Sulu\Component\Workspace\Analyzer\Exception\UrlMatchNotFoundException
     */
    public function testAnalyzeNotExisting()
    {
        $this->workspaceManager->expects($this->any())->method('findPortalInformationByUrl')->will(
            $this->returnValue(null)
        );

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $this->requestAnalyzer->analyze($request);
    }
}

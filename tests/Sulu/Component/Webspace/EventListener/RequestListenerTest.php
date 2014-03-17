<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\EventListener;


use PHPUnit_Framework_MockObject_MockObject;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Webspace\Portal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestListener
     */
    private $requestListener;

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

        $this->requestListener = new RequestListener($this->requestAnalyzer);
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
            'url' => 'sulu.lo'
        );

        $this->webspaceManager->expects($this->any())->method('findPortalInformationByUrl')->will(
            $this->returnValue($portalInformation)
        );

        $kernel = $this->getMock('\Symfony\Component\HttpKernel\Kernel', array(), array(), '', false);
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $this->requestListener->onKernelRequest(new GetResponseEvent($kernel, $request, ''));

        $this->assertEquals('de_at', $this->requestAnalyzer->getCurrentLocalization()->getLocalization());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getCurrentSegment());
    }
}

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
use Sulu\Component\Webspace\Analyzer\WebsiteRequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestListener
     */
    private $requestListener;

    /**
     * @var WebsiteRequestAnalyzer
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

        $this->requestAnalyzer = new WebsiteRequestAnalyzer($this->webspaceManager, $this->userRepository, 'prod');

        $this->requestListener = new RequestListener($this->requestAnalyzer);
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

        $this->webspaceManager->expects($this->any())->method('findPortalInformationByUrl')->will(
            $this->returnValue($portalInformation)
        );

        $kernel = $this->getMock('\Symfony\Component\HttpKernel\Kernel', array(), array(), '', false);
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->request = new ParameterBag(array('post' => 1));
        $request->query = new ParameterBag(array('get' => 1));
        $this->requestListener->onKernelRequest(new GetResponseEvent($kernel, $request, ''));

        $this->assertEquals('de_at', $this->requestAnalyzer->getCurrentLocalization()->getLocalization());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentWebspace()->getKey());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getCurrentSegment());
        $this->assertEquals(array('post' => 1), $this->requestAnalyzer->getCurrentPostParameter());
        $this->assertEquals(array('get' => 1), $this->requestAnalyzer->getCurrentGetParameter());
    }
}

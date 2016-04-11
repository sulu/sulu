<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Unit\Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Bundle\WebsiteBundle\EventListener\SetThemeEventListener;

class SetThemeEventListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $activeTheme;
    protected $requestAnalyzer;

    public function setUp()
    {
        $this->requestAnalyzer = $this->getMock('Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface');
        $this->activeTheme = $this->getMockBuilder('Liip\ThemeBundle\ActiveTheme')
            ->disableOriginalConstructor()->getMock();

        $this->portal = $this->getMock('Sulu\Component\Webspace\Portal');
        $this->webspace = $this->getMock('Sulu\Component\Webspace\Webspace');
        $this->theme = $this->getMock('Sulu\Component\Webspace\Theme');
        $this->event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new SetThemeEventListener($this->requestAnalyzer, $this->activeTheme);
    }

    public function testEventListener()
    {
        $this->requestAnalyzer->expects($this->once())
            ->method('getPortal')
            ->will($this->returnValue($this->portal));
        $this->portal->expects($this->once())
            ->method('getWebspace')
            ->will($this->returnValue($this->webspace));
        $this->webspace->expects($this->once())
            ->method('getTheme')
            ->will($this->returnValue($this->theme));
        $this->theme->expects($this->once())
            ->method('getKey')
            ->will($this->returnValue('test'));
        $this->activeTheme->expects($this->once())
            ->method('setName')
            ->with('test');

        $this->listener->setActiveTheme($this->event);
    }

    public function testEventListenerNotMaster()
    {
        $this->requestAnalyzer->expects($this->once())
            ->method('getPortal')
            ->willReturn(null);
        $this->webspace->expects($this->never())
            ->method('getTheme');

        $this->listener->setActiveTheme($this->event);
    }
}

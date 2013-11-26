<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\EventListener;

use Sulu\Component\Workspace\Portal;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class PortalListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testKernelRequest()
    {
        $portal = new Portal();

        $portalManager = $this->getMockForAbstractClass('\Sulu\Component\Workspace\Manager\PortalManagerInterface', array(), '', true, true, true, array('findByUrl'));
        $portalManager->expects($this->any())->method('findByUrl')->will($this->returnValue($portal));
        $portalManager->expects($this->once())->method('setCurrentPortal');

        $portalListener = new PortalListener($portalManager);

        $kernel = $this->getMock('\Symfony\Component\HttpKernel\Kernel', array(), array(), '', false);

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');

        $getResponseEvent = new GetResponseEvent($kernel, $request, '');
        $portalListener->onKernelRequest($getResponseEvent);
    }
}

<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sensio\Bundle\FrameworkExtraBundle\EventListener\SecurityListener;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class SuluSecurityListenerTest extends ProphecyTestCase
{
    /**
     * @var SecurityListener
     */
    private $securityListener;

    /**
     * @var ObjectProphecy
     */
    private $securityChecker;

    /**
     * @var FilterControllerEvent
     */
    private $filterControllerEvent;

    public function setUp()
    {
        parent::setUp();

        $this->securityChecker = $this->prophesize('Sulu\Bundle\SecurityBundle\Permission\SecurityCheckerInterface');
        $this->securityListener = new SuluSecurityListener($this->securityChecker->reveal());
        $this->filterControllerEvent = $this->prophesize('Symfony\Component\HttpKernel\Event\FilterControllerEvent');
    }

    public function tearDown()
    {
        $this->assertPostConditions();
    }

    public function testRestController()
    {
        $controller = $this->prophesize('Sulu\Component\Rest\RestController');

        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $request->getMethod()->willReturn('GET');

        $this->filterControllerEvent->getController()->willReturn(array($controller));
        $this->filterControllerEvent->getRequest()->willReturn($request);

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());

        $this->securityChecker->checkPermission(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testNonRestControllerAbstain()
    {
        $this->securityChecker->checkPermission(Argument::cetera())->shouldNotHaveBeenCalled();

        $controller = $this->prophesize('Symfony\Bundle\FrameworkBundle\Controller\Controller');

        $this->filterControllerEvent->getController()->willReturn(array($controller));

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());
    }

    /**
     * @dataProvider provideMethodActionMapping
     */
    public function testMethodPermissionMapping($method, $action, $permission)
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $request->getMethod()->willReturn($method);

        $controller = $this->prophesize('Sulu\Component\Rest\RestController');

        $this->filterControllerEvent->getRequest()->willReturn($request->reveal());
        $this->filterControllerEvent->getController()->willReturn(array($controller, $action));

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());

        $this->securityChecker->checkPermission(Argument::any(), Argument::exact($permission), Argument::any())
            ->shouldHaveBeenCalled();
    }

    public static function provideMethodActionMapping()
    {
        return array(
            array('GET', 'getAction', 'view'),
            array('POST', 'postAction', 'add'),
            array('POST', 'postMergeAction', 'edit'),
            array('PUT', 'putAction', 'edit'),
            array('DELETE', 'deleteAction', 'delete'),
        );
    }
}

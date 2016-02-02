<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sensio\Bundle\FrameworkExtraBundle\EventListener\SecurityListener;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class SuluSecurityListenerTest extends \PHPUnit_Framework_TestCase
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

        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->securityListener = new SuluSecurityListener($this->securityChecker->reveal());
        $this->filterControllerEvent = $this->prophesize(FilterControllerEvent::class);
    }

    public function testObjectRestController()
    {
        $controller = $this->prophesize(SecuredObjectControllerInterface::class);
        $controller->willImplement(SecuredControllerInterface::class);
        $controller->getSecuredClass()->willReturn('Acme\Example');
        $controller->getSecuredObjectId(Argument::any())->willReturn('1');
        $controller->getSecurityContext()->willReturn('security.context');
        $controller->getLocale(Argument::any())->willReturn(null);

        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('GET');
        $request->get('id')->willReturn('1');

        $this->filterControllerEvent->getController()->willReturn([$controller->reveal()]);
        $this->filterControllerEvent->getRequest()->willReturn($request->reveal());

        $this->securityChecker->checkPermission(
            new SecurityCondition('security.context', null, 'Acme\Example', '1'),
            'view',
            null
        )->shouldBeCalled();

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());
    }

    public function testObjectRestControllerWithContext()
    {
        $controller = $this->prophesize(SecuredControllerInterface::class);
        $controller->willImplement(SecuredObjectControllerInterface::class);
        $controller->getSecuredClass()->willReturn('Acme\Example');
        $controller->getSecurityContext()->willReturn('security.context');
        $controller->getSecuredObjectId(Argument::any())->willReturn('1');
        $controller->getLocale(Argument::any())->willReturn(null);

        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('GET');
        $request->get('id')->willReturn('1');

        $this->filterControllerEvent->getController()->willReturn([$controller->reveal()]);
        $this->filterControllerEvent->getRequest()->willReturn($request->reveal());

        $this->securityChecker->checkPermission(
            new SecurityCondition('security.context', null, 'Acme\Example', '1'),
            Argument::cetera(),
            null
        )->shouldBeCalled();

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());
    }

    public function testRestController()
    {
        $controller = $this->prophesize(SecuredControllerInterface::class);
        $controller->getSecurityContext()->willReturn('security.context');
        $controller->getLocale(Argument::any())->willReturn('de');

        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('GET');
        $request->get('id')->willReturn('1');

        $this->filterControllerEvent->getController()->willReturn([$controller]);
        $this->filterControllerEvent->getRequest()->willReturn($request);

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());

        $this->securityChecker->checkPermission(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testNonRestControllerAbstain()
    {
        $this->securityChecker->checkPermission(Argument::cetera())->shouldNotHaveBeenCalled();

        $controller = $this->prophesize(Controller::class);

        $request = $this->prophesize(Request::class);

        $this->filterControllerEvent->getController()->willReturn([$controller->reveal()]);
        $this->filterControllerEvent->getRequest()->willReturn($request);

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());
    }

    public function testSubject()
    {
        $controller = $this->prophesize(SecuredControllerInterface::class);
        $controller->getSecurityContext()->willReturn('sulu.media.collection');
        $controller->getLocale(Argument::any())->willReturn(null);

        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('GET');
        $request->get('id')->willReturn('1');

        $this->filterControllerEvent->getRequest()->willReturn($request);
        $this->filterControllerEvent->getController()->willReturn([$controller->reveal(), 'getAction']);

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());

        $this->securityChecker->checkPermission('sulu.media.collection', Argument::cetera());
    }

    /**
     * @dataProvider provideMethodActionMapping
     */
    public function testMethodPermissionMapping($method, $action, $permission)
    {
        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn($method);
        $request->get('id')->willReturn('1');

        $controller = $this->prophesize(SecuredControllerInterface::class);
        $controller->getSecurityContext()->willReturn('security.context');
        $controller->getLocale(Argument::any())->willReturn('de');

        $this->filterControllerEvent->getRequest()->willReturn($request->reveal());
        $this->filterControllerEvent->getController()->willReturn([$controller->reveal(), $action]);

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());

        $this->securityChecker->checkPermission(Argument::any(), $permission, Argument::any())
            ->shouldHaveBeenCalled();
    }

    public function testLocale()
    {
        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn(null);
        $request->get('id')->willReturn('1');

        $controller = $this->prophesize(SecuredControllerInterface::class);
        $controller->getSecurityContext()->willReturn('security.context');
        $controller->getLocale(Argument::any())->willReturn('de');

        $this->filterControllerEvent->getRequest()->willReturn($request->reveal());
        $this->filterControllerEvent->getController()->willReturn([$controller->reveal(), 'getAction']);

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());

        $this->securityChecker->checkPermission(Argument::any(), Argument::any())->shouldHaveBeenCalled();
    }

    public function testNullSecurityContext()
    {
        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn(null);
        $request->get('id')->willReturn('1');

        $controller = $this->prophesize(SecuredControllerInterface::class);
        $controller->getSecurityContext()->willReturn(null);
        $controller->getLocale(Argument::any())->willReturn('de');

        $this->filterControllerEvent->getRequest()->willReturn($request->reveal());
        $this->filterControllerEvent->getController()->willReturn([$controller->reveal(), 'getAction']);

        $this->securityListener->onKernelController($this->filterControllerEvent->reveal());

        $this->securityChecker->checkPermission(Argument::any(), Argument::any())->shouldNotBeCalled();
    }

    public static function provideMethodActionMapping()
    {
        return [
            ['GET', 'getAction', 'view'],
            ['POST', 'postAction', 'add'],
            ['POST', 'postMergeAction', 'edit'],
            ['PUT', 'putAction', 'edit'],
            ['PATCH', 'patchAction', 'edit'],
            ['DELETE', 'deleteAction', 'delete'],
        ];
    }
}

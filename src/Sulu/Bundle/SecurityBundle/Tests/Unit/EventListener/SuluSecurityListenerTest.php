<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TestCallableClass implements SecuredControllerInterface
{
    public function getSecurityContext(): string
    {
        return 'security.context';
    }

    public function getLocale(Request $request): string
    {
        return 'en';
    }

    public function __invoke(): Response
    {
        return new Response();
    }
}

class SuluSecurityListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SuluSecurityListener
     */
    private $securityListener;

    /**
     * @var ObjectProphecy
     */
    private $securityChecker;

    /**
     * @var HttpKernelInterface
     */
    private $kernel;

    public function setUp(): void
    {
        parent::setUp();

        $this->kernel = $this->prophesize(HttpKernelInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->securityListener = new SuluSecurityListener($this->securityChecker->reveal());
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

        $controllerEvent = $this->createControllerEvent(
            [$controller->reveal(), 'someFunction'],
            $request->reveal()
        );

        $this->securityChecker->checkPermission(
            new SecurityCondition('security.context', null, 'Acme\Example', '1'),
            'view',
            null
        )->shouldBeCalled();

        $this->securityListener->onKernelController($controllerEvent);
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

        $controllerEvent = $this->createControllerEvent(
            [$controller->reveal(), 'someFunction'],
            $request->reveal()
        );

        $this->securityChecker->checkPermission(
            new SecurityCondition('security.context', null, 'Acme\Example', '1'),
            Argument::cetera(),
            null
        )->shouldBeCalled();

        $this->securityListener->onKernelController($controllerEvent);
    }

    public function testRestController()
    {
        $controller = $this->prophesize(SecuredControllerInterface::class);
        $controller->getSecurityContext()->willReturn('security.context');
        $controller->getLocale(Argument::any())->willReturn('de');

        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('GET');
        $request->get('id')->willReturn('1');

        $controllerEvent = $this->createControllerEvent(
            [$controller->reveal(), 'someFunction'],
            $request->reveal()
        );

        $this->securityListener->onKernelController($controllerEvent);

        $this->securityChecker->checkPermission(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testNonRestControllerAbstain()
    {
        $this->securityChecker->checkPermission(Argument::cetera())->shouldNotHaveBeenCalled();

        $controller = $this->prophesize(AbstractController::class);

        $request = $this->prophesize(Request::class);

        $controllerEvent = $this->createControllerEvent(
            [$controller->reveal(), 'someFunction'],
            $request->reveal()
        );

        $this->securityListener->onKernelController($controllerEvent);
    }

    public function testSubject()
    {
        $controller = $this->prophesize(SecuredControllerInterface::class);
        $controller->getSecurityContext()->willReturn('sulu.media.collection')->shouldBeCalled();
        $controller->getLocale(Argument::any())->willReturn(null);

        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('GET');
        $request->get('id')->willReturn('1');

        $controllerEvent = $this->createControllerEvent(
            [$controller->reveal(), 'getAction'],
            $request->reveal()
        );
        $this->securityListener->onKernelController($controllerEvent);

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

        $controllerEvent = $this->createControllerEvent(
            [$controller->reveal(), $action],
            $request->reveal()
        );

        $this->securityListener->onKernelController($controllerEvent);

        $this->securityChecker->checkPermission(Argument::any(), $permission, Argument::any())
            ->shouldHaveBeenCalled();
    }

    /**
     * @dataProvider provideInvokeMethodActionMapping
     */
    public function testCallableControllerPermission($method, $permission)
    {
        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn($method);
        $request->get('id')->willReturn('1');

        $controller = new TestCallableClass();
        $controllerEvent = $this->createControllerEvent($controller, $request->reveal());

        $this->securityListener->onKernelController($controllerEvent);

        $this->securityChecker->checkPermission(Argument::any(), $permission, Argument::any())
            ->shouldHaveBeenCalled();
    }

    public function testLocale()
    {
        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('GET');
        $request->get('id')->willReturn('1');

        $controller = $this->prophesize(SecuredControllerInterface::class);
        $controller->getSecurityContext()->willReturn('security.context');
        $controller->getLocale(Argument::any())->willReturn('de');

        $controllerEvent = $this->createControllerEvent(
            [$controller->reveal(), 'getAction'],
            $request->reveal()
        );

        $this->securityListener->onKernelController($controllerEvent);

        $this->securityChecker->checkPermission(Argument::any(), Argument::any())->shouldHaveBeenCalled();
    }

    public function testNullSecurityContext()
    {
        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('GET');
        $request->get('id')->willReturn('1');

        $controller = $this->prophesize(SecuredControllerInterface::class);
        $controller->getSecurityContext()->willReturn(null);
        $controller->getLocale(Argument::any())->willReturn('de');

        $controllerEvent = $this->createControllerEvent(
            [$controller->reveal(), 'getAction'],
            $request->reveal()
        );

        $this->securityListener->onKernelController($controllerEvent);

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

    public static function provideInvokeMethodActionMapping()
    {
        return [
            ['GET', 'view'],
            ['POST', 'add'],
            ['PUT', 'edit'],
            ['PATCH', 'edit'],
            ['DELETE', 'delete'],
        ];
    }

    private function createControllerEvent($controller, Request $request): ControllerEvent
    {
        return new ControllerEvent(
            $this->kernel->reveal(),
            $controller,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}

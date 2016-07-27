<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Preview\Renderer;

use Prophecy\Argument;
use Sulu\Bundle\PreviewBundle\Preview\Events;
use Sulu\Bundle\PreviewBundle\Preview\Events\PreRenderEvent;
use Sulu\Bundle\PreviewBundle\Preview\Exception\PortalNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\RouteDefaultsProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TemplateNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TwigException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\UnexpectedException;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\KernelFactoryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRenderer;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PreviewRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouteDefaultsProviderInterface
     */
    private $routeDefaultsProvider;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var KernelFactoryInterface
     */
    private $kernelFactory;

    /**
     * @var HttpKernelInterface
     */
    private $httpKernel;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var PreviewRendererInterface
     */
    private $renderer;

    /**
     * @var array
     */
    private $previewDefault = ['analyticsKey' => 'UA-xxxx'];

    /**
     * @var string
     */
    private $environment = 'prod';

    public function setUp()
    {
        $this->routeDefaultsProvider = $this->prophesize(RouteDefaultsProviderInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->kernelFactory = $this->prophesize(KernelFactoryInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->httpKernel = $this->prophesize(HttpKernelInterface::class);
        $this->kernelFactory->create($this->environment)->willReturn($this->httpKernel->reveal());

        $this->renderer = new PreviewRenderer(
            $this->routeDefaultsProvider->reveal(),
            $this->requestStack->reveal(),
            $this->kernelFactory->reveal(),
            $this->webspaceManager->reveal(),
            $this->eventDispatcher->reveal(),
            $this->previewDefault,
            $this->environment
        );
    }

    public function testRender()
    {
        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MASTER_REQUEST, false)
            ->shouldBeCalled()->willReturn(new Response('<title>Hallo</title>'));

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $response = $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);
        $this->assertEquals('<title>Hallo</title>', $response);
    }

    public function testRenderWithoutRequest()
    {
        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldBeCalled();

        $this->httpKernel->handle(
            Argument::that(
                function (Request $request) {
                    return null !== $request->get('_sulu');
                }
            ),
            HttpKernelInterface::MASTER_REQUEST,
            false
        )->shouldBeCalled()->willReturn(new Response('<title>Hallo</title>'));

        $this->requestStack->getCurrentRequest()->willReturn(null);

        $response = $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);
        $this->assertEquals('<title>Hallo</title>', $response);
    }

    public function testRenderPortalNotFound()
    {
        $this->setExpectedException(PortalNotFoundException::class, '', 9901);

        $object = $this->prophesize(\stdClass::class);

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->shouldNotBeCalled();
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->shouldNotBeCalled();

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldNotBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MASTER_REQUEST, false)
            ->shouldNotBeCalled();

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);
    }

    public function testRenderRouteDefaultsProviderNotFound()
    {
        $this->setExpectedException(RouteDefaultsProviderNotFoundException::class, '', 9902);

        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(false);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->shouldNotBeCalled();

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldNotBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MASTER_REQUEST, false)
            ->shouldNotBeCalled();

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);
    }

    public function testRenderTwigError()
    {
        $this->setExpectedException(TwigException::class, '', 9903);

        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MASTER_REQUEST, false)
            ->shouldBeCalled()->willThrow(new \Twig_Error_Runtime('Test error'));

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);
    }

    public function testRenderInvalidArgumentException()
    {
        $this->setExpectedException(TemplateNotFoundException::class, '', 9904);

        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MASTER_REQUEST, false)
            ->shouldBeCalled()->willThrow(new \InvalidArgumentException());

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);
    }

    public function testRenderHttpExceptionWithPreviousException()
    {
        $this->setExpectedException(TemplateNotFoundException::class, '', 9904);

        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MASTER_REQUEST, false)
            ->shouldBeCalled()->willThrow(
                new HttpException(406, 'Error encountered when rendering content', new \InvalidArgumentException())
            );

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);
    }

    public function testRenderHttpExceptionWithoutPreviousException()
    {
        $this->setExpectedException(UnexpectedException::class, '', 9905);

        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MASTER_REQUEST, false)
            ->shouldBeCalled()->willThrow(
                new HttpException(406, 'Error encountered when rendering content')
            );

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);
    }
}

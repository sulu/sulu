<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Preview\Renderer;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PreviewBundle\Preview\Events;
use Sulu\Bundle\PreviewBundle\Preview\Events\PreRenderEvent;
use Sulu\Bundle\PreviewBundle\Preview\Exception\RouteDefaultsProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TemplateNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TwigException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\UnexpectedException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\WebspaceLocalizationNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\WebspaceNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\KernelFactoryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRenderer;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url\Replacer;
use Sulu\Component\Webspace\Url\ReplacerInterface;
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
     * @var ReplacerInterface
     */
    private $replacer;

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

    /**
     * @var string
     */
    private $defaultHost = 'default-sulu.io';

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
            new Replacer(),
            $this->previewDefault,
            $this->environment,
            $this->defaultHost,
            'X-Sulu-Target-Group'
        );
    }

    public function portalDataProvider()
    {
        return [
            [
                'http',
                'sulu.lo',
            ],
            [
                'https',
                'sulu.lo',
            ],
            [
                'http',
                Replacer::REPLACER_HOST,
            ],
            [
                'https',
                Replacer::REPLACER_HOST,
            ],
        ];
    }

    public function portalWithoutRequestDataProvider()
    {
        return [
            [
                'sulu.lo',
            ],
            [
                Replacer::REPLACER_HOST,
            ],
        ];
    }

    /**
     * @dataProvider portalDataProvider
     */
    public function testRender($scheme, $portalUrl)
    {
        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn($portalUrl);
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldBeCalled();

        $this->render($object, $scheme, $portalUrl);
    }

    /**
     * @param ObjectProphecy $object
     * @param string $expectedScheme
     * @param string $expectedHost
     * @param int $expectedPort
     * @param bool $hasRequest
     */
    protected function render(
        ObjectProphecy $object,
        $expectedScheme = 'http',
        $expectedHost = 'sulu.lo',
        $expectedPort = 8080,
        $hasRequest = true
    ) {
        $server = [
            'SERVER_NAME' => 'admin-sulu.io',
            'HTTP_HOST' => 'admin-sulu.io:' . $expectedPort,
            'SERVER_PORT' => $expectedPort,
        ];

        if ('https' === $expectedScheme) {
            $server['HTTPS'] = true;
        }

        if (Replacer::REPLACER_HOST === $expectedHost && $hasRequest) {
            $expectedHost = 'admin-sulu.io';
        } elseif (Replacer::REPLACER_HOST === $expectedHost) {
            $expectedHost = $this->defaultHost;
        }

        $this->httpKernel->handle(
            Argument::that(
                function(Request $request) use ($expectedScheme, $expectedHost, $expectedPort) {
                    return $request->getHost() === $expectedHost
                        && $request->getPort() === $expectedPort
                        && $request->getScheme() === $expectedScheme;
                }
            ),
            HttpKernelInterface::MASTER_REQUEST,
            false
        )->shouldBeCalled()->willReturn(new Response('<title>Hallo</title>'));

        $request = null;

        if ($hasRequest) {
            $request = new Request([], [], [], [], [], $server);
        }

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $response = $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);
        $this->assertEquals('<title>Hallo</title>', $response);
    }

    public function testRenderWithTargetGroup()
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

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MASTER_REQUEST, false)
            ->shouldBeCalled()->willReturn(new Response('<title>Hallo</title>'));

        $response = $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true, 2);
        $this->assertEquals('<title>Hallo</title>', $response);
    }

    /**
     * @dataProvider portalWithoutRequestDataProvider
     */
    public function testRenderWithoutRequest($portalUrl)
    {
        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn($portalUrl);
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldBeCalled();

        $this->render($object, 'http', $portalUrl, 80, false);
    }

    public function testRenderPortalNotFound()
    {
        $object = $this->prophesize(\stdClass::class);

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([]);

        $webspace = new Webspace();
        $localization = new Localization('de');
        $webspace->addLocalization($localization);
        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MASTER_REQUEST, false)
            ->willReturn(new Response('<title>Hallo</title>'));

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $response = $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);

        $this->assertEquals('<title>Hallo</title>', $response);
    }

    public function testRenderWebspaceNotFound()
    {
        $this->setExpectedException(WebspaceNotFoundException::class);
        $object = new \stdClass();

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->routeDefaultsProvider->supports(get_class($object))->willReturn(true);

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('not_existing', 'de', $this->environment)
            ->willReturn([]);

        $this->webspaceManager->findWebspaceByKey('not_existing')->willReturn(null);

        $this->renderer->render($object, 1, 'not_existing', 'de', true);
    }

    public function testRenderWebspaceLocalizationNotFound()
    {
        $this->setExpectedException(WebspaceLocalizationNotFoundException::class);

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $object = new \stdClass();
        $this->routeDefaultsProvider->supports(get_class($object))->willReturn(true);

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([]);

        $webspace = new Webspace();
        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace);

        $this->renderer->render($object, 1, 'sulu_io', 'de', true);
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

    public function testRenderRequestWithServerAttributes()
    {
        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('{host}');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Events::PRE_RENDER, Argument::type(PreRenderEvent::class))
            ->shouldBeCalled();

        $server = [
            'SERVER_NAME' => 'sulu-preview-test.io',
            'HOST_NAME' => 'sulu-preview-test.io',
            'SERVER_PORT' => 8080,
            'X-Forwarded-Host' => 'forwarded.sulu.io',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Port' => 8081,
            'HTTP_X_REQUESTED_WITH' => 'XmlHttpRequest',
            'HTTP_USER_AGENT' => 'Sulu/Preview',
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
        ];

        $this->httpKernel->handle(
            Argument::that(
                function(Request $request) use ($server) {
                    foreach ($server as $key => $expectedValue) {
                        $value = $request->server->get($key);

                        if ('HTTP_X_REQUESTED_WITH' === $key) {
                            $expectedValue = null;
                        }

                        $this->assertEquals(
                            $expectedValue,
                            $value,
                            sprintf(
                                'Expected for $_SERVER["%s"]: "%s" but "%s" was given',
                                $key,
                                $expectedValue,
                                $value
                            )
                        );
                    }

                    $this->assertTrue($request->attributes->get('preview'));
                    $this->assertTrue($request->attributes->get('partial'));

                    // Assert equals will throw exception so also true can be returned.
                    return true;
                }
            ),
            HttpKernelInterface::MASTER_REQUEST,
            false
        )->shouldBeCalled()->willReturn(new Response('<title>Hallo</title>'));

        $request = new Request([], [], [], [], [], $server, []);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true);
    }
}

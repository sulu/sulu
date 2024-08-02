<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Routing\RequestListener;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;

class RequestListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<RequestContextAwareInterface>
     */
    private $router;

    /**
     * @var ObjectProphecy<PortalInformation>
     */
    private $portalInformation;

    /**
     * @var RequestContext
     */
    private $requestContext;

    /**
     * @var ObjectProphecy<HttpKernelInterface>
     */
    private $kernel;

    public function setUp(): void
    {
        $this->kernel = $this->prophesize(HttpKernelInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->router = $this->prophesize(RequestContextAwareInterface::class);
        $this->portalInformation = $this->prophesize(PortalInformation::class);
        $this->requestContext = new RequestContext();
        $this->router->getContext()->willReturn($this->requestContext);
    }

    public function testRequestAnalyzer(): void
    {
        $this->portalInformation->getPrefix()->willReturn('test/');
        $this->portalInformation->getHost()->willReturn('sulu.io');
        $this->requestAnalyzer->getPortalInformation()->willReturn($this->portalInformation);

        $event = $this->createRequestEvent(new Request());

        $requestListener = new RequestListener($this->router->reveal(), $this->requestAnalyzer->reveal());
        $requestListener->onRequest($event);

        $this->assertSame('test/', $this->requestContext->getParameter('prefix'));
        $this->assertSame('sulu.io', $this->requestContext->getParameter('host'));
    }

    public function testRequestAnalyzerSubRequest(): void
    {
        $this->portalInformation->getPrefix()->willReturn('test/');
        $this->portalInformation->getHost()->willReturn('sulu.io');
        $this->requestAnalyzer->getPortalInformation()->willReturn($this->portalInformation);

        $event = $this->createRequestEvent(Request::create('/_fragment'), HttpKernelInterface::SUB_REQUEST);

        $requestListener = new RequestListener($this->router->reveal(), $this->requestAnalyzer->reveal());
        $requestListener->onRequest($event);

        $this->assertFalse($this->requestContext->hasParameter('prefix'));
        $this->assertFalse($this->requestContext->hasParameter('host'));
    }

    public function testRequestAnalyzerInternalRequest(): void
    {
        $this->portalInformation->getPrefix()->willReturn('test/');
        $this->portalInformation->getHost()->willReturn('sulu.io');
        $this->requestAnalyzer->getPortalInformation()->willReturn($this->portalInformation);

        // Context Hash is a Main Request https://github.com/FriendsOfSymfony/FOSHttpCache/blob/a582deb3f55f8a7efdae8ac916ef4adc285543a0/src/SymfonyCache/UserContextListener.php#L170
        // To avoid side effects to other requests we should not set the prefix and host in that case
        // Same is for /_sulu_target_group
        $request = Request::create('/_fos_user_context_hash');
        $request->attributes->set('internalRequest', true);
        $event = $this->createRequestEvent($request);

        $requestListener = new RequestListener($this->router->reveal(), $this->requestAnalyzer->reveal());
        $requestListener->onRequest($event);

        $this->assertFalse($this->requestContext->hasParameter('prefix'));
        $this->assertFalse($this->requestContext->hasParameter('host'));
    }

    private function createRequestEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent(
            $this->kernel->reveal(),
            $request,
            $requestType,
        );
    }
}

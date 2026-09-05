<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\EventListener\ParallelImageGenerationLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Semaphore\SemaphoreFactory;
use Symfony\Component\Semaphore\SemaphoreInterface;

class ParallelImageGenerationLimiterTest extends TestCase
{
    use ProphecyTrait;

    public function testImageProxyRequestWaitsForAFreeSlotAndReleasesItWithTheResponse(): void
    {
        $semaphore = $this->prophesize(SemaphoreInterface::class);
        $semaphore->acquire()->willReturn(false, false, true);
        $semaphore->release()->shouldBeCalledOnce();

        $semaphoreFactory = $this->prophesize(SemaphoreFactory::class);
        $semaphoreFactory->createSemaphore(ParallelImageGenerationLimiter::SEMAPHORE_RESOURCE, 3)
            ->willReturn($semaphore->reveal())
            ->shouldBeCalledOnce();

        $limiter = new ParallelImageGenerationLimiter($semaphoreFactory->reveal(), 3);
        $request = $this->createRequest(ParallelImageGenerationLimiter::IMAGE_PROXY_ROUTE);

        $limiter->onKernelRequest($this->createRequestEvent($request));
        $semaphore->release()->shouldNotHaveBeenCalled();

        $limiter->onKernelResponse($this->createResponseEvent($request));
        $semaphore->release()->shouldHaveBeenCalledOnce();

        // a second response for the same request must not release the slot twice
        $limiter->onKernelResponse($this->createResponseEvent($request));
        $semaphore->release()->shouldHaveBeenCalledOnce();
    }

    public function testOtherRoutesAreNotThrottled(): void
    {
        $semaphoreFactory = $this->prophesize(SemaphoreFactory::class);
        $semaphoreFactory->createSemaphore(Argument::cetera())->shouldNotBeCalled();

        $limiter = new ParallelImageGenerationLimiter($semaphoreFactory->reveal(), 3);
        $request = $this->createRequest('sulu_media.website.media.download');

        $limiter->onKernelRequest($this->createRequestEvent($request));
        $limiter->onKernelResponse($this->createResponseEvent($request));
    }

    public function testSubRequestsAreNotThrottled(): void
    {
        $semaphoreFactory = $this->prophesize(SemaphoreFactory::class);
        $semaphoreFactory->createSemaphore(Argument::cetera())->shouldNotBeCalled();

        $limiter = new ParallelImageGenerationLimiter($semaphoreFactory->reveal(), 3);
        $request = $this->createRequest(ParallelImageGenerationLimiter::IMAGE_PROXY_ROUTE);

        $limiter->onKernelRequest($this->createRequestEvent($request, HttpKernelInterface::SUB_REQUEST));
        $limiter->onKernelResponse($this->createResponseEvent($request, HttpKernelInterface::SUB_REQUEST));
    }

    public function testRequestFailsWhenNoSlotBecomesAvailableInTime(): void
    {
        $semaphore = $this->prophesize(SemaphoreInterface::class);
        $semaphore->acquire()->willReturn(false);
        $semaphore->release()->shouldNotBeCalled();

        $semaphoreFactory = $this->prophesize(SemaphoreFactory::class);
        $semaphoreFactory->createSemaphore(ParallelImageGenerationLimiter::SEMAPHORE_RESOURCE, 3)
            ->willReturn($semaphore->reveal());

        $limiter = new ParallelImageGenerationLimiter($semaphoreFactory->reveal(), 3, 0);
        $request = $this->createRequest(ParallelImageGenerationLimiter::IMAGE_PROXY_ROUTE);

        $this->expectException(ServiceUnavailableHttpException::class);

        $limiter->onKernelRequest($this->createRequestEvent($request));
    }

    private function createRequest(string $route): Request
    {
        $request = Request::create('/uploads/media/sulu-240x/01/photo.jpg');
        $request->attributes->set('_route', $route);

        return $request;
    }

    private function createRequestEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, $requestType);
    }

    private function createResponseEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        return new ResponseEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, $requestType, new Response());
    }
}

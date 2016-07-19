<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Tests\Unit\Handler;

use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Prophecy\Argument;
use Sulu\Component\HttpCache\Handler\UrlHandler;
use Sulu\Component\HttpCache\HandlerInterface;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UrlHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var ProxyClientInterface
     */
    private $proxyClient;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ReplacerInterface
     */
    private $replacer;

    /**
     * @var string
     */
    private $host = 'sulu.io';

    public function setUp()
    {
        $this->proxyClient = $this->prophesize(PurgeInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->request = $this->prophesize(Request::class);
        $this->replacer = $this->prophesize(ReplacerInterface::class);

        $this->request->getHost()->willReturn($this->host);
        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());

        $this->handler = new UrlHandler(
            $this->proxyClient->reveal(),
            $this->requestStack->reveal(),
            $this->replacer->reveal()
        );
    }

    public function testInvalidateRequestNull()
    {
        $this->requestStack->getCurrentRequest()->willReturn(null);
        $this->handler = new UrlHandler(
            $this->proxyClient->reveal(),
            $this->requestStack->reveal(),
            $this->replacer->reveal()
        );

        $url = 'sulu.io/path/to/1';

        $this->replacer->replaceHost(Argument::any())->shouldNotBeCalled();

        $this->proxyClient->purge($url, [])->shouldBeCalled();
        $this->proxyClient->flush()->shouldBeCalled();

        $this->handler->invalidatePath($url);
        $this->handler->flush();
    }

    public function testInvalidate()
    {
        $genericUrl = '{host}/path/to/1';
        $concreteUrl = 'sulu.io/path/to/1';

        $this->replacer->replaceHost($genericUrl, $this->host)->willReturn($concreteUrl);
        $this->proxyClient->purge($concreteUrl, [])->shouldBeCalled();
        $this->proxyClient->flush()->shouldBeCalled();

        $this->handler->invalidatePath($genericUrl);
        $this->handler->flush();
    }

    public function testInvalidateWithHeader()
    {
        $genericUrl = '{host}/path/to/1';
        $concreteUrl = 'sulu.io/path/to/1';
        $headers = ['X-Host' => 'example.com'];

        $this->replacer->replaceHost($genericUrl, $this->host)->willReturn($concreteUrl);
        $this->proxyClient->purge($concreteUrl, $headers)->shouldBeCalled();
        $this->proxyClient->flush()->shouldBeCalled();

        $this->handler->invalidatePath($genericUrl, $headers);
        $this->handler->flush();
    }
}

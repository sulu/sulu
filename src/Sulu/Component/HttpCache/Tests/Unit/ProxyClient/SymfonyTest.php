<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Tests\Unit\ProxyClient;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;
use Sulu\Component\HttpCache\ProxyClient\Invalidation\TagInterface;
use Sulu\Component\HttpCache\ProxyClient\Symfony;

class SymfonyTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->httpClient = $this->prophesize(Client::class);
        $this->request = $this->prophesize(Request::class);
    }

    /**
     * It should invalidate tags.
     */
    public function testInvalidateTags()
    {
        $method = 'POST';
        $url = 'http://localhost';
        $headers = [
            'X-Cache-Tags' => 'one,two',
        ];

        $this->request->getMethod()->willReturn($method);
        $this->request->getUrl()->willReturn($url);
        $this->request->getHeaders()->willReturn($headers);

        $this->httpClient->createRequest(
            $method,
            $url,
            $headers
        )->willReturn($this->request->reveal());

        $this->httpClient->send([
            $this->request->reveal(),
        ])->shouldBeCalled();

        $client = $this->createClient();
        $this->assertInstanceOf(TagInterface::class, $client);

        $client->invalidateTags(['one', 'two']);
        $client->flush();
    }

    /**
     * It should purge URLs.
     */
    public function testPurge()
    {
        $method = 'PURGE';
        $url = 'http://localhost/foo';

        $this->request->getMethod()->willReturn($method);
        $this->request->getUrl()->willReturn($url);
        $this->request->getHeaders()->willReturn([]);

        $this->httpClient->createRequest(
            $method,
            $url,
            []
        )->willReturn($this->request->reveal());

        $this->httpClient->send([
            $this->request->reveal(),
        ])->shouldBeCalled();

        $client = $this->createClient();
        $this->assertInstanceOf(TagInterface::class, $client);

        $client->purge('/foo');
        $client->flush();
    }

    private function createClient($invalidationUrls = null)
    {
        return new Symfony($invalidationUrls, $this->httpClient->reveal());
    }
}

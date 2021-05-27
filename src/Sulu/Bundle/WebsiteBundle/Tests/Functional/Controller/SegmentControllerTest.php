<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SegmentControllerTest extends WebsiteTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createWebsiteClient();
    }

    public function testSwitch(): void
    {
        $this->assertNull($this->client->getCookieJar()->get('_ss'));

        $this->client->request('GET', 'http://sulu.lo/_sulu_segment_switch?segment=s&url=http://sulu.lo/test');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(302, $response);
        $cookie = $this->client->getCookieJar()->get('_ss');
        $this->assertNotNull($cookie);
        $this->assertEquals('s', $cookie->getValue());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('http://sulu.lo/test', $response->getTargetUrl());
    }

    public function testSwitchNonExisting(): void
    {
        $this->assertNull($this->client->getCookieJar()->get('_ss'));

        $this->client->request('GET', 'http://sulu.lo/_sulu_segment_switch?segment=n&url=http://sulu.lo/test');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(302, $response);
        $this->assertNull($this->client->getCookieJar()->get('_ss'));
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('http://sulu.lo/test', $response->getTargetUrl());
    }

    public function testSwitchDefault(): void
    {
        $this->assertNull($this->client->getCookieJar()->get('_ss'));

        $this->client->request('GET', 'http://sulu.lo/_sulu_segment_switch?segment=w&url=http://sulu.lo/test');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(302, $response);
        $this->assertNull($this->client->getCookieJar()->get('_ss'));
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('http://sulu.lo/test', $response->getTargetUrl());
    }

    public function testSwitchFalseExternalUrl(): void
    {
        $this->assertNull($this->client->getCookieJar()->get('_ss'));

        $this->client->request('GET', 'http://sulu.lo/_sulu_segment_switch?segment=s&url=http://github.com/sulu/sulu');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(400, $response);
    }

    public function testSwitchFalseNoUrl(): void
    {
        $this->assertNull($this->client->getCookieJar()->get('_ss'));

        $this->client->request('GET', 'http://sulu.lo/_sulu_segment_switch?segment=s&url=');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(400, $response);
    }
}

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

    public function testSwitch()
    {
        $this->assertNull($this->client->getCookieJar()->get('_ss'));

        $this->client->request('GET', 'http://sulu.lo/_sulu_segment_switch?segment=s&url=http://sulu.lo/test');
        $response = $this->client->getResponse();

        $this->assertEquals('s', $this->client->getCookieJar()->get('_ss')->getValue());
        $this->assertEquals('http://sulu.lo/test', $response->getTargetUrl());
    }

    public function testSwitchNonExisting()
    {
        $this->assertNull($this->client->getCookieJar()->get('_ss'));

        $this->client->request('GET', 'http://sulu.lo/_sulu_segment_switch?segment=n&url=http://sulu.lo/test');
        $response = $this->client->getResponse();

        $this->assertNull($this->client->getCookieJar()->get('_ss'));
        $this->assertEquals('http://sulu.lo/test', $response->getTargetUrl());
    }

    public function testSwitchDefault()
    {
        $this->assertNull($this->client->getCookieJar()->get('_ss'));

        $this->client->request('GET', 'http://sulu.lo/_sulu_segment_switch?segment=w&url=http://sulu.lo/test');
        $response = $this->client->getResponse();

        $this->assertNull($this->client->getCookieJar()->get('_ss'));
        $this->assertEquals('http://sulu.lo/test', $response->getTargetUrl());
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Routing;

use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class PortalLoaderTest extends WebsiteTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = static::createWebsiteClient();
    }

    public function testPortalRoute()
    {
        $this->client->request('GET', 'http://example.lo/en/portal-route');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('GET', 'http://example.lo/de/portal-route');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('GET', 'http://example.lo/fr/portal-route');
        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $this->client->request('GET', 'http://example-english.lo/portal-route');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('GET', 'http://example-english.lo/valid-prefix/portal-route');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('GET', 'http://example-english.lo/invalid-prefix/portal-route');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }
}

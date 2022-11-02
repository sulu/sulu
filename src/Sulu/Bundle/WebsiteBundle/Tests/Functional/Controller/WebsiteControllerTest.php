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

class WebsiteControllerTest extends WebsiteTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createWebsiteClient();
        $this->initPhpcr();
    }

    public function testPage(): void
    {
        /* @var KernelBrowser $client */

        $this->client->request('GET', 'http://sulu.lo/');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
    }

    public function testPage406ForNotExistFormat(): void
    {
        $this->client->request('GET', 'http://sulu.lo/.xml');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(406, $response);
    }

    public function testPageClientAcceptHeaderNotUsed(): void
    {
        $this->client->request('GET', 'http://sulu.lo/', [], [], [
            'HTTP_ACCEPT' => 'text/plain',
        ]);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        // The accept header need to ignore so the http cache will have the correct content type in it
        $this->assertStringStartsWith(
            'text/html',
            $response->headers->get('Content-Type')
        );
    }
}

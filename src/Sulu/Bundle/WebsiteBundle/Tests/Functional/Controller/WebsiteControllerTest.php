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
    public function setUp(): void
    {
        $this->initPhpcr();
    }

    public function testPage()
    {
        /** @var KernelBrowser $client */
        $client = $this->createWebsiteClient();

        $client->request('GET', 'http://sulu.lo/');

        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);
    }

    public function testPage406ForNotExistFormat()
    {
        $client = $this->createWebsiteClient();
        $client->request('GET', 'http://sulu.lo/.xml');

        $response = $client->getResponse();
        $this->assertHttpStatusCode(406, $response);
    }

    public function testPageClientAcceptHeaderNotUsed()
    {
        $client = $this->createWebsiteClient();
        $client->request('GET', 'http://sulu.lo/', [], [], [
            'HTTP_ACCEPT' => 'text/plain',
        ]);

        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        // The accept header need to ignore so the http cache will have the correct content type in it
        $this->assertStringStartsWith(
            'text/html',
            $response->headers->get('Content-Type')
        );
    }
}

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

class ErrorControllerTest extends WebsiteTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createWebsiteClient();
    }

    public function test404ErrorTemplate(): void
    {
        $this->client->request('GET', 'http://sulu.lo/_error/404');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(404, $response);
        $this->assertStringContainsString('404 Error Template', $response->getContent());
    }

    public function testDefaultErrorTemplate(): void
    {
        $this->client->request('GET', 'http://sulu.lo/_error/500');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(500, $response);
        $this->assertStringContainsString('Default Error Template', $response->getContent());
    }
}

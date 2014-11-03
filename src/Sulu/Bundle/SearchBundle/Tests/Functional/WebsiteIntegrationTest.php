<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Functional;

class WebsiteIntegrationTest extends BaseTestCase
{
    protected $client;

    public function setUp()
    {
        parent::setUp();

        $this->indexStructure('Structure', '/structure');
        $this->client = $this->createClient();
    }

    public function testIntegration()
    {
        $this->client->request('GET', '/de/search?query=Structure');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

    }
}

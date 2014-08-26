<?php

namespace Sulu\Bundle\SearchBundle\Tests\Functional;

class WebsiteIntegrationTest extends BaseTestCase
{
    protected $client;

    public function setUp()
    {
        parent::setUp();

        $this->indexStructure('Structure', 'structure');
        $this->client = $this->createClient();
    }

    public function testIntegration()
    {
        $this->client->request('GET', '/de/search?query=Structure');
        $response = $this->client->getResponse();

        die($response->getContent());
    }
}

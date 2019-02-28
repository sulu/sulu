<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class SeoControllerTest extends SuluTestCase
{
    /**
     * @var SessionInterface
     */
    private $session;

    public function setUp()
    {
        parent::setUp();
        $this->session = $this->getContainer()->get('sulu_document_manager.default_session');
        $this->initPhpcr();
    }

    public function testPutAndGet()
    {
        $client = $this->createAuthenticatedClient();
        $webspaceUuid = $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier();

        $client->request('PUT', '/api/page-seos/' . $webspaceUuid . '?locale=en&webspace=sulu_io', [
            'title' => 'SEO Title',
        ]);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('SEO Title', $response->title);
        $this->assertEquals(false, $response->publishedState);
        $this->assertObjectHasAttribute('published', $response);

        $client->request('GET', '/api/page-seos/' . $webspaceUuid . '?locale=en&webspace=sulu_io');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('SEO Title', $response->title);
        $this->assertEquals(false, $response->publishedState);
        $this->assertObjectHasAttribute('published', $response);
    }

    public function testPutAndGetPublished()
    {
        $client = $this->createAuthenticatedClient();
        $webspaceUuid = $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier();

        $client->request('PUT', '/api/page-seos/' . $webspaceUuid . '?locale=en&webspace=sulu_io&action=publish', [
            'title' => 'SEO Title',
        ]);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('SEO Title', $response->title);
        $this->assertEquals(true, $response->publishedState);
        $this->assertObjectHasAttribute('published', $response);

        $client->request('GET', '/api/page-seos/' . $webspaceUuid . '?locale=en&webspace=sulu_io');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('SEO Title', $response->title);
        $this->assertEquals(true, $response->publishedState);
        $this->assertObjectHasAttribute('published', $response);
    }
}

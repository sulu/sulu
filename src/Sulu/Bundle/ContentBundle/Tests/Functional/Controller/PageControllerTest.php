<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Controller;

use PHPCR\SessionInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class PageControllerTest extends SuluTestCase
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

    public function testGetFlatResponseWithoutFieldsAndParent()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/pages?locale=en&flat=true');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(2, $response->_embedded->pages);

        $titles = array_map(function($page) {
            return $page->title;
        }, $response->_embedded->pages);

        $this->assertContains('Sulu CMF', $titles);
        $this->assertContains('Test CMF', $titles);
    }

    public function testGetFlatResponseForWebspace()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/pages?locale=en&flat=true&webspace=sulu_io');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(1, $response->_embedded->pages);
        $this->assertEquals('Sulu CMF', $response->_embedded->pages[0]->title);
    }

    public function testGetFlatResponseWithParentAndWithoutWebspace()
    {
        $client = $this->createAuthenticatedClient();

        $webspaceUuid = $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier();

        $client->request('GET', '/api/pages?locale=en&flat=true&parent=' . $webspaceUuid);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(0, $response->_embedded->pages);
    }

    public function testGetFlatResponseWithIds()
    {
        $client = $this->createAuthenticatedClient();

        $webspaceUuids = [
            $this->session->getNode('/cmf/test_io/contents')->getIdentifier(),
            $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier(),
        ];

        $client->request('GET', '/api/pages?locale=en&flat=true&ids=' . implode(',', $webspaceUuids));

        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(2, $response->_embedded->pages);
        $this->assertEquals('Homepage', $response->_embedded->pages[0]->title);
        $this->assertEquals('test_io', $response->_embedded->pages[0]->webspaceKey);
        $this->assertEquals('Homepage', $response->_embedded->pages[1]->title);
        $this->assertEquals('sulu_io', $response->_embedded->pages[1]->webspaceKey);
    }
}

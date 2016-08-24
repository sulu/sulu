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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * @group webtest
 */
class TemplateControllerTest extends SuluTestCase
{
    public $structureFactoryMock;

    public function testContentForm()
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/content/template/form/default.html');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals(1, $crawler->filter('form#content-form')->count());

        // foreach property one textfield
        $this->assertEquals(1, $crawler->filter('input#title')->count());
        $this->assertEquals(1, $crawler->filter('div#url')->count());
        $this->assertEquals(1, $crawler->filter('textarea#article')->count());
        // for tags 2
        $this->assertEquals(1, $crawler->filter('div#tags')->count());
    }

    public function testGetActionSorting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/content/template');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        for ($i = 0; $i < $response['total'] - 1; ++$i) {
            $this->assertLessThan(
                0,
                strcmp($response['_embedded'][$i]['title'], $response['_embedded'][$i + 1]['title'])
            );
        }
    }
}

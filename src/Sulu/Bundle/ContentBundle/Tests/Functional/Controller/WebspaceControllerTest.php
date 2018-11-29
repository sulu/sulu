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

class WebspaceControllerTest extends SuluTestCase
{
    public function testCgetAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/webspaces?locale=en');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertHttpStatusCode(200, $client->getResponse());

        $data = $response['_embedded']['webspaces'];
        $this->assertCount(3, $data);

        $destinationWebspace = $data[0];
        $suluWebspace = $data[1];
        $testWebspace = $data[2];

        $this->assertEquals('Destination CMF', $destinationWebspace['name']);
        $this->assertCount(2, $destinationWebspace['navigations']);
        $this->assertEquals('main', $destinationWebspace['navigations'][0]['key']);
        $this->assertEquals('footer', $destinationWebspace['navigations'][1]['key']);
        $this->assertEquals('Sulu CMF', $suluWebspace['name']);
        $this->assertCount(2, $suluWebspace['navigations']);
        $this->assertEquals('main', $suluWebspace['navigations'][0]['key']);
        $this->assertEquals('footer', $suluWebspace['navigations'][1]['key']);
        $this->assertEquals('Test CMF', $testWebspace['name']);
        $this->assertCount(2, $testWebspace['navigations']);
        $this->assertEquals('main', $testWebspace['navigations'][0]['key']);
        $this->assertEquals('footer', $testWebspace['navigations'][1]['key']);
    }
}

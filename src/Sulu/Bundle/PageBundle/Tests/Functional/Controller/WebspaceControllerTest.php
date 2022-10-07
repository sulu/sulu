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

class WebspaceControllerTest extends SuluTestCase
{
    public function testCgetAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/api/webspaces?locale=en');
        $response = \json_decode($client->getResponse()->getContent(), true);

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
        $this->assertCount(2, $destinationWebspace['allLocalizations']);
        $this->assertSame('de', $destinationWebspace['allLocalizations'][0]['name']);
        $this->assertSame('de', $destinationWebspace['allLocalizations'][0]['localization']);
        $this->assertSame('es', $destinationWebspace['allLocalizations'][1]['name']);
        $this->assertSame('es', $destinationWebspace['allLocalizations'][1]['localization']);
        $this->assertEquals('Sulu CMF', $suluWebspace['name']);
        $this->assertCount(2, $suluWebspace['navigations']);
        $this->assertEquals('main', $suluWebspace['navigations'][0]['key']);
        $this->assertEquals('footer', $suluWebspace['navigations'][1]['key']);
        $this->assertEquals('Test CMF', $testWebspace['name']);
        $this->assertCount(2, $testWebspace['navigations']);
        $this->assertEquals('main', $testWebspace['navigations'][0]['key']);
        $this->assertEquals('footer', $testWebspace['navigations'][1]['key']);
        $this->assertEquals('leaf', $testWebspace['resourceLocatorStrategy']['inputType']);
    }
}

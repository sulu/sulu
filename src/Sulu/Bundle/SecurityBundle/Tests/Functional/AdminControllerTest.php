<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    public function testUserMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/users');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('username', $response);
        $this->assertObjectHasAttribute('password', $response);
    }

    public function testRoleMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/roles');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('name', $response);
        $this->assertObjectHasAttribute('system', $response);
    }
}

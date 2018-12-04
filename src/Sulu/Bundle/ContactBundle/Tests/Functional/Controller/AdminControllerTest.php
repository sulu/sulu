<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    public function testContactMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/contacts');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('avatar', $response);
        $this->assertObjectHasAttribute('contact', $response);
    }

    public function testAccountMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/accounts');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('logo', $response);
        $this->assertObjectHasAttribute('account', $response);
    }
}

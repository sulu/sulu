<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    public function testCollectionMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/collection_details');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('title', $form);
        $this->assertObjectHasAttribute('description', $form);

        $schema = $response->schema;

        $this->assertEquals(['title'], $schema->required);
    }

    public function testMediaMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/media_details');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('title', $form);
        $this->assertObjectHasAttribute('description', $form);
        $this->assertObjectHasAttribute('license', $form);

        $schema = $response->schema;

        $this->assertEquals(['title'], $schema->required);
    }
}

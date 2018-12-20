<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    public function testMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/categories');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('name', $form);
        $this->assertObjectHasAttribute('key', $form);
        $this->assertObjectHasAttribute('description', $form);
        $this->assertObjectHasAttribute('medias', $form);

        $schema = $response->schema;

        $this->assertEquals(['name'], $schema->required);
    }
}

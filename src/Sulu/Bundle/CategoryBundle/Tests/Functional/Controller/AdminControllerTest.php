<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    public function testMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/category_details');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertTrue(\property_exists($form, 'name'));
        $this->assertTrue(\property_exists($form, 'key'));
        $this->assertTrue(\property_exists($form, 'description'));
        $this->assertTrue(\property_exists($form, 'medias'));

        $schema = $response->schema;

        $this->assertEquals(['name'], $schema->required);
    }
}

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

class AdminControllerTest extends SuluTestCase
{
    public function testPagesMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/pages');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $types = $response->types;

        // check for both types
        $this->assertObjectHasAttribute('default', $types);
        $this->assertObjectHasAttribute('overview', $types);

        $defaultType = $types->default;
        $this->assertObjectHasAttribute('name', $defaultType);
        $this->assertEquals('default', $defaultType->name);
        $this->assertObjectHasAttribute('title', $defaultType);
        $this->assertEquals('Standard page', $defaultType->title);
        $this->assertObjectHasAttribute('form', $defaultType);
        $this->assertObjectHasAttribute('title', $defaultType->form);
        $this->assertObjectHasAttribute('url', $defaultType->form);
        $this->assertEquals('sulu.rlp.part', $defaultType->form->title->tags[0]->name);
        $this->assertEquals(1, $defaultType->form->title->tags[0]->priority);

        $this->assertObjectHasAttribute('schema', $defaultType);
        $this->assertEquals(['title'], $defaultType->schema->required);

        $overviewType = $types->overview;
        $this->assertObjectHasAttribute('name', $overviewType);
        $this->assertEquals('overview', $overviewType->name);
        $this->assertObjectHasAttribute('title', $overviewType);
        $this->assertEquals('Overview', $overviewType->title);
        $this->assertObjectHasAttribute('form', $overviewType);
        $this->assertObjectHasAttribute('title', $overviewType->form);
        $this->assertObjectHasAttribute('tags', $overviewType->form);
        $this->assertObjectHasAttribute('url', $overviewType->form);
        $this->assertObjectHasAttribute('article', $overviewType->form);
        $this->assertObjectHasAttribute('schema', $overviewType);
        $this->assertNull($overviewType->schema);
    }

    public function testPageSeoMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/pages_seo');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('search_result', $form);
        $this->assertObjectHasAttribute('title', $form);
        $this->assertObjectHasAttribute('description', $form);

        $schema = $response->schema;

        $this->assertNull($schema);
    }

    public function testPageExcerptMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/pages_excerpt');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('title', $form);
        $this->assertObjectHasAttribute('more', $form);
        $this->assertObjectHasAttribute('description', $form);

        $schema = $response->schema;

        $this->assertNull($schema);
    }

    public function testPageSettingMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/pages_settings');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('navContexts', $form);
        $this->assertObjectHasAttribute('pageType', $form);
        $this->assertObjectHasAttribute('shadowPage', $form);

        $schema = $response->schema;

        $this->assertCount(2, $schema->allOf);
        $this->assertEquals(['nodeType'], $schema->allOf[0]->required);
    }
}

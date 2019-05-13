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

class AdminControllerTest extends SuluTestCase
{
    public function testTeaserConfig()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $pageConfig = $response->sulu_page;

        $this->assertCount(1, (array) $pageConfig->teaser);
        $this->assertEquals('Page', $pageConfig->teaser->pages->title);
        $this->assertEquals('pages', $pageConfig->teaser->pages->resourceKey);
    }

    public function testPagesListMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/list/pages');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('id', $response);
        $this->assertObjectHasAttribute('title', $response);
        $this->assertObjectHasAttribute('order', $response);
        $this->assertObjectHasAttribute('published', $response);

        $this->assertEquals('ID', $response->id->label);
        $this->assertEquals('string', $response->id->type);
    }

    public function testPagesFormMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/page');

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

        $this->assertEquals(['required' => []], (array) $overviewType->schema);
    }

    public function testPageSeoFormMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/page_seo');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('search_result', $form);
        $this->assertObjectHasAttribute('ext/seo/title', $form);
        $this->assertObjectHasAttribute('ext/seo/description', $form);

        $schema = $response->schema;

        $this->assertEquals(['required' => []], (array) $schema);
    }

    public function testPageExcerptFormMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/page_excerpt');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('ext/excerpt/title', $form);
        $this->assertObjectHasAttribute('ext/excerpt/more', $form);
        $this->assertObjectHasAttribute('ext/excerpt/description', $form);

        $schema = $response->schema;

        $this->assertEquals(['required' => []], (array) $schema);
    }

    public function testPageSettingFormMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/page_settings');

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

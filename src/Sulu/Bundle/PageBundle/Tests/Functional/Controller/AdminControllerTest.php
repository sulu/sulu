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
    public function testRouteConfig(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->jsonRequest('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $routeConfig = $response->sulu_admin->routes;

        $formRoute = null;
        foreach ($routeConfig as $route) {
            if ('sulu_page.page_add_form.details' === $route->name) {
                $formRoute = $route;
                break;
            }
        }

        $this->assertEquals('Edit', $formRoute->options->toolbarActions[3]->options->label);
    }

    public function testTeaserConfig(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->jsonRequest('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $pageConfig = $response->sulu_page;

        $this->assertCount(1, (array) $pageConfig->teaser);
        $this->assertEquals('Page', $pageConfig->teaser->pages->title);
        $this->assertEquals('pages', $pageConfig->teaser->pages->resourceKey);
    }

    public function testSmartContentConfig(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->jsonRequest('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $adminConfig = $response->sulu_admin;

        $this->assertTrue(\property_exists($adminConfig->smartContent, 'pages'));
        $this->assertEquals('sulu_page.page_edit_form', $adminConfig->smartContent->pages->view);
        $this->assertEquals(
            ['id' => 'id', 'webspace' => 'webspace'],
            (array) $adminConfig->smartContent->pages->resultToView
        );
    }

    public function testWebspacesConfig(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->jsonRequest('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $pageConfig = $response->sulu_page;

        $this->assertCount(3, (array) $pageConfig->webspaces);
        $this->assertEquals('destination_io', $pageConfig->webspaces->destination_io->key);
        $this->assertEquals('sulu_io', $pageConfig->webspaces->sulu_io->key);
        $this->assertEquals('test_io', $pageConfig->webspaces->test_io->key);
        $this->assertEquals('test_security_system', $pageConfig->webspaces->test_io->security->system);
        $this->assertEquals(true, $pageConfig->webspaces->test_io->security->permissionCheck);

        $this->assertEquals('en', $pageConfig->webspaces->test_io->localizations[0]->language);
        $this->assertTrue($pageConfig->webspaces->test_io->localizations[0]->default);
        $this->assertEquals('de', $pageConfig->webspaces->test_io->localizations[1]->language);
        $this->assertFalse($pageConfig->webspaces->test_io->localizations[1]->default);

        $this->assertEquals('w', $pageConfig->webspaces->test_io->segments[0]->key);
        $this->assertEquals('Winter', $pageConfig->webspaces->test_io->segments[0]->title);
        $this->assertSame(false, $pageConfig->webspaces->test_io->segments[0]->default);
        $this->assertEquals('s', $pageConfig->webspaces->test_io->segments[1]->key);
        $this->assertEquals('Summer', $pageConfig->webspaces->test_io->segments[1]->title);
        $this->assertSame(true, $pageConfig->webspaces->test_io->segments[1]->default);

        $this->assertEquals('Destination CMF', $pageConfig->webspaces->destination_io->name);
        $this->assertEquals('default', $pageConfig->webspaces->destination_io->defaultTemplates->page);
        $this->assertEquals('overview', $pageConfig->webspaces->destination_io->defaultTemplates->homepage);
        $this->assertEquals('main', $pageConfig->webspaces->destination_io->navigations[0]->key);
        $this->assertEquals('footer', $pageConfig->webspaces->destination_io->navigations[1]->key);
        $this->assertEquals('leaf', $pageConfig->webspaces->destination_io->resourceLocatorStrategy->inputType);
        $this->assertEquals([], $pageConfig->webspaces->destination_io->customUrls);
    }

    public function testPagesListMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/list/pages');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $this->assertTrue(\property_exists($response, 'id'));
        $this->assertTrue(\property_exists($response, 'title'));
        $this->assertTrue(\property_exists($response, 'published'));

        $this->assertEquals('ID', $response->id->label);
        $this->assertEquals('string', $response->id->type);
    }

    public function testPagesFormMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/page');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $types = $response->types;

        $this->assertTrue(\property_exists($types, 'default'));
        $this->assertTrue(\property_exists($types, 'overview'));
        $this->assertTrue(\property_exists($types, 'blocks'));

        $defaultType = $types->default;
        $this->assertTrue(\property_exists($defaultType, 'name'));
        $this->assertEquals('default', $defaultType->name);
        $this->assertTrue(\property_exists($defaultType, 'title'));
        $this->assertEquals('Standard page', $defaultType->title);
        $this->assertTrue(\property_exists($defaultType, 'form'));
        $this->assertTrue(\property_exists($defaultType->form, 'title'));
        $this->assertTrue(\property_exists($defaultType->form, 'url'));
        $this->assertEquals('sulu.rlp.part', $defaultType->form->title->tags[0]->name);
        $this->assertEquals(1, $defaultType->form->title->tags[0]->priority);
        $this->assertTrue(\property_exists($defaultType, 'schema'));
        $this->assertEquals(['title'], $defaultType->schema->required);

        $overviewType = $types->overview;
        $this->assertTrue(\property_exists($overviewType, 'name'));
        $this->assertEquals('overview', $overviewType->name);
        $this->assertTrue(\property_exists($overviewType, 'title'));
        $this->assertEquals('Overview', $overviewType->title);
        $this->assertTrue(\property_exists($overviewType, 'form'));
        $this->assertTrue(\property_exists($overviewType->form, 'title'));
        $this->assertTrue(\property_exists($overviewType->form, 'tags'));
        $this->assertTrue(\property_exists($overviewType->form, 'url'));
        $this->assertTrue(\property_exists($overviewType->form, 'article'));
        $this->assertTrue(\property_exists($overviewType, 'schema'));
        $this->assertCount(5, (array) $overviewType->schema->properties);
        $this->assertEquals('array', $overviewType->schema->properties->block->type);

        $this->assertCount(2, $overviewType->schema->properties->block->items->allOf);
        $this->assertEquals(['type'], $overviewType->schema->properties->block->items->allOf[0]->if->required);
        $this->assertCount(1, (array) $overviewType->schema->properties->block->items->allOf[0]->if->properties);
        $this->assertEquals('type1', $overviewType->schema->properties->block->items->allOf[0]->if->properties->type->const);
        $this->assertEquals(['title'], $overviewType->schema->properties->block->items->allOf[0]->then->required);
        $this->assertEquals(['type'], $overviewType->schema->properties->block->items->allOf[1]->if->required);
        $this->assertCount(1, (array) $overviewType->schema->properties->block->items->allOf[1]->if->properties);
        $this->assertEquals('type2', $overviewType->schema->properties->block->items->allOf[1]->if->properties->type->const);
        $this->assertEquals(['image'], $overviewType->schema->properties->block->items->allOf[1]->then->required);

        $blocksType = $types->blocks;
        $this->assertEquals(1, $blocksType->form->block->minOccurs);
        $this->assertEquals(5, $blocksType->form->block->maxOccurs);
        $this->assertEquals(2, $blocksType->form->block->types->article->form->lines->minOccurs);
        $this->assertEquals(2, $blocksType->form->block->types->article->form->lines->maxOccurs);

        $smartContentType = $types->smartcontent;
        $smartContentOptions = $smartContentType->form->smart_content->options;
        $this->assertEquals('properties', $smartContentOptions->properties->name);
        $this->assertEquals('collection', $smartContentOptions->properties->type);
        $this->assertCount(5, $smartContentOptions->properties->value);
        $this->assertEquals('article', $smartContentOptions->properties->value[0]->name);
        $this->assertEquals('article', $smartContentOptions->properties->value[0]->value);
        $this->assertEquals('excerptTitle', $smartContentOptions->properties->value[1]->name);
        $this->assertEquals('excerpt.title', $smartContentOptions->properties->value[1]->value);
        $this->assertEquals('excerptTags', $smartContentOptions->properties->value[2]->name);
        $this->assertEquals('excerpt.tags', $smartContentOptions->properties->value[2]->value);
        $this->assertEquals('excerptImages', $smartContentOptions->properties->value[3]->name);
        $this->assertEquals('excerpt.images', $smartContentOptions->properties->value[3]->value);
        $this->assertEquals('excerptDescription', $smartContentOptions->properties->value[4]->name);
        $this->assertEquals('excerpt.description', $smartContentOptions->properties->value[4]->value);
    }

    public function testPageSeoFormMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/page_seo');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertTrue(\property_exists($form, 'search_result'));
        $this->assertTrue(\property_exists($form, 'ext/seo/title'));
        $this->assertTrue(\property_exists($form, 'ext/seo/description'));

        $this->assertTrue(\property_exists($response->schema, 'properties'));
    }

    public function testPageExcerptFormMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/page_excerpt?webspace=sulu_io');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertTrue(\property_exists($form, 'ext/excerpt/title'));
        $this->assertTrue(\property_exists($form, 'ext/excerpt/more'));
        $this->assertTrue(\property_exists($form, 'ext/excerpt/description'));
        $this->assertTrue(\property_exists($form, 'ext/excerpt/segments'));

        $this->assertTrue(\property_exists($response->schema, 'properties'));
    }

    public function testPageSettingFormMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/page_settings');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertTrue(\property_exists($form, 'navContexts'));
        $this->assertTrue(\property_exists($form, 'pageType'));
        $this->assertTrue(\property_exists($form, 'shadowPage'));

        $schema = $response->schema;

        $this->assertCount(2, $schema->allOf);
        $this->assertEquals(['nodeType'], $schema->allOf[0]->required);
    }
}

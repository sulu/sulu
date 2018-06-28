<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function setUp()
    {
        $this->purgeDatabase();
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());
    }

    public function testGetConfig()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/config');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('sulu_admin', $response);
        $this->assertObjectHasAttribute('navigation', $response->sulu_admin);
        $this->assertObjectHasAttribute('resourceMetadataEndpoints', $response->sulu_admin);
        $this->assertObjectHasAttribute('routes', $response->sulu_admin);
        $this->assertObjectHasAttribute('fieldTypeOptions', $response->sulu_admin);
        $this->assertInternalType('array', $response->sulu_admin->navigation);
        $this->assertInternalType('array', $response->sulu_admin->routes);
        $this->assertInternalType('object', $response->sulu_admin->resourceMetadataEndpoints);
    }

    public function testGetResourcePages()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/resources/pages');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $resource = json_decode($client->getResponse()->getContent());

        // check for datagrid
        $this->assertObjectHasAttribute('datagrid', $resource);
        $this->assertObjectHasAttribute('id', $resource->datagrid);

        $this->assertObjectHasAttribute('name', $resource->datagrid->id);
        $this->assertObjectHasAttribute('label', $resource->datagrid->id);
        $this->assertObjectHasAttribute('type', $resource->datagrid->id);

        $this->assertEquals('id', $resource->datagrid->id->name);
        $this->assertEquals('ID', $resource->datagrid->id->label);
        $this->assertEquals('string', $resource->datagrid->id->type);

        // check for types
        $this->assertObjectHasAttribute('types', $resource);

        // check for both types
        $this->assertObjectHasAttribute('default', $resource->types);
        $this->assertObjectHasAttribute('overview', $resource->types);

        // check if type 'default' has it's needed attributes
        $this->assertObjectHasAttribute('name', $resource->types->default);
        $this->assertEquals('default', $resource->types->default->name);
        $this->assertObjectHasAttribute('title', $resource->types->default);
        $this->assertEquals('Animals', $resource->types->default->title);
        $this->assertObjectHasAttribute('form', $resource->types->default);
        // check if form has all fields
        $this->assertObjectHasAttribute('title', $resource->types->default->form);
        $this->assertObjectHasAttribute('url', $resource->types->default->form);
        $this->assertObjectHasAttribute('animals', $resource->types->default->form);
        $this->assertObjectHasAttribute('blog', $resource->types->default->form);
        $this->assertObjectHasAttribute('localized_blog', $resource->types->default->form);
        // check if form has tags
        $this->assertEquals('sulu.rlp.part', $resource->types->default->form->title->tags[0]->name);
        $this->assertEquals(100, $resource->types->default->form->title->tags[0]->priority);
        // check field "animals"
        $this->assertObjectHasAttribute('label', $resource->types->default->form->animals);
        $this->assertObjectHasAttribute('type', $resource->types->default->form->animals);
        $this->assertObjectHasAttribute('required', $resource->types->default->form->animals);
        $this->assertObjectHasAttribute('options', $resource->types->default->form->animals);
        $this->assertEquals('Animals', $resource->types->default->form->animals->label);
        $this->assertEquals('snippet', $resource->types->default->form->animals->type);
        $this->assertEquals(false, $resource->types->default->form->animals->required);
        $this->assertObjectHasAttribute('snippetType', $resource->types->default->form->animals->options);
        $this->assertObjectHasAttribute('name', $resource->types->default->form->animals->options->snippetType);
        $this->assertObjectHasAttribute('value', $resource->types->default->form->animals->options->snippetType);
        $this->assertEquals('snippetType', $resource->types->default->form->animals->options->snippetType->name);
        $this->assertEquals('animal', $resource->types->default->form->animals->options->snippetType->value);
        // check if schema is valid
        $this->assertObjectHasAttribute('schema', $resource->types->default);
        $this->assertObjectHasAttribute('required', $resource->types->default->schema);

        // check if type 'overview' has it's needed attributes
        $this->assertObjectHasAttribute('name', $resource->types->overview);
        $this->assertEquals('overview', $resource->types->overview->name);
        $this->assertObjectHasAttribute('title', $resource->types->overview);
        $this->assertEquals('Overview', $resource->types->overview->title);
        $this->assertObjectHasAttribute('form', $resource->types->overview);
        // check if form has all fields
        $this->assertObjectHasAttribute('title', $resource->types->overview->form);
        $this->assertObjectHasAttribute('tags', $resource->types->overview->form);
        $this->assertObjectHasAttribute('url', $resource->types->overview->form);
        $this->assertObjectHasAttribute('article', $resource->types->overview->form);
        $this->assertObjectHasAttribute('blog', $resource->types->overview->form);
        $this->assertObjectHasAttribute('external', $resource->types->overview->form);
        // check if schema is valid
        $this->assertObjectHasAttribute('schema', $resource->types->overview);
        $this->assertEquals(['title', 'url'], $resource->types->overview->schema->required);
    }

    public function testGetResourceContacts()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/resources/contacts');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $resource = json_decode($client->getResponse()->getContent());

        // check for datagrid
        $this->assertObjectHasAttribute('datagrid', $resource);
        $this->assertObjectHasAttribute('id', $resource->datagrid);
        $this->assertObjectHasAttribute('title', $resource->datagrid);
        $this->assertObjectHasAttribute('account', $resource->datagrid);
        $this->assertObjectHasAttribute('firstName', $resource->datagrid);

        // check for form
        $this->assertObjectHasAttribute('form', $resource);
        $contactForm = $resource->form->contact->items;
        $this->assertObjectHasAttribute('formOfAddress', $contactForm);
        $this->assertObjectHasAttribute('firstName', $contactForm);
        $this->assertObjectHasAttribute('lastName', $contactForm);
        $this->assertObjectHasAttribute('salutation', $contactForm);

        // check for schema
        $this->assertObjectHasAttribute('schema', $resource);
        $this->assertObjectHasAttribute('required', $resource->schema);
    }

    public function testGetResourceAccounts()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/resources/accounts');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $resource = json_decode($client->getResponse()->getContent());

        // check for datagrid
        $this->assertObjectHasAttribute('datagrid', $resource);
        $this->assertObjectHasAttribute('id', $resource->datagrid);
        $this->assertObjectHasAttribute('name', $resource->datagrid);
        $this->assertObjectHasAttribute('zip', $resource->datagrid);
        $this->assertObjectHasAttribute('city', $resource->datagrid);

        // check for form
        $this->assertObjectHasAttribute('form', $resource);
        $accountForm = $resource->form->account->items;
        $this->assertObjectHasAttribute('name', $accountForm);

        // check for schema
        $this->assertObjectHasAttribute('schema', $resource);
        $this->assertObjectHasAttribute('required', $resource->schema);
    }
}

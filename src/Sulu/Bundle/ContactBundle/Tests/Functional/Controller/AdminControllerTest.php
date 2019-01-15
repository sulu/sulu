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

use Doctrine\ORM\Mapping\ClassMetadata;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    public function setUp()
    {
        $this->purgeDatabase();
        $em = $this->getEntityManager();
        //
        // force id = 1
        $metadata = $em->getClassMetaData(CollectionType::class);
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $collectionType1 = new CollectionType();
        $collectionType1->setId(1);
        $collectionType1->setName('Default Collection Type');
        $collectionType1->setKey('collection.default');

        $collectionType2 = new CollectionType();
        $collectionType2->setId(2);
        $collectionType2->setName('System Collections');
        $collectionType2->setKey('collection.system');

        $em->persist($collectionType1);
        $em->persist($collectionType2);
        $em->flush();
    }

    public function testContactsDatagridMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/datagrid/contacts');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('id', $response);
        $this->assertObjectHasAttribute('title', $response);
        $this->assertObjectHasAttribute('account', $response);
        $this->assertObjectHasAttribute('firstName', $response);
    }

    public function testAccountsDatagridMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/datagrid/accounts');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('id', $response);
        $this->assertObjectHasAttribute('name', $response);
        $this->assertObjectHasAttribute('zip', $response);
        $this->assertObjectHasAttribute('city', $response);
    }

    public function testContactFormMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/contact_details');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('avatar', $form);
        $this->assertObjectHasAttribute('contact', $form);

        $schema = $response->schema;

        $this->assertEquals(['firstName', 'lastName', 'formOfAddress'], $schema->required);
    }

    public function testAccountFormMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/account_details');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('logo', $form);
        $this->assertObjectHasAttribute('account', $form);

        $schema = $response->schema;

        $this->assertEquals(['name'], $schema->required);
    }
}

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
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Country;
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

    public function testContactsConfig()
    {
        $em = $this->getEntityManager();

        $addressType1 = new AddressType();
        $addressType1->setName('work');

        $addressType2 = new AddressType();
        $addressType2->setName('private');

        $country = new Country();
        $country->setName('Austria');
        $country->setCode('AT');

        $em->persist($addressType1);
        $em->persist($addressType2);
        $em->persist($country);
        $em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $contactConfig = $response->sulu_contact;

        $this->assertEquals($addressType1->getId(), $contactConfig->addressTypes[0]->id);
        $this->assertEquals('work', $contactConfig->addressTypes[0]->name);
        $this->assertEquals($addressType2->getId(), $contactConfig->addressTypes[1]->id);
        $this->assertEquals('private', $contactConfig->addressTypes[1]->name);
        $this->assertEquals($country->getId(), $contactConfig->countries[0]->id);
        $this->assertEquals('Austria', $contactConfig->countries[0]->name);
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

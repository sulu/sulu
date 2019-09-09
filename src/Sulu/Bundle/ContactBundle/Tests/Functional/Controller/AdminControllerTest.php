<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Controller;

use Doctrine\ORM\Mapping\ClassMetadata;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfileType;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    public function setUp(): void
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
        $addressType1->setName('work address');
        $addressType2 = new AddressType();
        $addressType2->setName('private address');

        $phoneType1 = new PhoneType();
        $phoneType1->setName('work phone');
        $phoneType2 = new PhoneType();
        $phoneType2->setName('private phone');

        $emailType1 = new EmailType();
        $emailType1->setName('work email');
        $emailType2 = new EmailType();
        $emailType2->setName('private email');

        $urlType1 = new UrlType();
        $urlType1->setName('work url');
        $urlType2 = new UrlType();
        $urlType2->setName('private url');

        $socialMediaProfileType1 = new SocialMediaProfileType();
        $socialMediaProfileType1->setName('Facebook');
        $socialMediaProfileType2 = new SocialMediaProfileType();
        $socialMediaProfileType2->setName('Twitter');

        $faxType1 = new FaxType();
        $faxType1->setName('work fax');
        $faxType2 = new FaxType();
        $faxType2->setName('private fax');

        $country = new Country();
        $country->setName('Austria');
        $country->setCode('AT');

        $em->persist($addressType1);
        $em->persist($addressType2);
        $em->persist($phoneType1);
        $em->persist($phoneType2);
        $em->persist($emailType1);
        $em->persist($emailType2);
        $em->persist($urlType1);
        $em->persist($urlType2);
        $em->persist($socialMediaProfileType1);
        $em->persist($socialMediaProfileType2);
        $em->persist($faxType1);
        $em->persist($faxType2);
        $em->persist($country);
        $em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $contactConfig = $response->sulu_contact;

        $this->assertEquals($addressType1->getId(), $contactConfig->addressTypes[0]->id);
        $this->assertEquals('work address', $contactConfig->addressTypes[0]->name);
        $this->assertEquals($addressType2->getId(), $contactConfig->addressTypes[1]->id);
        $this->assertEquals('private address', $contactConfig->addressTypes[1]->name);

        $this->assertEquals($phoneType1->getId(), $contactConfig->phoneTypes[0]->id);
        $this->assertEquals('work phone', $contactConfig->phoneTypes[0]->name);
        $this->assertEquals($phoneType2->getId(), $contactConfig->phoneTypes[1]->id);
        $this->assertEquals('private phone', $contactConfig->phoneTypes[1]->name);

        $this->assertEquals($emailType1->getId(), $contactConfig->emailTypes[0]->id);
        $this->assertEquals('work email', $contactConfig->emailTypes[0]->name);
        $this->assertEquals($emailType2->getId(), $contactConfig->emailTypes[1]->id);
        $this->assertEquals('private email', $contactConfig->emailTypes[1]->name);

        $this->assertEquals($urlType1->getId(), $contactConfig->websiteTypes[0]->id);
        $this->assertEquals('work url', $contactConfig->websiteTypes[0]->name);
        $this->assertEquals($urlType2->getId(), $contactConfig->websiteTypes[1]->id);
        $this->assertEquals('private url', $contactConfig->websiteTypes[1]->name);

        $this->assertEquals($socialMediaProfileType1->getId(), $contactConfig->socialMediaTypes[0]->id);
        $this->assertEquals('Facebook', $contactConfig->socialMediaTypes[0]->name);
        $this->assertEquals($socialMediaProfileType2->getId(), $contactConfig->socialMediaTypes[1]->id);
        $this->assertEquals('Twitter', $contactConfig->socialMediaTypes[1]->name);

        $this->assertEquals($faxType1->getId(), $contactConfig->faxTypes[0]->id);
        $this->assertEquals('work fax', $contactConfig->faxTypes[0]->name);
        $this->assertEquals($faxType2->getId(), $contactConfig->faxTypes[1]->id);
        $this->assertEquals('private fax', $contactConfig->faxTypes[1]->name);

        $this->assertEquals($country->getId(), $contactConfig->countries[0]->id);
        $this->assertEquals('Austria', $contactConfig->countries[0]->name);
    }

    public function testContactsListMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/list/contacts');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('id', $response);
        $this->assertObjectHasAttribute('title', $response);
        $this->assertObjectHasAttribute('account', $response);
        $this->assertObjectHasAttribute('firstName', $response);
    }

    public function testAccountsListMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/list/accounts');

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

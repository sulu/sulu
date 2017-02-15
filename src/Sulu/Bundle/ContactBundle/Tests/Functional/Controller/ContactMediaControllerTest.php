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

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ContactMediaControllerTest extends SuluTestCase
{
    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->initOrm();
    }

    public function initOrm()
    {
        $this->purgeDatabase();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setPosition('CEO');
        $contact->setFormOfAddress(1);
        $contact->setSalutation('Sehr geehrter Herr Dr Mustermann');

        $this->contact = $contact;

        $title = new ContactTitle();
        $title->setTitle('MSc');

        $contact->setTitle($title);

        $position = new Position();
        $position->setPosition('Manager');

        $account = new Account();
        $account->setLft(0);
        $account->setRgt(1);
        $account->setDepth(0);
        $account->setName('Musterfirma');

        $account1 = new Account();
        $account1->setLft(0);
        $account1->setRgt(1);
        $account1->setDepth(0);
        $account1->setName('Musterfirma');

        $phoneType = new PhoneType();
        $phoneType->setName('Private');

        $phone = new Phone();
        $phone->setPhone('123456789');
        $phone->setPhoneType($phoneType);

        $contact->addPhone($phone);

        $emailType = new EmailType();
        $emailType->setName('Private');

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);

        $contact->addEmail($email);

        $faxType = new FaxType();
        $faxType->setName('Private');

        $this->faxType = $faxType;

        $fax = new Fax();
        $fax->setFax('123654789');
        $fax->setFaxType($faxType);
        $contact->addFax($fax);

        $country1 = new Country();
        $country1->setName('Musterland');
        $country1->setCode('ML');

        $country2 = new Country();
        $country2->setName('United States');
        $country2->setCode('US');

        $addressType = new AddressType();
        $addressType->setName('Private');

        $address = new Address();
        $address->setStreet('Musterstraße');
        $address->setNumber('1');
        $address->setZip('0000');
        $address->setCity('Musterstadt');
        $address->setState('Musterland');
        $address->setCountry($country1);
        $address->setAddressType($addressType);
        $address->setBillingAddress(true);
        $address->setPrimaryAddress(true);
        $address->setDeliveryAddress(false);
        $address->setPostboxCity('Dornbirn');
        $address->setPostboxPostcode('6850');
        $address->setPostboxNumber('4711');

        $contactAddress = new ContactAddress();
        $contactAddress->setAddress($address);
        $contactAddress->setContact($contact);
        $contactAddress->setMain(true);

        $contact->addContactAddress($contactAddress);
        $address->addContactAddress($contactAddress);

        $note = new Note();
        $note->setValue('Note');
        $contact->addNote($note);

        $this->setUpMediaEntities($contact);

        $this->em->persist($contact);
        $this->em->persist($title);
        $this->em->persist($position);
        $this->em->persist($account);
        $this->em->persist($account1);
        $this->em->persist($phoneType);
        $this->em->persist($phone);
        $this->em->persist($faxType);
        $this->em->persist($fax);
        $this->em->persist($emailType);
        $this->em->persist($email);
        $this->em->persist($country1);
        $this->em->persist($country2);
        $this->em->persist($addressType);
        $this->em->persist($contactAddress);
        $this->em->persist($address);
        $this->em->persist($note);

        $this->em->flush();
    }

    public function setUpMediaEntities($contact)
    {
        $mediaType = new MediaType();
        $mediaType->setName('document');
        $mediaType->setDescription('This is a document');

        $imageType = new MediaType();
        $imageType->setName('image');
        $imageType->setDescription('This is an image');

        $videoType = new MediaType();
        $videoType->setName('video');
        $videoType->setDescription('This is a video');

        $audioType = new MediaType();
        $audioType->setName('audio');
        $audioType->setDescription('This is an audio');

        $media = new Media();
        $media->setType($imageType);

        $this->media = $media;

        $media2 = new Media();
        $media2->setType($imageType);

        $this->media2 = $media2;

        $contact->addMedia($media2);

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);

        $file2 = new File();
        $file2->setVersion(1);
        $file2->setMedia($media2);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('photo.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setStorageOptions('{"segment":"1","fileName":"photo.jpeg"}');
        $file->addFileVersion($fileVersion);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('photo.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file2);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setStorageOptions('{"segment":"1","fileName":"photo.jpeg"}');
        $file2->addFileVersion($fileVersion);

        $collection = new Collection();
        $this->setUpCollection($collection);

        $media->setCollection($collection);
        $media2->setCollection($collection);
        $this->em->persist($media);
        $this->em->persist($media2);
        $this->em->persist($collection);
        $this->em->persist($file);
        $this->em->persist($file2);
        $this->em->persist($videoType);
        $this->em->persist($imageType);
        $this->em->persist($audioType);
        $this->em->persist($mediaType);
    }

    public function setUpCollection(&$collection)
    {
        $style = [
            'type' => 'circle',
            'color' => '#ffcc00',
        ];

        $collection->setStyle(json_encode($style));

        // Create Collection Type
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');

        $collection->setType($collectionType);

        // Collection Meta 1
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');
        $collectionMeta->setCollection($collection);

        $collection->addMeta($collectionMeta);

        // Collection Meta 2
        $collectionMeta2 = new CollectionMeta();
        $collectionMeta2->setTitle('Test Kollektion');
        $collectionMeta2->setDescription('Dies ist eine Test Beschreibung');
        $collectionMeta2->setLocale('de');
        $collectionMeta2->setCollection($collection);

        $collection->addMeta($collectionMeta2);

        $this->em->persist($collection);
        $this->em->persist($collectionType);
        $this->em->persist($collectionMeta);
        $this->em->persist($collectionMeta2);
    }

    public function testGetList()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/contacts/' . $this->contact->getId() . '/medias?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals($this->media2->getId(), $response->_embedded->media[0]->id);
        $this->assertObjectHasAttribute('thumbnails', $response->_embedded->media[0]);
        $this->assertObjectHasAttribute('sulu-100x100', $response->_embedded->media[0]->thumbnails);
        $this->assertTrue(is_string($response->_embedded->media[0]->thumbnails->{'sulu-100x100'}));
    }

    public function testContactMediaPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));

        $client->request(
            'POST',
            '/api/contacts/' . $this->contact->getId() . '/medias',
            [
                'mediaId' => $this->media->getId(),
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertNotNull($response->id);

        $client->request(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(2, count($response->medias));

        $this->assertNotNull($response->medias[0]->id);
        $this->assertNotNull($response->medias[1]->id);
    }

    public function testContactMediaPostNotExistingMedia()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));

        $client->request(
            'POST',
            '/api/contacts/' . $this->contact->getId() . '/medias',
            [
                'mediaId' => 99,
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));
    }

    public function testContactMediaDelete()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/api/contacts/' . $this->contact->getId() . '/medias/' . $this->media2->getId()
        );

        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));
    }

    public function testContactMediaDeleteNotExistingRelation()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/contacts/' . $this->contact->getId() . '/medias/99'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));
    }
}

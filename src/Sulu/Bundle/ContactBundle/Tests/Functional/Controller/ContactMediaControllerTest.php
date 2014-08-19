<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Controller;

use DateTime;
use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Sulu\Bundle\ContactBundle\Entity\Activity;
use Sulu\Bundle\ContactBundle\Entity\ActivityPriority;
use Sulu\Bundle\ContactBundle\Entity\ActivityStatus;
use Sulu\Bundle\ContactBundle\Entity\ActivityType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Entity\File;

class ContactMediaControllerTest extends DatabaseTestCase
{
    private $contactPosition = null;
    private $contactTitle = null;
    /**
     * @var array
     */
    protected static $entities;

    public function setUp()
    {
        $this->setUpSchema();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setPosition('CEO');
        $contact->setCreated(new DateTime());
        $contact->setChanged(new DateTime());
        $contact->setFormOfAddress(1);
        $contact->setSalutation("Sehr geehrter Herr Dr Mustermann");
        $contact->setDisabled(0);

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
        $account->setCreated(new DateTime());
        $account->setChanged(new DateTime());

        $account1 = new Account();
        $account1->setLft(0);
        $account1->setRgt(1);
        $account1->setDepth(0);
        $account1->setName('Musterfirma');
        $account1->setCreated(new DateTime());
        $account1->setChanged(new DateTime());

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
        $address->setPostboxCity("Dornbirn");
        $address->setPostboxPostcode("6850");
        $address->setPostboxNumber("4711");

        $contactAddress = new ContactAddress();
        $contactAddress->setAddress($address);
        $contactAddress->setContact($contact);
        $contactAddress->setMain(true);
        $contact->addContactAddresse($contactAddress);
        $address->addContactAddresse($contactAddress);

        $note = new Note();
        $note->setValue('Note');
        $contact->addNote($note);

        $this->setUpMediaEntities($contact);

        self::$em->persist($contact);
        self::$em->persist($title);
        self::$em->persist($position);
        self::$em->persist($account);
        self::$em->persist($account1);
        self::$em->persist($phoneType);
        self::$em->persist($phone);
        self::$em->persist($faxType);
        self::$em->persist($fax);
        self::$em->persist($emailType);
        self::$em->persist($email);
        self::$em->persist($country1);
        self::$em->persist($country2);
        self::$em->persist($addressType);
        self::$em->persist($contactAddress);
        self::$em->persist($address);
        self::$em->persist($note);

        self::$em->flush();

        $this->contactTitle = $title;
        $this->contactPosition = $position;
    }

    private function createTestClient()
    {
        return $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Activity'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactTitle'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Position'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Account'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountContact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Activity'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityPriority'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Address'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AddressType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\BankAccount'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactLocale'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Country'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Email'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\EmailType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Fax'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\FaxType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Note'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Phone'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\PhoneType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Url'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\UrlType'),
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountCategory'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfPayment'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\Collection'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\CollectionType'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\CollectionMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\Media'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\MediaType'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\File'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersion'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Category'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Category'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryMeta')
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
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
        $media->setCreated(new DateTime());
        $media->setChanged(new DateTime());
        $media->setType($imageType);

        $media2 = new Media();
        $media2->setCreated(new DateTime());
        $media2->setChanged(new DateTime());
        $media2->setType($imageType);

        $contact->addMedia($media2);

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setCreated(new DateTime());
        $file->setChanged(new DateTime());
        $file->setMedia($media);

        $file2 = new File();
        $file2->setVersion(1);
        $file2->setCreated(new DateTime());
        $file2->setChanged(new DateTime());
        $file2->setMedia($media2);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setCreated(new DateTime());
        $fileVersion->setChanged(new DateTime());
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
        $fileVersion->setCreated(new DateTime());
        $fileVersion->setChanged(new DateTime());
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
        self::$em->persist($media);
        self::$em->persist($media2);
        self::$em->persist($collection);
        self::$em->persist($file);
        self::$em->persist($file2);
        self::$em->persist($videoType);
        self::$em->persist($imageType);
        self::$em->persist($audioType);
        self::$em->persist($mediaType);
    }

    public function setUpCollection(&$collection)
    {
        $style = array(
            'type' => 'circle',
            'color' => '#ffcc00'
        );

        $collection->setStyle(json_encode($style));

        $collection->setCreated(new DateTime());
        $collection->setChanged(new DateTime());

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

        self::$em->persist($collection);
        self::$em->persist($collectionType);
        self::$em->persist($collectionMeta);
        self::$em->persist($collectionMeta2);
    }

    public function testContactMediaPost(){
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/contacts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));

        $client->request(
            'POST',
            '/api/contacts/1/medias',
            array(
                'mediaId' => 1
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->id);

        $client->request(
            'GET',
            '/api/contacts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(2, count($response->medias));

        if($response->medias[0]->id == 1) {
            $this->assertEquals(1, $response->medias[0]->id);
            $this->assertEquals(2, $response->medias[1]->id);
        } else {
            $this->assertEquals(1, $response->medias[1]->id);
            $this->assertEquals(2, $response->medias[0]->id);
        }
    }

    public function testContactMediaPostNotExistingMedia(){
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/contacts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));

        $client->request(
            'POST',
            '/api/contacts/1/medias',
            array(
                'mediaId' => 99
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/contacts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));
    }

    public function testContactMediaDelete(){
        $client = $this->createTestClient();

        $client->request(
            'DELETE',
            '/api/contacts/1/medias/2'
        );

        $this->assertEquals('204', $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/contacts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));
    }

    public function testContactMediaDeleteNotExistingRelation(){
        $client = $this->createTestClient();
        $client->request(
            'DELETE',
            '/api/contacts/1/medias/99'
        );

        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/contacts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));
    }
}

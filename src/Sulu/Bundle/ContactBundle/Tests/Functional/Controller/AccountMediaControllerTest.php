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
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Sulu\Bundle\ContactBundle\Entity\Activity;
use Sulu\Bundle\ContactBundle\Entity\ActivityPriority;
use Sulu\Bundle\ContactBundle\Entity\ActivityStatus;
use Sulu\Bundle\ContactBundle\Entity\ActivityType;

class AccountMediaControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var Account
     */
    protected static $account;

    public function setUp()
    {
        $this->setUpSchema();

        self::$account = new Account();
        self::$account->setName('Company');
        self::$account->setType(Account::TYPE_BASIC);
        self::$account->setDisabled(0);
        self::$account->setCreated(new DateTime());
        self::$account->setChanged(new DateTime());
        self::$account->setPlaceOfJurisdiction('Feldkirch');

        $urlType = new UrlType();
        $urlType->setName('Private');

        $url = new Url();
        $url->setUrl('http://www.company.example');
        $url->setUrlType($urlType);
        self::$account->addUrl($url);

        $emailType = new EmailType();
        $emailType->setName('Private');

        $email = new Email();
        $email->setEmail('office@company.example');
        $email->setEmailType($emailType);
        self::$account->addEmail($email);

        $phoneType = new PhoneType();
        $phoneType->setName('Private');

        $phone = new Phone();
        $phone->setPhone('123456789');
        $phone->setPhoneType($phoneType);
        self::$account->addPhone($phone);

        $faxType = new FaxType();
        $faxType->setName('Private');

        $fax = new Fax();
        $fax->setFax('123654789');
        $fax->setFaxType($faxType);
        self::$account->addFax($fax);

        $country = new Country();
        $country->setName('Musterland');
        $country->setCode('ML');

        $addressType = new AddressType();
        $addressType->setName('Private');

        $address = new Address();
        $address->setStreet('Musterstraße');
        $address->setNumber('1');
        $address->setZip('0000');
        $address->setCity('Musterstadt');
        $address->setState('Musterland');
        $address->setCountry($country);
        $address->setAddressType($addressType);
        $address->setBillingAddress(true);
        $address->setPrimaryAddress(true);
        $address->setDeliveryAddress(false);
        $address->setPostboxCity("Dornbirn");
        $address->setPostboxPostcode("6850");
        $address->setPostboxNumber("4711");

        $accountAddress = new AccountAddress();
        $accountAddress->setAddress($address);
        $accountAddress->setAccount(self::$account);
        $accountAddress->setMain(true);
        self::$account->addAccountAddresse($accountAddress);
        $address->addAccountAddresse($accountAddress);

        $contact = new Contact();
        $contact->setFirstName("Vorname");
        $contact->setLastName("Nachname");
        $contact->setMiddleName("Mittelname");
        $contact->setCreated(new \DateTime());
        $contact->setChanged(new \DateTime());
        $contact->setDisabled(0);
        $contact->setFormOfAddress(0);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount(self::$account);
        $accountContact->setMain(true);
        self::$account->addAccountContact($accountContact);

        $note = new Note();
        $note->setValue('Note');
        self::$account->addNote($note);

        $this->setUpMediaEntities();

        self::$em->persist(self::$account);
        self::$em->persist($urlType);
        self::$em->persist($url);
        self::$em->persist($emailType);
        self::$em->persist($accountContact);
        self::$em->persist($email);
        self::$em->persist($phoneType);
        self::$em->persist($phone);
        self::$em->persist($country);
        self::$em->persist($addressType);
        self::$em->persist($address);
        self::$em->persist($accountAddress);
        self::$em->persist($note);
        self::$em->persist($faxType);
        self::$em->persist($fax);
        self::$em->persist($contact);

        $accountCategory = new AccountCategory();
        $accountCategory->setCategory("Test");
        self::$em->persist($accountCategory);

        self::$em->flush();
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
            self::$em->getClassMetadata('Sulu\Bundle\TestBundle\Entity\TestUser'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Account'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Activity'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityPriority'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Address'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AddressType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactTitle'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Position'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\BankAccount'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactLocale'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Country'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Email'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\EmailType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Note'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Fax'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\FaxType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Phone'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\PhoneType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Url'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\UrlType'),
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountCategory'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountContact'),
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
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
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

    public function setUpMediaEntities()
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

        self::$account->addMedia($media2);

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

    public function testAccountMediaPost(){
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/accounts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));

        $client->request(
            'POST',
            '/api/accounts/1/medias',
            array(
                'mediaId' => 1
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->id);

        $client->request(
            'GET',
            '/api/accounts/1'
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

    public function testAccountMediaPostNotExistingMedia(){
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/accounts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));

        $client->request(
            'POST',
            '/api/accounts/1/medias',
            array(
                'mediaId' => 99
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/accounts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));
    }

    public function testAccountMediaDelete(){
        $client = $this->createTestClient();

        $client->request(
            'DELETE',
            '/api/accounts/1/medias/2'
        );

        $this->assertEquals('204', $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/accounts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));
    }

    public function testAccountMediaDeleteNotExistingRelation(){
        $client = $this->createTestClient();
        $client->request(
            'DELETE',
            '/api/accounts/1/medias/99'
        );

        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/accounts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->medias));
    }
}

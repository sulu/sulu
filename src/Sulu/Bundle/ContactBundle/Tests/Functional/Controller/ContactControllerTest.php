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

use Doctrine\ORM\EntityManager;
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
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ContactControllerTest extends SuluTestCase
{
    /**
     * @var Position
     */
    private $contactPosition = null;

    /**
     * @var ContactTitle
     */
    private $contactTitle = null;

    /**
     * @var Media
     */
    private $avatar = null;

    /**
     * @var Media
     */
    private $media1 = null;

    /**
     * @var Media
     */
    private $media2 = null;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Contact
     */
    private $contact;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var Account
     */
    private $account1;

    /**
     * @var PhoneType
     */
    private $phoneType;

    /**
     * @var Phone
     */
    private $phone;

    /**
     * @var EmailType
     */
    private $emailType;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var FaxType
     */
    private $faxType;

    /**
     * @var Fax
     */
    private $fax;

    /**
     * @var Country
     */
    private $country;

    /**
     * @var Country
     */
    private $country2;

    /**
     * @var AddressType
     */
    private $addressType;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var ContactAddress
     */
    private $contactAddress;

    /**
     * @var Note
     */
    private $note;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->initOrm();
    }

    private function initOrm()
    {
        $this->purgeDatabase();
        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setPosition('CEO');
        $contact->setBirthday(new \DateTime());
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

        $this->account = $account;

        $account1 = new Account();
        $account1->setLft(0);
        $account1->setRgt(1);
        $account1->setDepth(0);
        $account1->setName('Musterfirma');

        $this->account1 = $account1;

        $phoneType = new PhoneType();
        $phoneType->setName('Private');

        $this->phoneType = $phoneType;

        $phone = new Phone();
        $phone->setPhone('123456789');
        $phone->setPhoneType($phoneType);
        $contact->addPhone($phone);

        $this->phone = $phone;

        $emailType = new EmailType();
        $emailType->setName('Private');

        $this->emailType = $emailType;

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);
        $contact->addEmail($email);

        $this->email = $email;

        $faxType = new FaxType();
        $faxType->setName('Private');

        $this->faxType = $faxType;

        $fax = new Fax();
        $fax->setFax('123654789');
        $fax->setFaxType($faxType);
        $contact->addFax($fax);

        $this->fax = $fax;

        $country1 = new Country();
        $country1->setName('Musterland');
        $country1->setCode('ML');

        $this->country = $country1;

        $country2 = new Country();
        $country2->setName('United States');
        $country2->setCode('US');

        $this->country2 = $country2;

        $addressType = new AddressType();
        $addressType->setName('Private');

        $this->addressType = $addressType;

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
        $address->setNote('Note');

        $this->address = $address;

        $contactAddress = new ContactAddress();
        $contactAddress->setAddress($address);
        $contactAddress->setContact($contact);
        $contactAddress->setMain(true);

        $this->contactAddress = $contactAddress;

        $contact->addContactAddress($contactAddress);
        $address->addContactAddress($contactAddress);

        $note = new Note();
        $note->setValue('Note');
        $this->note = $note;
        $contact->addNote($note);

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

        /* First Category
        -------------------------------------*/
        $category = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category->setKey('first-category-key');
        $category->setDefaultLocale('en');

        $this->category = $category;

        // name for first category
        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale('en');
        $categoryTrans->setTranslation('First Category');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        // meta for first category
        $categoryMeta = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta->setLocale('en');
        $categoryMeta->setKey('description');
        $categoryMeta->setValue('Description of Category');
        $categoryMeta->setCategory($category);
        $category->addMeta($categoryMeta);

        $this->em->persist($category);

        /* Second Category
        -------------------------------------*/
        $category2 = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category2->setKey('second-category-key');
        $category2->setDefaultLocale('en');

        $this->category2 = $category2;

        // name for second category
        $categoryTrans2 = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans2->setLocale('de');
        $categoryTrans2->setTranslation('Second Category');
        $categoryTrans2->setCategory($category2);
        $category2->addTranslation($categoryTrans2);

        // meta for second category
        $categoryMeta2 = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta2->setLocale('de');
        $categoryMeta2->setKey('description');
        $categoryMeta2->setValue('Description of second Category');
        $categoryMeta2->setCategory($category2);
        $category2->addMeta($categoryMeta2);

        $this->em->persist($category2);

        $this->initMedias();
        $contact->setAvatar($this->avatar);

        $this->em->flush();

        $this->contactTitle = $title;
        $this->contactPosition = $position;
    }

    public function initMedias()
    {
        $collectionType = new CollectionType();
        $collectionType->setName('My collection type');
        $this->em->persist($collectionType);

        $collection = new Collection();
        $collection->setType($collectionType);
        $this->em->persist($collection);

        $imageType = new MediaType();
        $imageType->setName('image');
        $imageType->setDescription('This is an image');
        $this->em->persist($imageType);

        $file = new File();
        $file->setVersion(1);

        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('avatar.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1937-04-20'));
        $fileVersion->setCreated(new \DateTime('1937-04-20'));
        $file->addFileVersion($fileVersion);
        $this->em->persist($fileVersion);

        $this->avatar = new Media();
        $this->avatar->setType($imageType);
        $this->avatar->setCollection($collection);
        $this->avatar->addFile($file);
        $file->setMedia($this->avatar);
        $this->em->persist($this->avatar);
        $this->em->persist($file);

        $file = new File();
        $file->setVersion(1);

        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('media1.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(111111);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1950-04-20'));
        $fileVersion->setCreated(new \DateTime('1950-04-20'));
        $file->addFileVersion($fileVersion);
        $this->em->persist($fileVersion);

        $this->media1 = new Media();
        $this->media1->setType($imageType);
        $this->media1->setCollection($collection);
        $this->media1->addFile($file);
        $file->setMedia($this->media1);
        $this->em->persist($this->media1);
        $this->em->persist($file);

        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('media2.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(111111);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1970-04-20'));
        $fileVersion->setCreated(new \DateTime('1970-04-20'));
        $file->addFileVersion($fileVersion);
        $this->em->persist($fileVersion);

        $this->media2 = new Media();
        $this->media2->setType($imageType);
        $this->media2->setCollection($collection);
        $this->media2->addFile($file);
        $file->setMedia($this->media2);
        $this->em->persist($this->media2);
        $this->em->persist($file);
    }

    public function testGetById()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts/' . $this->contact->getId());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Max', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('Max Mustermann', $response->fullName);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('Private', $response->phones[0]->phoneType->name);
        $this->assertEquals('123654789', $response->faxes[0]->fax);
        $this->assertEquals('Private', $response->faxes[0]->faxType->name);
        $this->assertEquals('max.mustermann@muster.at', $response->emails[0]->email);
        $this->assertEquals('Private', $response->emails[0]->emailType->name);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterland', $response->addresses[0]->state);
        $this->assertEquals('Note', $response->notes[0]->value);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);
        $this->assertEquals($this->addressType->getId(), $response->addresses[0]->addressType->id);

        $this->assertObjectHasAttribute('avatar', $response);
        $this->assertObjectHasAttribute('thumbnails', $response->avatar);
        $this->assertObjectHasAttribute('sulu-100x100', $response->avatar->thumbnails);
        $this->assertTrue(is_string($response->avatar->thumbnails->{'sulu-100x100'}));

        $this->assertEquals(1, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter Herr Dr Mustermann', $response->salutation);
    }

    private function createTestClient()
    {
        return $this->createClient(
            [],
            [
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            ]
        );
    }

    public function testPostAccountIDNull()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'account' => [
                    'id' => null,
                ],
                'emails' => [
                    [
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'phone' => '123456789',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '987654321',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                    ],
                ],
                'notes' => [
                    ['value' => 'Note 1'],
                    ['value' => 'Note 2'],
                ],
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals('erika.mustermann@muster.at', $response->emails[0]->email);
        $this->assertEquals('erika.mustermann@muster.de', $response->emails[1]->email);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('987654321', $response->phones[1]->phone);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('Note 1', $response->notes[0]->value);
        $this->assertEquals('Note 2', $response->notes[1]->value);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals('erika.mustermann@muster.at', $response->emails[0]->email);
        $this->assertEquals('erika.mustermann@muster.de', $response->emails[1]->email);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('987654321', $response->phones[1]->phone);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('Note 1', $response->notes[0]->value);
        $this->assertEquals('Note 2', $response->notes[1]->value);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);
    }

    public function testPost()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'avatar' => [
                    'id' => $this->avatar->getId(),
                ],
                'account' => [
                    'id' => $this->account1->getId(),
                ],
                'emails' => [
                    [
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'phone' => '123456789',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '987654321',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'faxes' => [
                    [
                        'fax' => '123456789-1',
                        'faxType' => [
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'fax' => '987654321-1',
                        'faxType' => [
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'title' => 'Home',
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                    ],
                ],
                'notes' => [
                    ['value' => 'Note 1'],
                    ['value' => 'Note 2'],
                ],
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => [
                    'id' => 0,
                ],
                'categories' => [
                    [
                        'id' => $this->category->getId(),
                    ],
                    [
                        'id' => $this->category2->getId(),
                    ],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals($this->account1->getId(), $response->account->id);

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals('Manager', $response->position->position);
        $this->assertEquals('erika.mustermann@muster.at', $response->emails[0]->email);
        $this->assertEquals('erika.mustermann@muster.de', $response->emails[1]->email);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('987654321', $response->phones[1]->phone);
        $this->assertEquals('123456789-1', $response->faxes[0]->fax);
        $this->assertEquals('987654321-1', $response->faxes[1]->fax);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('Note 1', $response->notes[0]->value);
        $this->assertEquals('Note 2', $response->notes[1]->value);
        $this->assertEquals('note', $response->addresses[0]->note);

        $this->assertEquals('Home', $response->addresses[0]->title);
        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);

        $this->assertObjectHasAttribute('avatar', $response);
        $this->assertObjectHasAttribute('thumbnails', $response->avatar);
        $this->assertObjectHasAttribute('sulu-100x100', $response->avatar->thumbnails);
        $this->assertTrue(is_string($response->avatar->thumbnails->{'sulu-100x100'}));

        $this->assertEquals(2, count($response->categories));

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals('Manager', $response->position->position);
        $this->assertEquals('erika.mustermann@muster.at', $response->emails[0]->email);
        $this->assertEquals('erika.mustermann@muster.de', $response->emails[1]->email);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('987654321', $response->phones[1]->phone);
        $this->assertEquals('123456789-1', $response->faxes[0]->fax);
        $this->assertEquals('987654321-1', $response->faxes[1]->fax);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('Note 1', $response->notes[0]->value);
        $this->assertEquals('Note 2', $response->notes[1]->value);

        $this->assertEquals('Home', $response->addresses[0]->title);
        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertObjectHasAttribute('avatar', $response);
        $this->assertObjectHasAttribute('thumbnails', $response->avatar);
        $this->assertObjectHasAttribute('sulu-100x100', $response->avatar->thumbnails);
        $this->assertTrue(is_string($response->avatar->thumbnails->{'sulu-100x100'}));

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);

        $this->assertEquals(2, count($response->categories));
    }

    public function testPostWithoutAdditionalData()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
    }

    public function testPostWithoutFormOfAddress()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'salutation' => 'Sehr geehrte Frau Mustermann',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\ContactBundle\Entity\Contact"-entity requires a "contact"-argument',
            $response->message
        );
    }

    public function testPostWithEmptyAdditionalData()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'emails' => [],
                'phones' => [],
                'notes' => [],
                'addresses' => [],
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);
    }

    public function testGetListSearchEmpty()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&search=Nothing&searchFields=fullName');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(0, $response->total);
        $this->assertEquals(0, count($response->_embedded->contacts));
    }

    public function testGetListSearch()
    {
        $contact1 = new Contact();
        $contact1->setFirstName('Erika');
        $contact1->setLastName('Mustermann');
        $this->em->persist($contact1);
        $this->em->flush();

        // dont use max here because the user for tests also is called max

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&search=Erika&searchFields=fullName&fields=fullName');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, count($response->_embedded->contacts));
        $this->assertEquals('Erika Mustermann', $response->_embedded->contacts[0]->fullName);
    }

    public function testGetListBySystem()
    {
        $suluContact = new Contact();
        $suluContact->setFirstName('Max');
        $suluContact->setLastName('Mustermann');

        $user = new User();
        $user->setUsername('max');
        $user->setPassword('max');
        $user->setLocale('de');
        $user->setSalt('salt');
        $role = new Role();
        $role->setName('User');
        $role->setSystem('Sulu');
        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale('[]');
        $user->setContact($suluContact);

        $this->em->persist($suluContact);
        $this->em->persist($user);
        $this->em->persist($userRole);
        $this->em->persist($role);
        $this->em->flush();

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?bySystem=true');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertCount(1, $response->_embedded->contacts);
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);
    }

    public function testPut()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'avatar' => [
                    'id' => $this->avatar->getId(),
                ],
                'emails' => [
                    [
                        'id' => $this->email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'john.doe@muster.de',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'id' => $this->phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '789456123',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '147258369',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'faxes' => [
                    [
                        'id' => $this->fax->getId(),
                        'fax' => '321654987-1',
                        'faxType' => [
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'fax' => '789456123-1',
                        'faxType' => [
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'fax' => '147258369-1',
                        'faxType' => [
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $this->address->getId(),
                        'title' => 'work',
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                    ],
                ],
                'notes' => [
                    [
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ],
                ],
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => [
                    'id' => 0,
                ],
                'categories' => [
                    [
                        'id' => $this->category->getId(),
                    ],
                    [
                        'id' => $this->category2->getId(),
                    ],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals('john.doe@muster.at', $response->emails[0]->email);
        $this->assertEquals('john.doe@muster.de', $response->emails[1]->email);
        $this->assertEquals('321654987', $response->phones[0]->phone);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('147258369', $response->phones[2]->phone);
        $this->assertEquals('321654987-1', $response->faxes[0]->fax);
        $this->assertEquals('789456123-1', $response->faxes[1]->fax);
        $this->assertEquals('147258369-1', $response->faxes[2]->fax);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, count($response->notes));
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);

        $this->assertObjectHasAttribute('avatar', $response);
        $this->assertObjectHasAttribute('thumbnails', $response->avatar);
        $this->assertObjectHasAttribute('sulu-100x100', $response->avatar->thumbnails);
        $this->assertTrue(is_string($response->avatar->thumbnails->{'sulu-100x100'}));

        $this->assertEquals(2, count($response->categories));

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals('john.doe@muster.at', $response->emails[0]->email);
        $this->assertEquals('john.doe@muster.de', $response->emails[1]->email);
        $this->assertEquals('321654987', $response->phones[0]->phone);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('147258369', $response->phones[2]->phone);
        $this->assertEquals('321654987-1', $response->faxes[0]->fax);
        $this->assertEquals('789456123-1', $response->faxes[1]->fax);
        $this->assertEquals('147258369-1', $response->faxes[2]->fax);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, count($response->notes));

        $this->assertEquals('work', $response->addresses[0]->title);
        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);

        $this->assertObjectHasAttribute('avatar', $response);
        $this->assertObjectHasAttribute('thumbnails', $response->avatar);
        $this->assertObjectHasAttribute('sulu-100x100', $response->avatar->thumbnails);
        $this->assertTrue(is_string($response->avatar->thumbnails->{'sulu-100x100'}));

        $this->assertEquals(2, count($response->categories));
    }

    public function testPutDeleteAndAddWithoutId()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'emails' => [
                    [
                        'email' => 'john.doe@muster.de',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'phone' => '789456123',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'faxes' => [
                    [
                        'fax' => '147258369-1',
                        'faxType' => [
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                    ],
                ],
                'notes' => [
                    [
                        'value' => 'Note 1_1',
                    ],
                ],
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals('john.doe@muster.de', $response->emails[0]->email);
        $this->assertEquals('789456123', $response->phones[0]->phone);
        $this->assertEquals('147258369-1', $response->faxes[0]->fax);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals(1, count($response->notes));

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals('john.doe@muster.de', $response->emails[0]->email);
        $this->assertEquals('789456123', $response->phones[0]->phone);
        $this->assertEquals('147258369-1', $response->faxes[0]->fax);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, count($response->notes));

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
    }

    public function testPutNoEmail()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'emails' => [],
                'phones' => [
                    [
                        'id' => $this->phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '789456123',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '147258369',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                    ],
                ],
                'notes' => [
                    [
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ],
                ],
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals(0, count($response->emails));

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);
        $this->assertEquals('note', $response->addresses[0]->note);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
    }

    public function testPutNewCountryOnlyId()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'emails' => [],
                'phones' => [
                    [
                        'id' => $this->phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '789456123',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '147258369',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => [
                            'id' => $this->country2->getId(),
                            'name' => '',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'notes' => [
                    [
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ],
                ],
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals(0, count($response->emails));

        $this->assertNotNull($response->addresses[0]->country->id);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
    }

    public function testPutNewAccount()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'account' => [
                    'id' => $this->account1->getId(),
                ],
                'emails' => [],
                'phones' => [
                    [
                        'id' => $this->phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '789456123',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '147258369',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => [
                            'id' => $this->country2->getId(),
                            'name' => '',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'notes' => [
                    [
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ],
                ],
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals(0, count($response->emails));

        $this->assertEquals($this->account1->getId(), $response->account->id);

        $this->assertEquals($this->country2->getId(), $response->addresses[0]->country->id);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
    }

    public function testPutNotExisting()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/10113',
            [
                'firstName' => 'John',
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testGetList()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&fields=fullName,title,formOfAddress,salutation');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);

        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);
        $this->assertEquals('MSc', $response->_embedded->contacts[0]->title);

        $this->assertEquals(1, $response->_embedded->contacts[0]->formOfAddress);
        $this->assertEquals('Sehr geehrter Herr Dr Mustermann', $response->_embedded->contacts[0]->salutation);
    }

    public function testGetListFields()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&fields=id,fullName');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals($this->contact->getId(), $response->_embedded->contacts[0]->id);
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&fields=id,fullName');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals($this->contact->getId(), $response->_embedded->contacts[0]->id);
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);
    }

    public function testGetListIds()
    {
        $contact1 = new Contact();
        $contact1->setFirstName('Erika');
        $contact1->setLastName('Mustermann');
        $this->em->persist($contact1);

        $contact2 = new Contact();
        $contact2->setFirstName('Anne');
        $contact2->setLastName('Mustermann');
        $this->em->persist($contact2);

        $contact3 = new Contact();
        $contact3->setFirstName('Otto');
        $contact3->setLastName('Mustermann');
        $this->em->persist($contact3);
        $this->em->flush();

        $ids = sprintf('%s,%s,%s', $contact1->getId(), $contact2->getId(), $contact3->getId());

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&ids=' . $ids . '&fields=id');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);

        $this->assertEquals($contact1->getId(), $response->_embedded->contacts[0]->id);
        $this->assertEquals($contact2->getId(), $response->_embedded->contacts[1]->id);
        $this->assertEquals($contact3->getId(), $response->_embedded->contacts[2]->id);
    }

    public function testGetListIdsEmpty()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&ids=');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertCount(0, $response->_embedded->contacts);
    }

    public function testGetListIdsOrder()
    {
        $contact1 = new Contact();
        $contact1->setFirstName('Erika');
        $contact1->setLastName('Mustermann');
        $this->em->persist($contact1);

        $contact2 = new Contact();
        $contact2->setFirstName('Anne');
        $contact2->setLastName('Mustermann');
        $this->em->persist($contact2);

        $contact3 = new Contact();
        $contact3->setFirstName('Otto');
        $contact3->setLastName('Mustermann');
        $this->em->persist($contact3);
        $this->em->flush();

        $ids = sprintf('%s,%s,%s', $contact3->getId(), $contact1->getId(), $contact2->getId());

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&ids=' . $ids . '&fields=id');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);

        $this->assertEquals($contact3->getId(), $response->_embedded->contacts[0]->id);
        $this->assertEquals($contact1->getId(), $response->_embedded->contacts[1]->id);
        $this->assertEquals($contact2->getId(), $response->_embedded->contacts[2]->id);
    }

    public function testDelete()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', '/api/contacts/' . $this->contact->getId());

        $this->assertHttpStatusCode(204, $client->getResponse());

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts/' . $this->contact->getId());

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDeleteNotExisting()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', '/api/contacts/4711');

        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
    }

    public function testPutRemovedAccount()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'account' => [
                    'id' => $this->account1->getId(),
                ],
                'emails' => [
                    [
                        'id' => $this->email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'john.doe@muster.de',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'id' => $this->phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '789456123',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '147258369',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'note' => 'note',
                    ],
                ],
                'notes' => [
                    [
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ],
                ],
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals($this->account1->getId(), $response->account->id);
        $this->assertEquals('john.doe@muster.at', $response->emails[0]->email);
        $this->assertEquals('john.doe@muster.de', $response->emails[1]->email);
        $this->assertEquals('321654987', $response->phones[0]->phone);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('147258369', $response->phones[2]->phone);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, count($response->notes));

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'account' => [
                    'id' => null,
                ],
                'emails' => [
                    [
                        'id' => $this->email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'id' => $response->emails[1]->id,
                        'email' => 'john.doe@muster.de',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'id' => $response->phones[0]->id,
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'id' => $response->phones[1]->id,
                        'phone' => '789456123',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'id' => $response->phones[2]->id,
                        'phone' => '147258369',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'note' => 'note1',
                    ],
                ],
                'notes' => [
                    [
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertObjectNotHasAttribute('account', $response);
        $this->assertEquals('john.doe@muster.at', $response->emails[0]->email);
        $this->assertEquals('john.doe@muster.de', $response->emails[1]->email);
        $this->assertEquals('321654987', $response->phones[0]->phone);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('note1', $response->addresses[0]->note);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, count($response->notes));

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertFalse(property_exists($response, 'salutation'));

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertObjectNotHasAttribute('account', $response);
        $this->assertEquals('john.doe@muster.at', $response->emails[0]->email);
        $this->assertEquals('john.doe@muster.de', $response->emails[1]->email);
        $this->assertEquals('321654987', $response->phones[0]->phone);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('147258369', $response->phones[2]->phone);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, count($response->notes));

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertFalse(property_exists($response, 'salutation'));
    }

    public function testPatchNotExisting()
    {
        $client = $this->createTestClient();

        $client->request(
            'PATCH',
            '/api/contacts/101',
            [
                'medias' => [],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testPatchAssignedMedias()
    {
        $client = $this->createTestClient();

        $client->request('GET', '/api/contacts/' . $this->contact->getId());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));

        // add two medias
        $client->request(
            'PATCH',
            '/api/contacts/' . $this->contact->getId(),
            [
                'medias' => [
                    [
                        'id' => $this->media1->getId(),
                    ],
                    [
                        'id' => $this->media2->getId(),
                    ],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(2, count($response->medias));

        // remove medias
        $client->request(
            'PATCH',
            '/api/contacts/' . $this->contact->getId(),
            [
                'medias' => [],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));

        // missing media
        $client->request(
            'PATCH',
            '/api/contacts/' . $this->contact->getId(),
            [
                'medias' => [
                    [
                        'id' => $this->media1->getId(),
                    ],
                    [
                        'id' => 101,
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request('GET', '/api/contacts/' . $this->contact->getId());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));
    }

    public function testPrimaryAddressHandlingPost()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => 'MSc',
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'account' => [
                    'id' => $this->account1->getId(),
                ],
                'emails' => [
                    [
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                    [
                        'street' => 'Musterstraße 2',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                ],
                'notes' => [
                    ['value' => 'Note 1'],
                    ['value' => 'Note 2'],
                ],
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($this->account1->getId(), $response->account->id);

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(true, $response->addresses[1]->primaryAddress);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent(), true);

        $addresses = $response['addresses'];

        $filterKeys = [
            'primaryAddress',
            'street',
        ];

        $filteredAddresses = array_map(
            function ($address) use ($filterKeys) {
                return array_intersect_key($address, array_flip($filterKeys));
            },
            $addresses
        );

        $this->assertContains(
            [
                'primaryAddress' => false,
                'street' => 'Musterstraße',
            ],
            $filteredAddresses
        );

        $this->assertContains(
            [
                'primaryAddress' => true,
                'street' => 'Musterstraße 2',
            ],
            $filteredAddresses
        );
    }

    public function testPrimaryAddressHandlingPut()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => [
                    'id' => $this->contactPosition->getId(),
                    'position' => $this->contactPosition->getPosition(),
                ],
                'emails' => [
                    [
                        'id' => $this->email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                    [
                        'street' => 'Street 1',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                    [
                        'street' => 'Street 2',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => [
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                ],
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        usort($response->addresses, $this->sortAddressesPrimaryLast());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[1]->primaryAddress);
        $this->assertEquals(true, $response->addresses[2]->primaryAddress);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());
        usort($response->addresses, $this->sortAddressesPrimaryLast());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[1]->primaryAddress);
        $this->assertEquals(true, $response->addresses[2]->primaryAddress);
    }

    public function testPostEmptyBirthday()
    {
        $client = $this->createTestClient();

        $client->request('GET', '/api/contacts/' . $this->contact->getId());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('birthday', $response);

        $data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'title' => $this->contactTitle->getId(),
            'birthday' => '',
        ];

        $client->request('PUT', '/api/contacts/' . $this->contact->getId(), $data);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('birthday', $response);

        $client->request('GET', '/api/contacts/' . $this->contact->getId());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('birthday', $response);
    }

    public function sortAddressesPrimaryLast()
    {
        return function ($a, $b) {
            if ($a->primaryAddress === true && $b->primaryAddress === false) {
                return true;
            }

            return false;
        };
    }
}

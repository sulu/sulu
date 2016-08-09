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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AccountControllerTest extends SuluTestCase
{
    private $accountCount = 1;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var Account
     */
    private $childAccount;

    /**
     * @var Media
     */
    private $logo;

    /**
     * @var Media
     */
    private $media1 = null;

    /**
     * @var Media
     */
    private $media2 = null;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Account
     */
    private $parentAccount;

    /**
     * @var UrlType
     */
    private $urlType;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var EmailType
     */
    private $emailType;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var PhoneType
     */
    private $phoneType;

    /**
     * @var FaxType
     */
    private $faxType;

    /**
     * @var Country
     */
    private $country;

    /**
     * @var AddressType
     */
    private $addressType;

    /**
     * @var Address
     */
    private $address;

    public function setUp()
    {
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->initOrm();
    }

    private function initOrm()
    {
        $account = new Account();
        $account->setName('Company');
        $account->setPlaceOfJurisdiction('Feldkirch');

        $parentAccount = new Account();
        $parentAccount->setName('Parent');
        $parentAccount->setPlaceOfJurisdiction('Feldkirch');

        $childAccount = new Account();
        $childAccount->setName('Child');
        $childAccount->setPlaceOfJurisdiction('Feldkirch');
        $childAccount->setParent($parentAccount);

        $this->account = $account;
        $this->childAccount = $childAccount;
        $this->parentAccount = $parentAccount;

        $urlType = new UrlType();
        $urlType->setName('Private');

        $this->urlType = $urlType;

        $url = new Url();
        $url->setUrl('http://www.company.example');

        $this->url = $url;
        $url->setUrlType($urlType);
        $account->addUrl($url);

        $this->emailType = new EmailType();
        $this->emailType->setName('Private');

        $this->email = new Email();
        $this->email->setEmail('office@company.example');
        $this->email->setEmailType($this->emailType);
        $account->addEmail($this->email);

        $phoneType = new PhoneType();
        $phoneType->setName('Private');

        $this->phoneType = $phoneType;

        $phone = new Phone();
        $phone->setPhone('123456789');
        $phone->setPhoneType($phoneType);
        $account->addPhone($phone);

        $faxType = new FaxType();
        $faxType->setName('Private');

        $this->faxType = $faxType;

        $fax = new Fax();
        $fax->setFax('123654789');
        $fax->setFaxType($faxType);
        $account->addFax($fax);

        $country = new Country();
        $country->setName('Musterland');
        $country->setCode('ML');

        $this->country = $country;

        $addressType = new AddressType();
        $addressType->setName('Private');

        $this->addressType = $addressType;

        $address = new Address();
        $address->setStreet('Musterstraße');
        $address->setNumber('1');
        $address->setZip('0000');
        $address->setCity('Musterstadt');
        $address->setState('Musterland');
        $address->setCountry($country);
        $address->setAddition('');
        $address->setAddressType($addressType);
        $address->setBillingAddress(true);
        $address->setPrimaryAddress(true);
        $address->setDeliveryAddress(false);
        $address->setPostboxCity('Dornbirn');
        $address->setPostboxPostcode('6850');
        $address->setPostboxNumber('4711');
        $address->setNote('note');

        $this->address = $address;

        $accountAddress = new AccountAddress();
        $accountAddress->setAddress($address);
        $accountAddress->setAccount($account);
        $accountAddress->setMain(true);
        $account->addAccountAddress($accountAddress);
        $address->addAccountAddress($accountAddress);

        $contact = new Contact();
        $contact->setFirstName('Vorname');
        $contact->setLastName('Nachname');
        $contact->setMiddleName('Mittelname');
        $contact->setFormOfAddress(0);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $accountContact->setMain(true);
        $account->addAccountContact($accountContact);

        $note = new Note();
        $note->setValue('Note');
        $account->addNote($note);

        $this->initMedias();
        $account->setLogo($this->logo);

        $this->em->persist($account);
        $this->em->persist($childAccount);
        $this->em->persist($parentAccount);
        $this->em->persist($urlType);
        $this->em->persist($url);
        $this->em->persist($this->emailType);
        $this->em->persist($accountContact);
        $this->em->persist($this->email);
        $this->em->persist($phoneType);
        $this->em->persist($phone);
        $this->em->persist($country);
        $this->em->persist($addressType);
        $this->em->persist($address);
        $this->em->persist($accountAddress);
        $this->em->persist($note);
        $this->em->persist($faxType);
        $this->em->persist($fax);
        $this->em->persist($contact);

        $this->em->flush();
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
        $fileVersion->setName('logo.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1937-04-20'));
        $fileVersion->setCreated(new \DateTime('1937-04-20'));
        $file->addFileVersion($fileVersion);
        $this->em->persist($fileVersion);

        $this->logo = new Media();
        $this->logo->setType($imageType);
        $this->logo->setCollection($collection);
        $this->logo->addFile($file);
        $file->setMedia($this->logo);
        $this->em->persist($this->logo);
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

    public function testCgetSerializationExclusions()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/accounts'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayNotHasKey('account', $response['_embedded']['accounts'][0]['accountContacts'][0]['contact']);
        $this->assertArrayNotHasKey('account', $response['_embedded']['accounts'][0]['contacts'][0]);
    }

    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('Company', $response->name);
        $this->assertEquals('http://www.company.example', $response->urls[0]->url);
        $this->assertEquals('Private', $response->urls[0]->urlType->name);
        $this->assertEquals('office@company.example', $response->emails[0]->email);
        $this->assertEquals('Private', $response->emails[0]->emailType->name);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('Private', $response->phones[0]->phoneType->name);
        $this->assertEquals('123654789', $response->faxes[0]->fax);
        $this->assertEquals('Private', $response->faxes[0]->faxType->name);
        $this->assertEquals('Note', $response->notes[0]->value);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterland', $response->addresses[0]->state);
        $this->assertEquals('Musterland', $response->addresses[0]->country->name);
        $this->assertEquals('ML', $response->addresses[0]->country->code);
        $this->assertEquals('Private', $response->addresses[0]->addressType->name);
        $this->assertEquals('Feldkirch', $response->placeOfJurisdiction);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertObjectHasAttribute('logo', $response);
        $this->assertEquals($this->logo->getId(), $response->logo->id);
        $this->assertObjectHasAttribute('thumbnails', $response->logo);
        $this->assertObjectHasAttribute('100x100', $response->logo->thumbnails);
        $this->assertTrue(is_string($response->logo->thumbnails->{'100x100'}));
    }

    public function testGetByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/accounts/11230'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertTrue(isset($response->message));
    }

    public function testGetEmptyAccountContacts()
    {
        $account = new Account();
        $account->setName('test');

        $this->em->persist($account);
        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/accounts/' . $account->getId() . '/contacts?flat=true');

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals(0, $response->total);
        $this->assertCount(0, $response->_embedded->contacts);
    }

    public function testGetAccountContacts()
    {
        $account = new Account();
        $account->setName('test');
        $this->em->persist($account);

        $account2 = new Account();
        $account2->setName('test2');
        $this->em->persist($account2);

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setFormOfAddress(0);
        $this->em->persist($contact);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $accountContact->setMain(true);
        $account->addAccountContact($accountContact);
        $this->em->persist($accountContact);

        $accountContact2 = new AccountContact();
        $accountContact2->setContact($contact);
        $accountContact2->setAccount($account2);
        $accountContact2->setMain(false);
        $account2->addAccountContact($accountContact2);
        $this->em->persist($accountContact2);

        $contact = new Contact();
        $contact->setFirstName('Erika');
        $contact->setLastName('Mustermann');
        $contact->setFormOfAddress(1);
        $this->em->persist($contact);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $account->addAccountContact($accountContact);
        $accountContact->setMain(false);
        $this->em->persist($accountContact);

        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/accounts/' . $account->getId() . '/contacts?flat=true&fields=firstName&sortBy=firstName'
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['contacts']);

        $this->assertEquals('Erika', $response['_embedded']['contacts'][0]['firstName']);
        $this->assertEquals('Max', $response['_embedded']['contacts'][1]['firstName']);
    }

    public function testGetAccountContactsSearch()
    {
        $account = new Account();
        $account->setName('test');
        $this->em->persist($account);

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setFormOfAddress(0);
        $this->em->persist($contact);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $accountContact->setMain(true);
        $account->addAccountContact($accountContact);
        $this->em->persist($accountContact);

        $contact = new Contact();
        $contact->setFirstName('Erika');
        $contact->setLastName('Mustermann');
        $contact->setFormOfAddress(1);
        $this->em->persist($contact);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $account->addAccountContact($accountContact);
        $accountContact->setMain(false);
        $this->em->persist($accountContact);

        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/accounts/' . $account->getId() . '/contacts?search=Max&searchFields=fullName&flat=true&fields=fullName'
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['contacts']);

        $this->assertEquals('Max Mustermann', $response['_embedded']['contacts'][0]['fullName']);
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'parent' => ['id' => $this->account->getId()],
                'logo' => ['id' => $this->logo->getId()],
                'urls' => [
                    [
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
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
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals($this->account->getId(), $response->parent->id);
        $this->assertEquals('erika.mustermann@muster.at', $response->emails[0]->email);
        $this->assertEquals('erika.mustermann@muster.de', $response->emails[1]->email);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('987654321', $response->phones[1]->phone);
        $this->assertEquals('123456789-1', $response->faxes[0]->fax);
        $this->assertEquals('987654321-1', $response->faxes[1]->fax);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('Note 1', $response->notes[0]->value);
        $this->assertEquals('Note 2', $response->notes[1]->value);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertObjectHasAttribute('logo', $response);
        $this->assertEquals($this->logo->getId(), $response->logo->id);
        $this->assertObjectHasAttribute('thumbnails', $response->logo);
        $this->assertObjectHasAttribute('100x100', $response->logo->thumbnails);
        $this->assertTrue(is_string($response->logo->thumbnails->{'100x100'}));

        $client->request('GET', '/api/accounts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals($this->account->getId(), $response->parent->id);
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

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertObjectHasAttribute('logo', $response);
        $this->assertEquals($this->logo->getId(), $response->logo->id);
        $this->assertObjectHasAttribute('thumbnails', $response->logo);
        $this->assertObjectHasAttribute('100x100', $response->logo->thumbnails);
        $this->assertTrue(is_string($response->logo->thumbnails->{'100x100'}));
    }

    public function testPostWithCategory()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'parent' => ['id' => $this->account->getId()],
                'urls' => [
                    [
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
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
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals($this->account->getId(), $response->parent->id);
        $this->assertEquals('erika.mustermann@muster.at', $response->emails[0]->email);
        $this->assertEquals('erika.mustermann@muster.de', $response->emails[1]->email);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('987654321', $response->phones[1]->phone);
        $this->assertEquals('123456789-1', $response->faxes[0]->fax);
        $this->assertEquals('987654321-1', $response->faxes[1]->fax);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('Note 1', $response->notes[0]->value);
        $this->assertEquals('Note 2', $response->notes[1]->value);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $client->request('GET', '/api/accounts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals($this->account->getId(), $response->parent->id);
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

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);
    }

    public function testPostWithIds()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'urls' => [
                    [
                        'id' => 1512312312313,
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertContains('15', $response->message);

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'emails' => [
                    [
                        'id' => 16,
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => 1,
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => [
                            'id' => 1,
                            'name' => 'Work',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertContains('16', $response->message);

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'phones' => [
                    [
                        'id' => 17,
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
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertContains('17', $response->message);

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'addresses' => [
                    [
                        'id' => 18,
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
                            'id' => 1,
                            'name' => 'Private',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertContains('18', $response->message);

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'notes' => [
                    [
                        'id' => 19,
                        'value' => 'Note',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertContains('19', $response->message);
    }

    public function testPostWithNotExistingUrlType()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'urls' => [
                    [
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => '12312',
                            'name' => 'Work',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingEmailType()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'emails' => [
                    [
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => 1,
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => [
                            'id' => 2,
                            'name' => 'Work',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingPhoneType()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
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
                            'id' => '1233',
                            'name' => 'Work',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingAddressType()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'addresses' => [
                    [
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => [
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => 2,
                            'name' => 'Work',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingFaxType()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'faxes' => [
                    [
                        'fax' => '12345',
                        'faxType' => [
                            'id' => '123123',
                            'name' => 'Work',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingCountry()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'addresses' => [
                    [
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => [
                            'id' => 12393,
                            'name' => 'Österreich',
                            'code' => 'AT',
                        ],
                        'addressType' => [
                            'id' => 1,
                            'name' => 'Private',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testGetList()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/accounts?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);

        $this->assertEquals('Company', $response->_embedded->accounts[0]->name);
        $this->assertObjectHasAttribute('logo', $response->_embedded->accounts[0]);
        $this->assertObjectHasAttribute('100x100', $response->_embedded->accounts[0]->logo);
        $this->assertTrue(is_string($response->_embedded->accounts[0]->logo->{'100x100'}));
    }

    public function testGetListSearch()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/accounts?flat=true&search=Nothing&searchFields=name');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(0, $response->total);
        $this->assertEquals(0, count($response->_embedded->accounts));

        $client->request('GET', '/api/accounts?flat=true&search=Comp&searchFields=name');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, count($response->_embedded->accounts));
        $this->assertEquals('Company', $response->_embedded->accounts[0]->name);
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/accounts/' . $this->account->getId(),
            [
                'name' => 'ExampleCompany',
                'logo' => ['id' => $this->logo->getId()],
                'urls' => [
                    [
                        'id' => $this->url->getId(),
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'url' => 'http://test.company.com',
                        'urlType' => [
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'email' => 'office@company.com',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'erika.mustermann@company.com',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'phone' => '4567890',
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
                ],
                'faxes' => [
                    [
                        'fax' => '4567890-1',
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
                ],
                'addresses' => [
                    [
                        'street' => 'Bahnhofstraße',
                        'number' => '2',
                        'zip' => '0022',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                    [
                        'street' => 'Rathausgasse',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                    ['value' => 'Note1'],
                    ['value' => 'Note2'],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('ExampleCompany', $response->name);

        $this->assertEquals(2, count($response->urls));
        $this->assertEquals('http://example.company.com', $response->urls[0]->url);
        $this->assertEquals('Private', $response->urls[0]->urlType->name);
        $this->assertEquals('http://test.company.com', $response->urls[1]->url);
        $this->assertEquals('Private', $response->urls[1]->urlType->name);

        $this->assertEquals(2, count($response->emails));
        $this->assertEquals('office@company.com', $response->emails[0]->email);
        $this->assertEquals('Private', $response->emails[0]->emailType->name);
        $this->assertEquals('erika.mustermann@company.com', $response->emails[1]->email);
        $this->assertEquals('Private', $response->emails[1]->emailType->name);

        $this->assertEquals(2, count($response->phones));
        $this->assertEquals('4567890', $response->phones[0]->phone);
        $this->assertEquals('Private', $response->phones[0]->phoneType->name);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('Private', $response->phones[1]->phoneType->name);

        $this->assertEquals(2, count($response->faxes));
        $this->assertEquals('4567890-1', $response->faxes[0]->fax);
        $this->assertEquals('Private', $response->faxes[0]->faxType->name);
        $this->assertEquals('789456123-1', $response->faxes[1]->fax);
        $this->assertEquals('Private', $response->faxes[1]->faxType->name);

        $this->assertEquals(2, count($response->notes));
        $this->assertEquals('Note1', $response->notes[0]->value);
        $this->assertEquals('Note2', $response->notes[1]->value);

        $this->assertObjectHasAttribute('logo', $response);
        $this->assertEquals($this->logo->getId(), $response->logo->id);
        $this->assertObjectHasAttribute('thumbnails', $response->logo);
        $this->assertObjectHasAttribute('100x100', $response->logo->thumbnails);
        $this->assertTrue(is_string($response->logo->thumbnails->{'100x100'}));

        if ($response->addresses[0]->street === 'Bahnhofstraße') {
            $this->assertEquals(2, count($response->addresses));
            $this->assertEquals('Bahnhofstraße', $response->addresses[0]->street);
            $this->assertEquals('2', $response->addresses[0]->number);
            $this->assertEquals('0022', $response->addresses[0]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[0]->city);
            $this->assertEquals('state1', $response->addresses[0]->state);
            $this->assertEquals('note', $response->addresses[0]->note);
            $this->assertEquals('Musterland', $response->addresses[0]->country->name);
            $this->assertEquals('ML', $response->addresses[0]->country->code);
            $this->assertEquals('Private', $response->addresses[0]->addressType->name);

            $this->assertEquals(true, $response->addresses[0]->billingAddress);
            $this->assertEquals(true, $response->addresses[0]->primaryAddress);
            $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
            $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
            $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
            $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

            $this->assertEquals('Rathausgasse', $response->addresses[1]->street);
            $this->assertEquals('3', $response->addresses[1]->number);
            $this->assertEquals('2222', $response->addresses[1]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[1]->city);
            $this->assertEquals('note1', $response->addresses[1]->note);
            $this->assertEquals('state1', $response->addresses[1]->state);
            $this->assertEquals('Musterland', $response->addresses[1]->country->name);
            $this->assertEquals('ML', $response->addresses[1]->country->code);
            $this->assertEquals('Private', $response->addresses[1]->addressType->name);
        } else {
            $this->assertEquals(2, count($response->addresses));
            $this->assertEquals('Bahnhofstraße', $response->addresses[1]->street);
            $this->assertEquals('2', $response->addresses[1]->number);
            $this->assertEquals('note', $response->addresses[1]->note);
            $this->assertEquals('0022', $response->addresses[1]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[1]->city);
            $this->assertEquals('state1', $response->addresses[1]->state);
            $this->assertEquals('Musterland', $response->addresses[1]->country->name);
            $this->assertEquals('ML', $response->addresses[1]->country->code);
            $this->assertEquals('Private', $response->addresses[1]->addressType->name);

            $this->assertEquals(true, $response->addresses[1]->billingAddress);
            $this->assertEquals(true, $response->addresses[1]->primaryAddress);
            $this->assertEquals(false, $response->addresses[1]->deliveryAddress);
            $this->assertEquals('Dornbirn', $response->addresses[1]->postboxCity);
            $this->assertEquals('6850', $response->addresses[1]->postboxPostcode);
            $this->assertEquals('4711', $response->addresses[1]->postboxNumber);

            $this->assertEquals('Rathausgasse', $response->addresses[0]->street);
            $this->assertEquals('3', $response->addresses[0]->number);
            $this->assertEquals('2222', $response->addresses[0]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[0]->city);
            $this->assertEquals('state1', $response->addresses[0]->state);
            $this->assertEquals('Musterland', $response->addresses[0]->country->name);
            $this->assertEquals('ML', $response->addresses[0]->country->code);
            $this->assertEquals('note1', $response->addresses[0]->note);
            $this->assertEquals('Private', $response->addresses[0]->addressType->name);
        }

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('ExampleCompany', $response->name);

        $this->assertEquals(2, count($response->urls));
        $this->assertEquals('http://example.company.com', $response->urls[0]->url);
        $this->assertEquals('Private', $response->urls[0]->urlType->name);
        $this->assertEquals('http://test.company.com', $response->urls[1]->url);
        $this->assertEquals('Private', $response->urls[1]->urlType->name);

        $this->assertEquals(2, count($response->emails));
        $this->assertEquals('office@company.com', $response->emails[0]->email);
        $this->assertEquals('Private', $response->emails[0]->emailType->name);
        $this->assertEquals('erika.mustermann@company.com', $response->emails[1]->email);
        $this->assertEquals('Private', $response->emails[1]->emailType->name);

        $this->assertEquals(2, count($response->phones));
        $this->assertEquals('4567890', $response->phones[0]->phone);
        $this->assertEquals('Private', $response->phones[0]->phoneType->name);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('Private', $response->phones[1]->phoneType->name);

        $this->assertEquals(2, count($response->faxes));
        $this->assertEquals('4567890-1', $response->faxes[0]->fax);
        $this->assertEquals('Private', $response->faxes[0]->faxType->name);
        $this->assertEquals('789456123-1', $response->faxes[1]->fax);
        $this->assertEquals('Private', $response->faxes[1]->faxType->name);

        $this->assertEquals(2, count($response->notes));
        $this->assertEquals('Note1', $response->notes[0]->value);
        $this->assertEquals('Note2', $response->notes[1]->value);

        $this->assertObjectHasAttribute('logo', $response);
        $this->assertEquals($this->logo->getId(), $response->logo->id);
        $this->assertObjectHasAttribute('thumbnails', $response->logo);
        $this->assertObjectHasAttribute('100x100', $response->logo->thumbnails);
        $this->assertTrue(is_string($response->logo->thumbnails->{'100x100'}));

        if ($response->addresses[0]->street === 'Bahnhofstraße') {
            $this->assertEquals(2, count($response->addresses));
            $this->assertEquals('Bahnhofstraße', $response->addresses[0]->street);
            $this->assertEquals('2', $response->addresses[0]->number);
            $this->assertEquals('0022', $response->addresses[0]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[0]->city);
            $this->assertEquals('state1', $response->addresses[0]->state);
            $this->assertEquals('Musterland', $response->addresses[0]->country->name);
            $this->assertEquals('ML', $response->addresses[0]->country->code);
            $this->assertEquals('Private', $response->addresses[0]->addressType->name);

            $this->assertEquals(true, $response->addresses[0]->billingAddress);
            $this->assertEquals(true, $response->addresses[0]->primaryAddress);
            $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
            $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
            $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
            $this->assertEquals('note', $response->addresses[0]->note);
            $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

            $this->assertEquals('Rathausgasse', $response->addresses[1]->street);
            $this->assertEquals('3', $response->addresses[1]->number);
            $this->assertEquals('2222', $response->addresses[1]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[1]->city);
            $this->assertEquals('state1', $response->addresses[1]->state);
            $this->assertEquals('Musterland', $response->addresses[1]->country->name);
            $this->assertEquals('ML', $response->addresses[1]->country->code);
            $this->assertEquals('Private', $response->addresses[1]->addressType->name);
            $this->assertEquals('note1', $response->addresses[1]->note);
        } else {
            $this->assertEquals(2, count($response->addresses));
            $this->assertEquals('Bahnhofstraße', $response->addresses[1]->street);
            $this->assertEquals('2', $response->addresses[1]->number);
            $this->assertEquals('0022', $response->addresses[1]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[1]->city);
            $this->assertEquals('state1', $response->addresses[1]->state);
            $this->assertEquals('Musterland', $response->addresses[1]->country->name);
            $this->assertEquals('ML', $response->addresses[1]->country->code);
            $this->assertEquals('Private', $response->addresses[1]->addressType->name);

            $this->assertEquals(true, $response->addresses[1]->billingAddress);
            $this->assertEquals(true, $response->addresses[1]->primaryAddress);
            $this->assertEquals(false, $response->addresses[1]->deliveryAddress);
            $this->assertEquals('Dornbirn', $response->addresses[1]->postboxCity);
            $this->assertEquals('6850', $response->addresses[1]->postboxPostcode);
            $this->assertEquals('4711', $response->addresses[1]->postboxNumber);
            $this->assertEquals('note', $response->addresses[1]->note);

            $this->assertEquals('Rathausgasse', $response->addresses[0]->street);
            $this->assertEquals('3', $response->addresses[0]->number);
            $this->assertEquals('2222', $response->addresses[0]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[0]->city);
            $this->assertEquals('state1', $response->addresses[0]->state);
            $this->assertEquals('Musterland', $response->addresses[0]->country->name);
            $this->assertEquals('ML', $response->addresses[0]->country->code);
            $this->assertEquals('Private', $response->addresses[0]->addressType->name);
            $this->assertEquals('note1', $response->addresses[0]->note);
        }
    }

    public function testPutNoDetails()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/accounts/' . $this->account->getId(),
            [
                'name' => 'ExampleCompany',
                'urls' => [],
                'emails' => [],
                'phones' => [],
                'addresses' => [],
                'faxes' => [],
                'notes' => [],
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('ExampleCompany', $response->name);

        $this->assertEquals(0, count($response->urls));
        $this->assertEquals(0, count($response->emails));
        $this->assertEquals(0, count($response->phones));
        $this->assertEquals(0, count($response->faxes));
        $this->assertEquals(0, count($response->notes));
        $this->assertEquals(0, count($response->addresses));
    }

    public function testPutNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/accounts/4711',
            [
                'name' => 'TestCompany',
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testPatchNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PATCH',
            '/api/accounts/101',
            [
                'medias' => [],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testPatchAssignedMedias()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $this->account->getId());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));

        // add two medias
        $client->request(
            'PATCH',
            '/api/accounts/' . $this->account->getId(),
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
            '/api/accounts/' . $this->account->getId(),
            [
                'medias' => [],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));

        // missing media
        $client->request(
            'PATCH',
            '/api/accounts/' . $this->account->getId(),
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

        $client->request('GET', '/api/accounts/' . $this->account->getId());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));
    }

    public function testDeleteById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/accounts/' . $this->account->getId());
        $this->assertHttpStatusCode(204, $client->getResponse());
    }

    public function testAccountAddresses()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/addresses');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $address = $response->_embedded->addresses[0];
        $this->assertEquals('Musterstraße', $address->street);
        $this->assertEquals('1', $address->number);

        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/addresses?flat=true');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);

        $address = $response->_embedded->addresses[0];
        $this->assertEquals('Musterstraße 1 , 0000, Musterstadt, Musterland, Musterland, 4711', $address->address);
        $this->assertNotNull($address->id);
    }

    public function testDeleteByIdAndNotDeleteContacts()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/api/accounts/' . $this->account->getId(),
            [
                'removeContacts' => 'false',
            ]
        );
        $this->assertHttpStatusCode(204, $client->getResponse());

        // check if contacts are still there
        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(2, $response->total);
    }

    public function testDeleteByIdAndDeleteContacts()
    {
        $contact = new Contact();
        $contact->setFirstName('Vorname');
        $contact->setLastName('Nachname');
        $contact->setMiddleName('Mittelname');
        $contact->setFormOfAddress(0);
        $this->em->persist($contact);
        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($this->account);
        $accountContact->setMain(true);
        $this->account->addAccountContact($accountContact);
        $this->em->persist($accountContact);

        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/api/accounts/' . $this->account->getId(),
            [
                'removeContacts' => 'true',
            ]
        );
        // check if contacts are still there
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }

    public function testDeleteByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/accounts/4711');
        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request('GET', '/api/accounts?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(3, $response->total);
    }

    /**
     * Test if deleteinfo returns correct data.
     */
    public function testMultipleDeleteInfo()
    {
        // modify test data
        $acc = new Account();
        $acc->setName('Test Account');
        $this->em->persist($acc);

        // add 5 contacts to account
        for ($i = 0; $i < 5; ++$i) {
            $contact = new Contact();
            $contact->setFirstName('Vorname ' . $i);
            $contact->setLastName('Nachname ' . $i);
            $contact->setMiddleName('Mittelname ' . $i);
            $contact->setFormOfAddress(0);
            $this->em->persist($contact);

            $accountContact = new AccountContact();
            $accountContact->setContact($contact);
            $accountContact->setAccount($this->account);
            $accountContact->setMain(true);
            $this->em->persist($accountContact);
            $this->account->addAccountContact($accountContact);
        }

        // add subaccount to $this->account
        $subacc = new Account();
        $subacc->setName('Subaccount');
        $subacc->setParent($this->account);

        $this->em->persist($subacc);

        $this->em->flush();

        // get number of contacts from both accounts
        $numContacts = $this->account->getAccountContacts()->count() + $acc->getAccountContacts()->count();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/accounts/multipledeleteinfo',
            [
                'ids' => [$this->account->getId(), $acc->getId()],
            ]
        );

        // asserts

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        // return full number of contacts related to account
        $this->assertEquals($numContacts, $response->numContacts);

        // allowed if no subaccount exists
        $this->assertEquals(1, $response->numChildren);
    }

    /**
     * Test if deleteinfo returns correct data.
     */
    public function testGetDeleteInfoById()
    {
        // modify test data

        for ($i = 0; $i < 5; ++$i) {
            $contact = new Contact();
            $contact->setFirstName('Vorname ' . $i);
            $contact->setLastName('Nachname ' . $i);
            $contact->setMiddleName('Mittelname ' . $i);
            $contact->setFormOfAddress(0);
            $this->em->persist($contact);

            $accountContact = new AccountContact();
            $accountContact->setContact($contact);
            $accountContact->setAccount($this->account);
            $accountContact->setMain(true);
            $this->em->persist($accountContact);
            $this->account->addAccountContact($accountContact);
        }

        $this->em->flush();

        $numContacts = $this->account->getAccountContacts()->count();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/deleteinfo');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        // number of returned contacts has to be less or equal 3
        $this->assertEquals(3, count($response->contacts));

        // return full number of contacts related to account
        $this->assertEquals($numContacts, $response->numContacts);

        // allowed if no subaccount exists
        $this->assertEquals(0, $response->numChildren);
    }

    /**
     * Test if delete info returns right isAllowed, when there is a superaccount.
     */
    public function testGetDeletInfoByIdWithSuperAccount()
    {

        // changing test data: adding child accounts
        for ($i = 0; $i < 5; ++$i) {
            $childAccount = new Account();
            $childAccount->setName('child num#' . $i);
            $childAccount->setParent($this->account);

            $this->em->persist($childAccount);
        }
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/deleteinfo');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        // deletion not allowed if children existent
        $this->assertGreaterThan(0, $response->numChildren);

        // number of returned contacts has to be less or equal 3
        $this->assertLessThanOrEqual(3, count($response->children));
    }

    public function testGetDeleteInfoByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/accounts/4711/deleteinfo');
        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/deleteinfo');
        $this->assertHttpStatusCode(200, $client->getResponse());
    }

    public function testPutRemovedParentAccount()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'parent' => ['id' => $this->account->getId()],
                'urls' => [
                    [
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
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
                ],
                'notes' => [
                    ['value' => 'Note 1'],
                    ['value' => 'Note 2'],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals($this->account->getId(), $response->parent->id);
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

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $account2Id = $response->id;

        $client->request(
            'PUT',
            '/api/accounts/' . $account2Id,
            [
                'id' => $account2Id,
                'name' => 'ExampleCompany 222',
                'parent' => ['id' => null],
                'urls' => [
                    [
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'id' => $response->emails[0]->id,
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'id' => $response->emails[1]->id,
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'id' => $response->phones[0]->id,
                        'phone' => '123456789',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'id' => $response->phones[1]->id,
                        'phone' => '987654321',
                        'phoneType' => [
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'faxes' => [
                    [
                        'id' => $response->faxes[0]->id,
                        'fax' => '123456789-1',
                        'faxType' => [
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'id' => $response->faxes[1]->id,
                        'fax' => '987654321-1',
                        'faxType' => [
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $response->addresses[0]->id,
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
                ],
                'notes' => [
                    ['id' => $response->notes[0]->id, 'value' => 'Note 1'],
                    ['id' => $response->notes[1]->id, 'value' => 'Note 2'],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $client->request(
            'GET',
            '/api/accounts/' . $account2Id
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('ExampleCompany 222', $response->name);
        $this->assertObjectNotHasAttribute('parent', $response);
        $this->assertEquals('erika.mustermann@muster.at', $response->emails[0]->email);
        $this->assertEquals('erika.mustermann@muster.de', $response->emails[1]->email);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('987654321', $response->phones[1]->phone);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('Note 1', $response->notes[0]->value);
        $this->assertEquals('Note 2', $response->notes[1]->value);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);
    }

    public function testPrimaryAddressHandlingPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'parent' => ['id' => $this->account->getId()],
                'urls' => [
                    [
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
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
                        'street' => 'Musterstraße',
                        'number' => '2',
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
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(true, $response->addresses[1]->primaryAddress);

        $client->request('GET', '/api/accounts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        if ($response->addresses[0]->number == 1) {
            $this->assertEquals(false, $response->addresses[0]->primaryAddress);
            $this->assertEquals(true, $response->addresses[1]->primaryAddress);
        } else {
            $this->assertEquals(false, $response->addresses[1]->primaryAddress);
            $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        }
    }

    public function testPrimaryAddressHandlingPut()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/accounts/' . $this->account->getId(),
            [
                'name' => 'ExampleCompany',
                'urls' => [
                    [
                        'id' => $this->url->getId(),
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'url' => 'http://test.company.com',
                        'urlType' => [
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'email' => 'office@company.com',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'erika.mustermann@company.com',
                        'emailType' => [
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $this->address->getId(),
                        'street' => 'Bahnhofstraße',
                        'number' => '2',
                        'zip' => '0022',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                        'street' => 'Rathausgasse 1',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                        'street' => 'Rathausgasse 2',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());
        usort($response->addresses, $this->sortAddressesPrimaryLast());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[1]->primaryAddress);
        $this->assertEquals(true, $response->addresses[2]->primaryAddress);

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());
        usort($response->addresses, $this->sortAddressesPrimaryLast());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[1]->primaryAddress);
        $this->assertEquals(true, $response->addresses[2]->primaryAddress);
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

    public function testGetAccountsWithNoParent()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/accounts?flat=true&hasNoParent=true'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals(2, $response->total);
    }

    /**
     * Tests if all accounts are returned when fetching flat api by ids.
     */
    public function testCGetByIdsOnFlatApi()
    {
        $amount = 30;

        // Create 30 new accounts.
        $accounts = $this->createMultipleMinimalAccounts($amount);
        $this->em->flush();

        // Get ids of new accounts.
        $ids = array_map(
            function ($account) {
                return $account->getId();
            },
            $accounts
        );

        // Make get request on flat api.
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/accounts?flat=true',
            [
                'ids' => $ids,
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount($amount, $response->_embedded->accounts);
    }

    /**
     * Tests if all accounts are returned when fetching flat api by ids.
     */
    public function testCGetByIdsOnFlatApiWithLimit()
    {
        $amount = 30;

        // Create 30 new accounts.
        $accounts = $this->createMultipleMinimalAccounts($amount);
        $this->em->flush();

        // Get ids of new accounts.
        $ids = array_map(
            function ($account) {
                return $account->getId();
            },
            $accounts
        );

        // Make get request on flat api.
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/accounts?flat=true',
            [
                'ids' => $ids,
                'page' => 2,
                'limit' => 10,
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(10, $response->_embedded->accounts);
    }

    /**
     * Creates a minimal account.
     *
     * @return AccountInterface
     */
    private function createMinimalAccount()
    {
        $account = new Account();
        $account->setName('Minimal Account ' . $this->accountCount++);

        $this->em->persist($account);

        return $account;
    }

    /**
     * Creates a certain amount of accounts.
     *
     * @param int $number
     *
     * @return array
     */
    private function createMultipleMinimalAccounts($number)
    {
        $accounts = [];

        for ($i = 0; $i < $number; ++$i) {
            $accounts[] = $this->createMinimalAccount();
        }

        return $accounts;
    }
}

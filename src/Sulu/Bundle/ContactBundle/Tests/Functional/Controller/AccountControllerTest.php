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
     * @var EntityManagerInterface
     */
    private $em;

    public function setUp()
    {
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
    }

    public function testCgetSerializationExclusions()
    {
        $account = $this->createAccount('Company');
        $contact = $this->createContact($account, 'Vorname', 'Nachname');
        $this->em->flush();

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
        $mediaType = $this->createMediaType('image');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);

        $this->em->flush();

        $logo = $this->createMedia('logo.jpeg', 'image/jpeg', $mediaType, $collection);
        $urlType = $this->createUrlType('Private');
        $url = $this->createUrl('http://www.company.example', $urlType);
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('office@company.example', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('123456789', $faxType);
        $note = $this->createNote('Note');
        $country = $this->createCountry('Musterland', 'ML');
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            $country,
            true,
            true,
            false,
            'Dornbirn',
            '6850',
            '4711',
            'note',
            47.4048346,
            9.7602198
        );
        $account = $this->createAccount(
            'Company',
            null,
            $url,
            $address,
            $email,
            $phone,
            $fax,
            $note,
            'Feldkirch',
            $logo
        );
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/accounts/' . $account->getId()
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
        $this->assertEquals(47.4048346, $response->addresses[0]->latitude);
        $this->assertEquals(9.7602198, $response->addresses[0]->longitude);
        $this->assertEquals('Feldkirch', $response->placeOfJurisdiction);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertObjectHasAttribute('logo', $response);
        $this->assertEquals($logo->getId(), $response->logo->id);
        $this->assertObjectHasAttribute('thumbnails', $response->logo);
        $this->assertObjectHasAttribute('sulu-100x100', $response->logo->thumbnails);
        $this->assertTrue(is_string($response->logo->thumbnails->{'sulu-100x100'}));
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
        $mediaType = $this->createMediaType('image');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);

        $this->em->flush();

        $logo = $this->createMedia('logo.jpeg', 'image/jpeg', $mediaType, $collection);
        $account = $this->createAccount('parent');
        $emailType = $this->createEmailType('Private');
        $phoneType = $this->createPhoneType('Private');
        $urlType = $this->createUrlType('Private');
        $faxType = $this->createFaxType('Private');
        $addressType = $this->createAddressType('Private');
        $country = $this->createCountry('Musterland', 'ML');
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'note' => 'A small notice',
                'parent' => ['id' => $account->getId()],
                'logo' => ['id' => $logo->getId()],
                'urls' => [
                    [
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'phone' => '123456789',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '987654321',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'faxes' => [
                    [
                        'fax' => '123456789-1',
                        'faxType' => [
                            'id' => $faxType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'fax' => '987654321-1',
                        'faxType' => [
                            'id' => $faxType->getId(),
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
                            'id' => $country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                        'latitude' => 47.4049309,
                        'longitude' => 9.7593077,
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
        $this->assertEquals('A small notice', $response->note);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals($account->getId(), $response->parent->id);
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
        $this->assertEquals(47.4049309, $response->addresses[0]->latitude);
        $this->assertEquals(9.7593077, $response->addresses[0]->longitude);

        $this->assertObjectHasAttribute('logo', $response);
        $this->assertEquals($logo->getId(), $response->logo->id);
        $this->assertObjectHasAttribute('thumbnails', $response->logo);
        $this->assertObjectHasAttribute('sulu-100x100', $response->logo->thumbnails);
        $this->assertTrue(is_string($response->logo->thumbnails->{'sulu-100x100'}));
    }

    public function testPostWithNullLogo()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'logo' => null,
                'corporation' => null,
                'note' => null,
                'parent' => null,
                'uid' => null,
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('ExampleCompany', $response->name);
    }

    public function testPostWithIds()
    {
        $phoneType = $this->createPhoneType('Private');
        $urlType = $this->createUrlType('Private');
        $country = $this->createCountry('Musterland', 'ML');

        $this->em->flush();

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
                            'id' => $urlType->getId(),
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
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '987654321',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
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
                            'id' => $country->getId(),
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
        $phoneType = $this->createPhoneType('Private');
        $this->em->flush();

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
                            'id' => $phoneType->getId(),
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
        $this->createAccount('Company');
        $parent = $this->createAccount('Parent');
        $this->createAccount('Child', $parent);

        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/accounts?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);

        $this->assertEquals('Company', $response->_embedded->accounts[0]->name);
    }

    public function testGetListSearch()
    {
        $this->createAccount('Company');
        $this->createAccount('Something');
        $this->em->flush();

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
        $mediaType = $this->createMediaType('image');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);

        $this->em->flush();

        $urlType = $this->createUrlType('Private');
        $url = $this->createUrl('http://www.company.example', $urlType);
        $emailType = $this->createEmailType('Private');
        $phoneType = $this->createPhoneType('Private');
        $faxType = $this->createFaxType('Private');
        $country = $this->createCountry('Musterland', 'ML');
        $addressType = $this->createAddressType('Private');
        $account = $this->createAccount('Company', null, $url);
        $logo = $this->createMedia('logo.jpeg', 'image/jpeg', $mediaType, $collection);

        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/accounts/' . $account->getId(),
            [
                'name' => 'ExampleCompany',
                'note' => 'A small notice',
                'logo' => ['id' => $logo->getId()],
                'urls' => [
                    [
                        'id' => $url->getId(),
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'url' => 'http://test.company.com',
                        'urlType' => [
                            'id' => $urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'email' => 'office@company.com',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'erika.mustermann@company.com',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'phone' => '4567890',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'phone' => '789456123',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'faxes' => [
                    [
                        'fax' => '4567890-1',
                        'faxType' => [
                            'id' => $faxType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'fax' => '789456123-1',
                        'faxType' => [
                            'id' => $faxType->getId(),
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
                            'id' => $country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $addressType->getId(),
                            'name' => 'Private',
                        ],
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                        'latitude' => 47.4048168,
                        'longitude' => 9.7585263,
                    ],
                    [
                        'street' => 'Rathausgasse',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
                        'country' => [
                            'id' => $country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $addressType->getId(),
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
        $this->assertEquals('A small notice', $response->note);

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
        $this->assertEquals($logo->getId(), $response->logo->id);
        $this->assertObjectHasAttribute('thumbnails', $response->logo);
        $this->assertObjectHasAttribute('sulu-100x100', $response->logo->thumbnails);
        $this->assertTrue(is_string($response->logo->thumbnails->{'sulu-100x100'}));

        if ('Bahnhofstraße' === $response->addresses[0]->street) {
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

            $this->assertEquals(47.4048168, $response->addresses[0]->latitude);
            $this->assertEquals(9.7585263, $response->addresses[0]->longitude);

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
    }

    public function testPutNoDetails()
    {
        $urlType = $this->createUrlType('Private');
        $url = $this->createUrl('http://www.company.example', $urlType);
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('info@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('123456789', $faxType);
        $country = $this->createCountry('Musterland', 'ML');
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            $country,
            true,
            true,
            false,
            'Dornbirn',
            '6850',
            '4711',
            'note',
            47.4048346,
            9.7602198
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Company', null, $url, $address, $email, $phone, $fax, $note);

        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/accounts/' . $account->getId(),
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
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(0, count($response->urls));
        $this->assertEquals(0, count($response->emails));
        $this->assertEquals(0, count($response->phones));
        $this->assertEquals(0, count($response->faxes));
        $this->assertEquals(0, count($response->notes));
        $this->assertEquals(0, count($response->addresses));
    }

    public function testPutWithNullLogo()
    {
        $urlType = $this->createUrlType('Private');
        $url = $this->createUrl('http://www.company.example', $urlType);
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('info@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('123456789', $faxType);
        $country = $this->createCountry('Musterland', 'ML');
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            $country,
            true,
            true,
            false,
            'Dornbirn',
            '6850',
            '4711',
            'note',
            47.4048346,
            9.7602198
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Company', null, $url, $address, $email, $phone, $fax, $note);

        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/accounts/' . $account->getId(),
            [
                'name' => 'ExampleCompany',
                'logo' => null,
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
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
        $account = $this->createAccount('Company');
        $mediaType = $this->createMediaType('image');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);
        $this->em->flush();

        $media1 = $this->createMedia('media1.jpeg', 'image/jpeg', $mediaType, $collection);
        $media2 = $this->createMedia('media2.jpeg', 'image/jpeg', $mediaType, $collection);
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $account->getId());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));

        // add two medias
        $client->request(
            'PATCH',
            '/api/accounts/' . $account->getId(),
            [
                'medias' => [
                    [
                        'id' => $media1->getId(),
                    ],
                    [
                        'id' => $media2->getId(),
                    ],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(2, count($response->medias));

        // remove medias
        $client->request(
            'PATCH',
            '/api/accounts/' . $account->getId(),
            [
                'medias' => [],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));

        // missing media
        $client->request(
            'PATCH',
            '/api/accounts/' . $account->getId(),
            [
                'medias' => [
                    [
                        'id' => $media1->getId(),
                    ],
                    [
                        'id' => 101,
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request('GET', '/api/accounts/' . $account->getId());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));
    }

    public function testDeleteById()
    {
        $account = $this->createAccount('Company');
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/accounts/' . $account->getId());
        $this->assertHttpStatusCode(204, $client->getResponse());
    }

    public function testAccountAddresses()
    {
        $addressType = $this->createAddressType('Private');
        $country = $this->createCountry('Musterland', 'ML');

        $address = $this->createAddress($addressType, 'Musterstraße', '1', '0000', 'Musterstadt', 'Musterland', $country, true, true, false, 'Dornbirn', '6850', '4711', 47.4048346, 9.7602198);
        $account = $this->createAccount('Company', null, null, $address);
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $account->getId() . '/addresses');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $address = $response->_embedded->addresses[0];
        $this->assertEquals('Musterstraße', $address->street);
        $this->assertEquals('1', $address->number);

        $client->request('GET', '/api/accounts/' . $account->getId() . '/addresses?flat=true');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);

        $address = $response->_embedded->addresses[0];
        $this->assertEquals('Musterstraße 1 , 0000, Musterstadt, Musterland, Musterland, 4711', $address->address);
        $this->assertNotNull($address->id);
    }

    public function testDeleteByIdAndNotDeleteContacts()
    {
        $account = $this->createAccount('Company');
        $contact = $this->createContact($account, 'Vorname', 'Nachname');
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/api/accounts/' . $account->getId(),
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
        $account = $this->createAccount('Company');

        $contact = new Contact();
        $contact->setFirstName('Vorname');
        $contact->setLastName('Nachname');
        $contact->setMiddleName('Mittelname');
        $contact->setFormOfAddress(0);
        $this->em->persist($contact);
        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $accountContact->setMain(true);
        $account->addAccountContact($accountContact);
        $this->em->persist($accountContact);

        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/api/accounts/' . $account->getId(),
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
    }

    /**
     * Test if deleteinfo returns correct data.
     */
    public function testMultipleDeleteInfo()
    {
        $account = $this->createAccount('Company');

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
            $accountContact->setAccount($account);
            $accountContact->setMain(true);
            $this->em->persist($accountContact);
            $account->addAccountContact($accountContact);
        }

        // add subaccount to $this->account
        $subacc = new Account();
        $subacc->setName('Subaccount');
        $subacc->setParent($account);

        $this->em->persist($subacc);

        $this->em->flush();

        // get number of contacts from both accounts
        $numContacts = $account->getAccountContacts()->count() + $acc->getAccountContacts()->count();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/accounts/multipledeleteinfo',
            [
                'ids' => [$account->getId(), $acc->getId()],
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
        $account = $this->createAccount('Company');

        for ($i = 0; $i < 5; ++$i) {
            $contact = new Contact();
            $contact->setFirstName('Vorname ' . $i);
            $contact->setLastName('Nachname ' . $i);
            $contact->setMiddleName('Mittelname ' . $i);
            $contact->setFormOfAddress(0);
            $this->em->persist($contact);

            $accountContact = new AccountContact();
            $accountContact->setContact($contact);
            $accountContact->setAccount($account);
            $accountContact->setMain(true);
            $this->em->persist($accountContact);
            $account->addAccountContact($accountContact);
        }

        $this->em->flush();

        $numContacts = $account->getAccountContacts()->count();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $account->getId() . '/deleteinfo');
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
    public function testGetDeleteInfoByIdWithSuperAccount()
    {
        $account = $this->createAccount('Parent');

        // changing test data: adding child accounts
        for ($i = 0; $i < 5; ++$i) {
            $childAccount = new Account();
            $childAccount->setName('child num#' . $i);
            $childAccount->setParent($account);

            $this->em->persist($childAccount);
        }
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $account->getId() . '/deleteinfo');
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
    }

    public function testPutRemovedParentAccount()
    {
        $urlType = $this->createUrlType('Private');
        $emailType = $this->createEmailType('Private');
        $phoneType = $this->createPhoneType('Private');
        $faxType = $this->createFaxType('Private');
        $addressType = $this->createAddressType('Private');
        $country = $this->createCountry('Musterland', 'ML');
        $account = $this->createAccount('Company', null);
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'parent' => ['id' => $account->getId()],
                'urls' => [
                    [
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'phone' => '123456789',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'faxes' => [
                    [
                        'fax' => '123456789-1',
                        'faxType' => [
                            'id' => $faxType->getId(),
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
                            'id' => $country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $addressType->getId(),
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
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals($account->getId(), $response->parent->id);
        $this->assertEquals('erika.mustermann@muster.at', $response->emails[0]->email);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('123456789-1', $response->faxes[0]->fax);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('Note 1', $response->notes[0]->value);

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
                            'id' => $urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'id' => $response->emails[0]->id,
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'id' => $response->phones[0]->id,
                        'phone' => '123456789',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'faxes' => [
                    [
                        'id' => $response->faxes[0]->id,
                        'fax' => '123456789-1',
                        'faxType' => [
                            'id' => $faxType->getId(),
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
                            'id' => $country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $addressType->getId(),
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
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('ExampleCompany 222', $response->name);
        $this->assertNull($response->parent);
        $this->assertEquals('erika.mustermann@muster.at', $response->emails[0]->email);
        $this->assertEquals('123456789', $response->phones[0]->phone);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('Note 1', $response->notes[0]->value);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);
    }

    public function testPrimaryAddressHandlingPost()
    {
        $urlType = $this->createUrlType('Private');
        $emailType = $this->createEmailType('Private');
        $addressType = $this->createAddressType('Private');
        $country = $this->createCountry('Musterland', 'ML');
        $account = $this->createAccount('Company', null);
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'parent' => ['id' => $account->getId()],
                'urls' => [
                    [
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => $emailType->getId(),
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
                            'id' => $country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $addressType->getId(),
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
                            'id' => $country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $addressType->getId(),
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

        if (1 == $response->addresses[0]->number) {
            $this->assertEquals(false, $response->addresses[0]->primaryAddress);
            $this->assertEquals(true, $response->addresses[1]->primaryAddress);
        } else {
            $this->assertEquals(false, $response->addresses[1]->primaryAddress);
            $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        }
    }

    public function testPrimaryAddressHandlingPut()
    {
        $urlType = $this->createUrlType('Private');
        $url = $this->createUrl('http://www.company.example', $urlType);
        $emailType = $this->createEmailType('Private');
        $addressType = $this->createAddressType('Private');
        $country = $this->createCountry('Musterland', 'ML');
        $address = $this->createAddress($addressType);
        $account = $this->createAccount('Company', null, $url, $address);
        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/accounts/' . $account->getId(),
            [
                'name' => 'ExampleCompany',
                'urls' => [
                    [
                        'id' => $url->getId(),
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'email' => 'office@company.com',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $address->getId(),
                        'street' => 'Bahnhofstraße',
                        'number' => '2',
                        'zip' => '0022',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
                        'country' => [
                            'id' => $country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $addressType->getId(),
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
                            'id' => $country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $addressType->getId(),
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
                            'id' => $country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ],
                        'addressType' => [
                            'id' => $addressType->getId(),
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
            '/api/accounts/' . $account->getId()
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
            if (true === $a->primaryAddress && false === $b->primaryAddress) {
                return true;
            }

            return false;
        };
    }

    public function testGetAccountsWithNoParent()
    {
        $this->createAccount('Account 1');
        $account2 = $this->createAccount('Account 2');
        $this->createAccount('Account 2.1', $account2);
        $this->em->flush();

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
        $amount = 11;

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
        $accounts = $this->createMultipleMinimalAccounts(11);
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
        $this->assertCount(1, $response->_embedded->accounts);
    }

    /**
     * Creates a minimal account.
     *
     * @return AccountInterface
     */
    private function createAccount(
        string $name,
        ?Account $parent = null,
        ?Url $url = null,
        ?Address $address = null,
        ?Email $email = null,
        ?Phone $phone = null,
        ?Fax $fax = null,
        ?Note $note = null,
        ?string $placeOfJurisdiction = null,
        ?Media $logo = null
    ) {
        $account = new Account();
        $account->setName($name);
        $account->setParent($parent);

        if ($placeOfJurisdiction) {
            $account->setPlaceOfJurisdiction($placeOfJurisdiction);
        }

        if ($url) {
            $account->addUrl($url);
        }

        if ($logo) {
            $account->setLogo($logo);
        }

        if ($address) {
            $accountAddress = new AccountAddress();
            $accountAddress->setAddress($address);
            $accountAddress->setAccount($account);
            $accountAddress->setMain(true);
            $account->addAccountAddress($accountAddress);
            $this->em->persist($accountAddress);
        }

        if ($email) {
            $account->addEmail($email);
        }

        if ($phone) {
            $account->addPhone($phone);
        }

        if ($fax) {
            $account->addFax($fax);
        }

        if ($note) {
            $account->addNote($note);
        }

        $this->em->persist($account);

        return $account;
    }

    private function createUrlType(string $name)
    {
        $urlType = new UrlType();
        $urlType->setName($name);

        $this->em->persist($urlType);

        return $urlType;
    }

    private function createUrl(string $urlValue, UrlType $urlType)
    {
        $url = new Url();
        $url->setUrl($urlValue);
        $url->setUrlType($urlType);

        $this->em->persist($url);

        return $url;
    }

    private function createEmail(string $emailAddress, EmailType $emailType)
    {
        $email = new Email();
        $email->setEmail($emailAddress);
        $email->setEmailType($emailType);

        $this->em->persist($email);

        return $email;
    }

    private function createEmailType(string $type)
    {
        $emailType = new EmailType();
        $emailType->setName($type);

        $this->em->persist($emailType);

        return $emailType;
    }

    private function createCountry(string $name, string $code)
    {
        $country = new Country();
        $country->setName($name);
        $country->setCode($code);

        $this->em->persist($country);

        return $country;
    }

    private function createAddressType(string $type)
    {
        $addressType = new AddressType();
        $addressType->setName($type);

        $this->em->persist($addressType);

        return $addressType;
    }

    private function createFaxType(string $type)
    {
        $faxType = new FaxType();
        $faxType->setName('Private');

        $this->em->persist($faxType);

        return $faxType;
    }

    private function createFax(string $number, FaxType $faxType)
    {
        $fax = new Fax();
        $fax->setFax('123654789');
        $fax->setFaxType($faxType);

        $this->em->persist($fax);

        return $fax;
    }

    private function createPhoneType(string $type)
    {
        $phoneType = new PhoneType();
        $phoneType->setName($type);

        $this->em->persist($phoneType);

        return $phoneType;
    }

    private function createPhone(string $phoneNumber, PhoneType $phoneType)
    {
        $phone = new Phone();
        $phone->setPhone($phoneNumber);
        $phone->setPhoneType($phoneType);

        $this->em->persist($phone);

        return $phone;
    }

    private function createAddress(
        ?AddressType $addressType = null,
        ?string $street = null,
        ?string $number = null,
        ?string $zip = null,
        ?string $city = null,
        ?string $state = null,
        ?Country $country = null,
        ?bool $billingAddress = null,
        ?bool $primaryAddress = null,
        ?bool $deliveryAddress = null,
        ?string $postboxCity = null,
        ?string $postboxCode = null,
        ?string $postboxNumber = null,
        ?string $note = null,
        ?float $latitude = null,
        ?float $longitude = null
    ) {
        $address = new Address();
        $address->setStreet($street);
        $address->setNumber($number);
        $address->setZip($zip);
        $address->setCity($city);
        $address->setState($state);
        $address->setCountry($country);
        $address->setAddition('');
        $address->setAddressType($addressType);
        $address->setBillingAddress($billingAddress);
        $address->setPrimaryAddress($primaryAddress);
        $address->setDeliveryAddress($deliveryAddress);
        $address->setPostboxCity($postboxCity);
        $address->setPostboxPostcode($postboxCode);
        $address->setPostboxNumber($postboxNumber);
        $address->setNote($note);
        $address->setLatitude($latitude);
        $address->setLongitude($longitude);

        $this->em->persist($address);

        return $address;
    }

    private function createContact(
        Account $account,
        string $firstName,
        string $lastName,
        ?string $middleName = null,
        ?int $formOfAddress = null
    ) {
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->setMiddleName($middleName);
        $contact->setFormOfAddress($formOfAddress);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $accountContact->setMain(true);
        $account->addAccountContact($accountContact);

        $this->em->persist($contact);
        $this->em->persist($accountContact);

        return $contact;
    }

    private function createCollection(CollectionType $collectionType)
    {
        $collection = new Collection();
        $collection->setType($collectionType);
        $this->em->persist($collection);

        return $collection;
    }

    private function createCollectionType(string $name)
    {
        $collectionType = new CollectionType();
        $collectionType->setName($name);
        $this->em->persist($collectionType);

        return $collectionType;
    }

    private function createMediaType(string $name, ?string $description = null)
    {
        $mediaType = new MediaType();
        $mediaType->setName($name);
        $mediaType->setDescription($description);
        $this->em->persist($mediaType);

        return $mediaType;
    }

    private function createMedia(string $name, string $mimeType, MediaType $mediaType, Collection $collection)
    {
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

        $media = new Media();
        $media->setType($mediaType);
        $media->setCollection($collection);
        $media->addFile($file);
        $file->setMedia($media);
        $this->em->persist($media);
        $this->em->persist($file);

        return $media;
    }

    private function createNote(string $value)
    {
        $note = new Note();
        $note->setValue($value);

        $this->em->persist($note);

        return $note;
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
            $accounts[] = $this->createAccount('Minimal Account ' . $this->accountCount++);
        }

        return $accounts;
    }
}

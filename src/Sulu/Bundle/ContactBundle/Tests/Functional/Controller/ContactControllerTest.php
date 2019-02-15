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
     * @var EntityManager
     */
    private $em;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
    }

    public function testGetById()
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('max.mustermann@muster.at', $faxType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
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
            'Note'
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Musterfirma');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);
        $mediaType = $this->createMediaType('image', 'This is an image');
        $media = $this->createMedia('media1.jpeg', 'image/jpeg', $mediaType, $collection);
        $contact = $this->createContact(
            'Max',
            'Mustermann',
            'CEO',
            new \DateTime(),
            1,
            'Sehr geehrter Herr Dr Mustermann',
            $title,
            $position,
            $email,
            $phone,
            $fax,
            $address,
            $note,
            $media
        );

        $category = $this->createCategory('first-category-key', 'en', 'First Category', 'Description of Category');
        $category2 = $this->createCategory('second-category-key', 'en', 'Second Category', 'Description of second Category');

        $this->em->flush();

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts/' . $contact->getId());

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
        $this->assertEquals($addressType->getId(), $response->addresses[0]->addressType);

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
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $phoneType = $this->createPhoneType('Private');
        $addressType = $this->createAddressType('Private');
        $country = $this->createCountry('Musterland', 'ML');
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'account' => [
                    'id' => null,
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
                'addresses' => [
                    [
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
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
                'formOfAddress' => 0,
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
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
        $this->assertEquals($title->getId(), $response->title);
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

    public function testPostCategoryNull()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'formOfAddress' => 0,
                'categories' => null,
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEmpty($response->categories);
    }

    public function testPost()
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $phoneType = $this->createPhoneType('Private');
        $faxType = $this->createFaxType('Private');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);
        $mediaType = $this->createMediaType('image', 'This is an image');
        $media = $this->createMedia('media1.jpeg', 'image/jpeg', $mediaType, $collection);
        $country = $this->createCountry('Musterland', 'ML');
        $addressType = $this->createAddressType('Private');
        $account = $this->createAccount('Musterfirma');
        $category1 = $this->createCategory('first-category-key', 'en', 'First Category', 'Description of Category');
        $category2 = $this->createCategory('second-category-key', 'en', 'Second Category', 'Description of second Category');
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'avatar' => [
                    'id' => $media->getId(),
                ],
                'account' => [
                    'id' => $account->getId(),
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
                        'title' => 'Home',
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
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
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => [
                    'id' => 0,
                ],
                'categories' => [
                    $category1->getId(),
                    $category2->getId(),
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals($position->getId(), $response->position);
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
        $this->assertEquals($category1->getId(), $response->categories[0]);
        $this->assertEquals($category2->getId(), $response->categories[1]);
    }

    public function testPostEmptyAddress()
    {
        $addressType = $this->createAddressType('Private');
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
                'formOfAddress' => 1,
                'addresses' => [
                    [
                        'addressType' => $addressType->getId(),
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertCount(1, $response->addresses);
    }

    public function testPostWithoutBankNameAndBic()
    {
        $addressType = $this->createAddressType('Private');
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
                'formOfAddress' => 1,
                'bankAccounts' => [
                    [
                        'iban' => 'DE89370400440532013000',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertCount(1, $response->bankAccounts);
        $this->assertEquals('DE89370400440532013000', $response->bankAccounts[0]->iban);
        $this->assertNull($response->bankAccounts[0]->bic);
    }

    public function testPostEmptyLatitude()
    {
        $title = $this->createTitle('MSc');
        $country = $this->createCountry('Musterland', 'ML');
        $addressType = $this->createAddressType('Private');
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $title->getId(),
                'addresses' => [
                    [
                        'title' => 'Home',
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                        'latitude' => 47.4049309,
                        'longitude' => '',
                    ],
                ],
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Erika', $response['firstName']);
        $this->assertEquals('Mustermann', $response['lastName']);

        $this->assertEquals('Home', $response['addresses'][0]['title']);
        $this->assertEquals(47.4049309, $response['addresses'][0]['latitude']);
        $this->assertNull($response['addresses'][0]['longitude']);
    }

    public function testPostWithoutAdditionalData()
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => [
                    'id' => 0,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
    }

    public function testPostWithoutFormOfAddress()
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'salutation' => 'Sehr geehrte Frau Mustermann',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\ContactBundle\Entity\Contact"-entity requires a "formOfAddress"-argument',
            $response->message
        );
    }

    public function testPostWithEmptyAdditionalData()
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'emails' => [],
                'phones' => [],
                'notes' => [],
                'addresses' => [],
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => '0',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);

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
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('max.mustermann@muster.at', $faxType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
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
            'Note'
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Musterfirma');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);
        $mediaType = $this->createMediaType('image', 'This is an image');
        $media = $this->createMedia('media1.jpeg', 'image/jpeg', $mediaType, $collection);
        $category1 = $this->createCategory('first-category-key', 'en', 'First Category', 'Description of Category');
        $category2 = $this->createCategory('second-category-key', 'en', 'Second Category', 'Description of second Category');
        $category3 = $this->createCategory('third-category-key', 'en', 'Third Category', 'Description of third Category');

        $contact = $this->createContact(
            'Max',
            'Mustermann',
            'CEO',
            new \DateTime(),
            0,
            'Sehr geehrter Herr',
            $title,
            $position,
            $email,
            $phone,
            $fax,
            $address,
            $note,
            null,
            [$category1, $category2]
        );

        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'note' => 'A small notice',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'avatar' => [
                    'id' => $media->getId(),
                ],
                'emails' => [
                    [
                        'id' => $email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                    [
                        'email' => 'john.doe@muster.de',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'id' => $phone->getId(),
                        'phone' => '321654987',
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
                        'id' => $fax->getId(),
                        'fax' => '321654987-1',
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
                        'id' => $address->getId(),
                        'title' => 'work',
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
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
                        'id' => $note->getId(),
                        'value' => 'Note 1_1',
                    ],
                ],
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => [
                    'id' => 0,
                ],
                'categories' => [
                    $category3->getId(),
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('A small notice', $response->note);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals('john.doe@muster.at', $response->emails[0]->email);
        $this->assertEquals('john.doe@muster.de', $response->emails[1]->email);
        $this->assertEquals('321654987', $response->phones[0]->phone);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('321654987-1', $response->faxes[0]->fax);
        $this->assertEquals('789456123-1', $response->faxes[1]->fax);
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

        $this->assertEquals(1, count($response->categories));
        $this->assertEquals($category3->getId(), $response->categories[0]);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('A small notice', $response->note);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals('john.doe@muster.at', $response->emails[0]->email);
        $this->assertEquals('john.doe@muster.de', $response->emails[1]->email);
        $this->assertEquals('321654987', $response->phones[0]->phone);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('321654987-1', $response->faxes[0]->fax);
        $this->assertEquals('789456123-1', $response->faxes[1]->fax);
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

        $this->assertEquals(1, count($response->categories));
        $this->assertEquals($category3->getId(), $response->categories[0]);
    }

    public function testPutDeleteAndAddWithoutId()
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('max.mustermann@muster.at', $faxType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
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
            'Note'
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Musterfirma');
        $contact = $this->createContact(
            'Max',
            'Mustermann',
            'CEO',
            new \DateTime(),
            0,
            'Sehr geehrter Herr',
            $title,
            $position,
            $email,
            $phone,
            null,
            $address,
            $note
        );

        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'emails' => [
                    [
                        'email' => 'john.doe@muster.de',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
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
                        'fax' => '147258369-1',
                        'faxType' => [
                            'id' => $faxType->getId(),
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
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
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
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
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
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
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
            'Note'
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Musterfirma');
        $contact = $this->createContact(
            'Max',
            'Mustermann',
            'CEO',
            new \DateTime(),
            0,
            'Sehr geehrter Herr',
            $title,
            $position,
            $email,
            $phone,
            null,
            $address,
            $note
        );

        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'emails' => [],
                'phones' => [
                    [
                        'id' => $phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
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
                        'id' => $note->getId(),
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
        $this->assertEquals($title->getId(), $response->title);
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
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
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
            'Note'
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Musterfirma');
        $contact = $this->createContact(
            'Max',
            'Mustermann',
            'CEO',
            new \DateTime(),
            0,
            'Sehr geehrter Herr',
            $title,
            $position,
            $email,
            $phone,
            null,
            $address,
            $note
        );
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'emails' => [],
                'phones' => [
                    [
                        'id' => $phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
                    ],
                ],
                'notes' => [
                    [
                        'id' => $note->getId(),
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
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals(0, count($response->emails));

        $this->assertNotNull($response->addresses[0]->country);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
    }

    public function testPutNewAccount()
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
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
            'Note'
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Musterfirma');
        $contact = $this->createContact(
            'Max',
            'Mustermann',
            'CEO',
            new \DateTime(),
            0,
            'Sehr geehrter Herr',
            $title,
            $position,
            $email,
            $phone,
            null,
            $address,
            $note
        );
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'account' => [
                    'id' => $account->getId(),
                ],
                'emails' => [],
                'phones' => [
                    [
                        'id' => $phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
                    ],
                ],
                'notes' => [
                    [
                        'id' => $note->getId(),
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
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals(0, count($response->emails));

        $this->assertEquals($account->getId(), $response->account->id);

        $this->assertEquals($country->getId(), $response->addresses[0]->country);

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

        $this->assertEquals(1, $response->total);

        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);
        $this->assertNull($response->_embedded->contacts[0]->title);

        $this->assertEquals(0, $response->_embedded->contacts[0]->formOfAddress);
        $this->assertNull($response->_embedded->contacts[0]->salutation);
        $this->assertObjectNotHasAttribute('firstName', $response->_embedded->contacts[0]);
    }

    public function testGetListFields()
    {
        $contact = $this->createContact('Max', 'Mustermann');
        $this->em->flush();

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&fields=id,fullName');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals($contact->getId(), $response->_embedded->contacts[0]->id);
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&fields=id,fullName');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals($contact->getId(), $response->_embedded->contacts[0]->id);
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
        $contact = $this->createContact('Max', 'Mustermann');
        $this->em->flush();

        $client = $this->createTestClient();
        $client->request('DELETE', '/api/contacts/' . $contact->getId());

        $this->assertHttpStatusCode(204, $client->getResponse());

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts/' . $contact->getId());

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDeleteNotExisting()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', '/api/contacts/4711');

        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        // Only the test user should be there
        $this->assertEquals(1, $response->total);
    }

    public function testPutRemovedAccount()
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
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
            'Note'
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Musterfirma');
        $contact = $this->createContact(
            'Max',
            'Mustermann',
            'CEO',
            new \DateTime(),
            0,
            'Sehr geehrter Herr',
            $title,
            $position,
            $email,
            $phone,
            null,
            $address,
            $note
        );
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'account' => [
                    'id' => $account->getId(),
                ],
                'emails' => [
                    [
                        'id' => $email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'id' => $phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
                        'note' => 'note',
                    ],
                ],
                'notes' => [
                    [
                        'id' => $note->getId(),
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
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals($account->getId(), $response->account->id);
        $this->assertEquals('john.doe@muster.at', $response->emails[0]->email);
        $this->assertEquals('321654987', $response->phones[0]->phone);
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
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'account' => [
                    'id' => null,
                ],
                'emails' => [
                    [
                        'id' => $email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'phones' => [
                    [
                        'id' => $response->phones[0]->id,
                        'phone' => '321654987',
                        'phoneType' => [
                            'id' => $phoneType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
                        'note' => 'note1',
                    ],
                ],
                'notes' => [
                    [
                        'id' => $note->getId(),
                        'value' => 'Note 1_1',
                    ],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertNull($response->account);
        $this->assertEquals('john.doe@muster.at', $response->emails[0]->email);
        $this->assertEquals('321654987', $response->phones[0]->phone);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('note1', $response->addresses[0]->note);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, count($response->notes));

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertNull($response->salutation);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertNull($response->account);
        $this->assertEquals('john.doe@muster.at', $response->emails[0]->email);
        $this->assertEquals('321654987', $response->phones[0]->phone);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, count($response->notes));

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertNull($response->salutation);
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
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);
        $mediaType = $this->createMediaType('image', 'This is an image');
        $media1 = $this->createMedia('media1.jpeg', 'image/jpeg', $mediaType, $collection);
        $media2 = $this->createMedia('media2.jpeg', 'image/jpeg', $mediaType, $collection);
        $contact = $this->createContact('Max', 'Mustermann');
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request('GET', '/api/contacts/' . $contact->getId());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));

        // add two medias
        $client->request(
            'PATCH',
            '/api/contacts/' . $contact->getId(),
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
            '/api/contacts/' . $contact->getId(),
            [
                'medias' => [],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));

        // missing media
        $client->request(
            'PATCH',
            '/api/contacts/' . $contact->getId(),
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

        $client->request('GET', '/api/contacts/' . $contact->getId());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, count($response->medias));
    }

    public function testPrimaryAddressHandlingPost()
    {
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $addressType = $this->createAddressType('Private');
        $country = $this->createCountry('Musterland', 'ML');
        $account = $this->createAccount('Musterfirma');
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => 'MSc',
                'position' => $position->getId(),
                'account' => [
                    'id' => $account->getId(),
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
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
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
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
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

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($account->getId(), $response['account']['id']);

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
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $addressType = $this->createAddressType('Private');
        $country = $this->createCountry('Musterland', 'ML');
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
            'Note'
        );
        $contact = $this->createContact(
            'Max',
            'Mustermann',
            'CEO',
            new \DateTime(),
            1,
            'Sehr geehrter Herr Dr Mustermann',
            $title,
            $position,
            $email,
            null,
            null,
            $address
        );
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'emails' => [
                    [
                        'id' => $email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
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
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
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
                        'country' => $country->getId(),
                        'addressType' => $addressType->getId(),
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
    }

    public function testPostEmptyBirthday()
    {
        $contact = $this->createContact('Max', 'Mustermann', null, new \DateTime());
        $this->em->flush();

        $client = $this->createTestClient();

        $client->request('GET', '/api/contacts/' . $contact->getId());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotNull($response['birthday']);

        $data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'birthday' => '',
        ];

        $client->request('PUT', '/api/contacts/' . $contact->getId(), $data);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertNull($response['birthday']);
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

    private function createAccount(string $name)
    {
        $account = new Account();
        $account->setName($name);

        $this->em->persist($account);

        return $account;
    }

    private function createContact(
        string $firstName,
        string $lastName,
        ?string $positionName = null,
        ?\DateTime $birthday = null,
        ?int $formOfAddress = null,
        ?string $salutation = null,
        ?ContactTitle $title = null,
        ?Position $position = null,
        ?Email $email = null,
        ?Phone $phone = null,
        ?Fax $fax = null,
        ?Address $address = null,
        ?Note $note = null,
        ?Media $media = null,
        ?array $categories = null
    ) {
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->setPosition($positionName);
        $contact->setBirthday($birthday);
        $contact->setFormOfAddress($formOfAddress);
        $contact->setSalutation($salutation);
        $contact->setTitle($title);
        $contact->setPosition($position);
        if ($email) {
            $contact->addEmail($email);
        }

        if ($phone) {
            $contact->addPhone($phone);
        }

        if ($fax) {
            $contact->addFax($fax);
        }

        if ($note) {
            $contact->addNote($note);
        }

        if ($address) {
            $contactAddress = new ContactAddress();
            $contactAddress->setAddress($address);
            $contactAddress->setContact($contact);
            $contactAddress->setMain(true);
            $contact->addContactAddress($contactAddress);
            $this->em->persist($contactAddress);
        }

        if ($media) {
            $contact->setAvatar($media);
        }

        if ($categories) {
            foreach ($categories as $category) {
                $contact->addCategory($category);
            }
        }

        $this->em->persist($contact);

        return $contact;
    }

    private function createTitle(string $titleName)
    {
        $title = new ContactTitle();
        $title->setTitle($titleName);

        $this->em->persist($title);

        return $title;
    }

    private function createPosition(string $positionName)
    {
        $position = new Position();
        $position->setPosition($positionName);

        $this->em->persist($position);

        return $position;
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

    private function createPhone(string $phoneNumber, PhoneType $phoneType)
    {
        $phone = new Phone();
        $phone->setPhone($phoneNumber);
        $phone->setPhoneType($phoneType);

        $this->em->persist($phone);

        return $phone;
    }

    private function createPhoneType(string $type)
    {
        $phoneType = new PhoneType();
        $phoneType->setName($type);

        $this->em->persist($phoneType);

        return $phoneType;
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

    private function createAddressType(string $type)
    {
        $addressType = new AddressType();
        $addressType->setName($type);

        $this->em->persist($addressType);

        return $addressType;
    }

    private function createAddress(
        ?AddressType $addressType,
        ?string $street,
        ?string $number,
        ?string $zip,
        ?string $city,
        ?string $state,
        ?Country $country,
        ?bool $billingAddress,
        ?bool $primaryAddress,
        ?bool $deliveryAddress,
        ?string $postboxCity,
        ?string $postboxCode,
        ?string $postboxNumber,
        ?string $note
    ) {
        $address = new Address();
        $address->setStreet($street);
        $address->setNumber($number);
        $address->setZip($zip);
        $address->setCity($city);
        $address->setState($state);
        $address->setCountry($country);
        $address->setAddressType($addressType);
        $address->setBillingAddress($billingAddress);
        $address->setPrimaryAddress($primaryAddress);
        $address->setDeliveryAddress($deliveryAddress);
        $address->setPostboxCity($postboxCity);
        $address->setPostboxPostcode($postboxCode);
        $address->setPostboxNumber($postboxNumber);
        $address->setNote($note);

        $this->em->persist($address);

        return $address;
    }

    private function createCountry(string $name, string $code)
    {
        $country = new Country();
        $country->setName($name);
        $country->setCode($code);

        $this->em->persist($country);

        return $country;
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

    private function createCategory(string $key, string $locale, string $name, string $description)
    {
        $category = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category->setKey($name);
        $category->setDefaultLocale($locale);

        $this->category = $category;

        // name for first category
        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale($locale);
        $categoryTrans->setTranslation($name);
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        // meta for first category
        $categoryMeta = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta->setLocale($locale);
        $categoryMeta->setKey('description');
        $categoryMeta->setValue($description);
        $categoryMeta->setCategory($category);
        $category->addMeta($categoryMeta);

        $this->em->persist($category);

        return $category;
    }
}

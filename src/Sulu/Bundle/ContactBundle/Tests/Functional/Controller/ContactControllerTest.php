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

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfile;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfileType;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ContactControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
    }

    public function testGetById(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('max.mustermann@muster.at', $faxType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
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
            null,
            null,
            $address,
            $note,
            $media
        );

        $category = $this->createCategory('first-category-key', 'en', 'First Category', 'Description of Category');
        $category2 = $this->createCategory('second-category-key', 'en', 'Second Category', 'Description of second Category');

        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contacts/' . $contact->getId());

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('Max', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('Max Mustermann', $response->fullName);
        $this->assertEquals('123456789', $response->contactDetails->phones[0]->phone);
        $this->assertEquals($phoneType->getId(), $response->contactDetails->phones[0]->phoneType);
        $this->assertEquals('123654789', $response->contactDetails->faxes[0]->fax);
        $this->assertEquals($faxType->getId(), $response->contactDetails->faxes[0]->faxType);
        $this->assertEquals('max.mustermann@muster.at', $response->contactDetails->emails[0]->email);
        $this->assertEquals($emailType->getId(), $response->contactDetails->emails[0]->emailType);
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

        $this->assertTrue(\property_exists($response, 'avatar'));
        $this->assertTrue(\property_exists($response->avatar, 'thumbnails'));
        $this->assertTrue(\property_exists($response->avatar->thumbnails, 'sulu-100x100'));
        $this->assertTrue(\is_string($response->avatar->thumbnails->{'sulu-100x100'}));

        $this->assertEquals(1, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter Herr Dr Mustermann', $response->salutation);
    }

    public function testPostAccountIDNull(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $phoneType = $this->createPhoneType('Private');
        $addressType = $this->createAddressType('Private');
        $this->em->flush();

        $this->client->jsonRequest(
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
                'contactDetails' => [
                    'emails' => [
                        [
                            'email' => 'erika.mustermann@muster.at',
                            'emailType' => $emailType->getId(),
                        ],
                        [
                            'email' => 'erika.mustermann@muster.de',
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'phone' => '123456789',
                            'phoneType' => $phoneType->getId(),
                        ],
                        [
                            'phone' => '987654321',
                            'phoneType' => $phoneType->getId(),
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals('erika.mustermann@muster.at', $response->contactDetails->emails[0]->email);
        $this->assertEquals($emailType->getId(), $response->contactDetails->emails[0]->emailType);
        $this->assertEquals('erika.mustermann@muster.de', $response->contactDetails->emails[1]->email);
        $this->assertEquals($emailType->getId(), $response->contactDetails->emails[1]->emailType);
        $this->assertEquals('123456789', $response->contactDetails->phones[0]->phone);
        $this->assertEquals($phoneType->getId(), $response->contactDetails->phones[0]->phoneType);
        $this->assertEquals('987654321', $response->contactDetails->phones[1]->phone);
        $this->assertEquals($phoneType->getId(), $response->contactDetails->phones[1]->phoneType);
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

        $this->client->jsonRequest('GET', '/api/contacts/' . $response->id);
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals('erika.mustermann@muster.at', $response->contactDetails->emails[0]->email);
        $this->assertEquals('erika.mustermann@muster.de', $response->contactDetails->emails[1]->email);
        $this->assertEquals('123456789', $response->contactDetails->phones[0]->phone);
        $this->assertEquals('987654321', $response->contactDetails->phones[1]->phone);
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

    public function testPostCategoryNull(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'formOfAddress' => 0,
                'categories' => null,
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEmpty($response->categories);
    }

    public function testPost(): void
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
        $addressType = $this->createAddressType('Private');
        $account = $this->createAccount('Musterfirma');
        $category1 = $this->createCategory('first-category-key', 'en', 'First Category', 'Description of Category');
        $category2 = $this->createCategory('second-category-key', 'en', 'Second Category', 'Description of second Category');
        $this->em->flush();

        $this->client->jsonRequest(
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
                'contactDetails' => [
                    'emails' => [
                        [
                            'email' => 'erika.mustermann@muster.at',
                            'emailType' => $emailType->getId(),
                        ],
                        [
                            'email' => 'erika.mustermann@muster.de',
                            'emailType' => $emailType->getId(),
                        ],
                        [
                            'email' => null,
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'phone' => '123456789',
                            'phoneType' => $phoneType->getId(),
                        ],
                        [
                            'phone' => '987654321',
                            'phoneType' => $phoneType->getId(),
                        ],
                        [
                            'phone' => null,
                            'phoneType' => $phoneType->getId(),
                        ],
                    ],
                    'faxes' => [
                        [
                            'fax' => '123456789-1',
                            'faxType' => $faxType->getId(),
                        ],
                        [
                            'fax' => '987654321-1',
                            'faxType' => $faxType->getId(),
                        ],
                        [
                            'fax' => null,
                            'faxType' => $faxType->getId(),
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals($position->getId(), $response->position);
        $this->assertEquals($position->getPosition(), $response->positionName);
        $this->assertEquals('erika.mustermann@muster.at', $response->contactDetails->emails[0]->email);
        $this->assertEquals('erika.mustermann@muster.de', $response->contactDetails->emails[1]->email);
        $this->assertEquals('123456789', $response->contactDetails->phones[0]->phone);
        $this->assertEquals('987654321', $response->contactDetails->phones[1]->phone);
        $this->assertEquals('123456789-1', $response->contactDetails->faxes[0]->fax);
        $this->assertEquals('987654321-1', $response->contactDetails->faxes[1]->fax);
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

        $this->assertTrue(\property_exists($response, 'avatar'));
        $this->assertTrue(\property_exists($response->avatar, 'thumbnails'));
        $this->assertTrue(\property_exists($response->avatar->thumbnails, 'sulu-100x100'));
        $this->assertTrue(\is_string($response->avatar->thumbnails->{'sulu-100x100'}));

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);

        $this->assertEquals(2, \count($response->categories));
        $this->assertEquals($category1->getId(), $response->categories[0]);
        $this->assertEquals($category2->getId(), $response->categories[1]);
    }

    public function testPostWithAccountWithoutPosition(): void
    {
        $collectionType = $this->createCollectionType('My collection type');
        $account = $this->createAccount('Musterfirma');
        $this->em->flush();

        $this->client->jsonRequest(
            'POST',
            '/api/contacts',
            [
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'formOfAddress' => 1,
                'account' => [
                    'id' => $account->getId(),
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals(1, $response->formOfAddress);
        $this->assertEquals($account->getid(), $response->account->id);
    }

    public function testPostEmptyAddress(): void
    {
        $addressType = $this->createAddressType('Private');
        $this->em->flush();

        $this->client->jsonRequest(
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

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertCount(1, $response->addresses);
    }

    public function testPostWithoutBankNameAndBic(): void
    {
        $addressType = $this->createAddressType('Private');
        $this->em->flush();

        $this->client->jsonRequest(
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

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertCount(1, $response->bankAccounts);
        $this->assertEquals('DE89370400440532013000', $response->bankAccounts[0]->iban);
        $this->assertNull($response->bankAccounts[0]->bic);
    }

    public function testPostEmptyLatitude(): void
    {
        $title = $this->createTitle('MSc');
        $addressType = $this->createAddressType('Private');
        $this->em->flush();

        $this->client->jsonRequest(
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Erika', $response['firstName']);
        $this->assertEquals('Mustermann', $response['lastName']);

        $this->assertEquals('Home', $response['addresses'][0]['title']);
        $this->assertEquals(47.4049309, $response['addresses'][0]['latitude']);
        $this->assertNull($response['addresses'][0]['longitude']);
    }

    public function testPostWithoutAdditionalData(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $this->em->flush();

        $this->client->jsonRequest(
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

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
    }

    public function testPostWithoutFormOfAddress(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $this->em->flush();

        $this->client->jsonRequest(
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

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\ContactBundle\Entity\Contact"-entity requires a "formOfAddress"-argument',
            $response->message
        );
    }

    public function testPostWithEmptyAdditionalData(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $this->em->flush();

        $this->client->jsonRequest(
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

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);

        $this->client->jsonRequest('GET', '/api/contacts/' . $response->id);
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);
    }

    public function testGetListSearchEmpty(): void
    {
        $this->client->jsonRequest('GET', '/api/contacts?flat=true&search=Nothing&searchFields=fullName');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(0, $response->total);
        $this->assertEquals(0, \count($response->_embedded->contacts));
    }

    public function testGetListSearch(): void
    {
        $contact1 = new Contact();
        $contact1->setFirstName('Erika');
        $contact1->setLastName('Mustermann');
        $this->em->persist($contact1);
        $this->em->flush();

        // dont use max here because the user for tests also is called max

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&search=Erika&searchFields=fullName&fields=fullName');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, \count($response->_embedded->contacts));
        $this->assertEquals('Erika Mustermann', $response->_embedded->contacts[0]->fullName);
    }

    public function testGetListFilter(): void
    {
        $contact1 = new Contact();
        $contact1->setFirstName('Erika');
        $contact1->setLastName('Mustermann');
        $contact1->setSalutation('Frau');
        $this->em->persist($contact1);

        $contact2 = new Contact();
        $contact2->setFirstName('John');
        $contact2->setLastName('Doe');
        $contact2->setSalutation('Mann');
        $this->em->persist($contact2);
        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&fields=fullName&filter[salutation][eq]=Mann');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, \count($response->_embedded->contacts));
        $this->assertEquals('John Doe', $response->_embedded->contacts[0]->fullName);
    }

    public function testGetList(): void
    {
        $this->purgeDatabase();

        $category1 = $this->createCategory('first-category-key', 'en', 'First Category', 'Description of Category');
        $category2 = $this->createCategory('second-category-key', 'en', 'Second Category', 'Description of Category');

        $contact1 = new Contact();
        $contact1->setFirstName('Erika');
        $contact1->setLastName('Mustermann');
        $contact1->setSalutation('Frau');
        $contact1->addCategory($category1);
        $contact1->addCategory($category2);
        $this->em->persist($contact1);

        $contact2 = new Contact();
        $contact2->setFirstName('John');
        $contact2->setLastName('Doe');
        $contact2->setSalutation('Mann');
        $contact2->addCategory($category1);
        $this->em->persist($contact2);
        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&fields=fullName,categoryNames');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);
        $this->assertEquals(3, \count($response->_embedded->contacts));
        $this->assertEquals('Erika Mustermann', $response->_embedded->contacts[0]->fullName);

        $categoryNames = \explode(',', $response->_embedded->contacts[0]->categoryNames);
        $this->assertCount(2, $categoryNames);
        $this->assertContains('First Category', $categoryNames);
        $this->assertContains('Second Category', $categoryNames);
        $this->assertEquals('John Doe', $response->_embedded->contacts[1]->fullName);
        $this->assertEquals('First Category', $response->_embedded->contacts[1]->categoryNames);

        // Following contact is created as contact for the user which is used in the client
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[2]->fullName);
        $this->assertEquals(null, $response->_embedded->contacts[2]->categoryNames);
    }

    public function testGetListSearchWithExcludedAccountId(): void
    {
        $account1 = $this->createAccount('Musterfirma 1');
        $account2 = $this->createAccount('Musterfirma 2');
        $account3 = $this->createAccount('Musterfirma 3');

        $contact1 = new Contact();
        $contact1->setFirstName('Erika');
        $contact1->setLastName('Mustermann');
        $accountContact1 = new AccountContact();
        $accountContact1->setMain(false);
        $accountContact1->setContact($contact1);
        $accountContact1->setAccount($account1);
        $contact1->addAccountContact($accountContact1);
        $accountContact2 = new AccountContact();
        $accountContact2->setMain(false);
        $accountContact2->setContact($contact1);
        $accountContact2->setAccount($account2);
        $contact1->addAccountContact($accountContact2);

        $this->em->persist($account1);
        $this->em->persist($account2);
        $this->em->persist($account3);
        $this->em->persist($contact1);
        $this->em->persist($accountContact1);
        $this->em->persist($accountContact2);

        $this->em->flush();

        // dont use max here because the user for tests also is called max

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&search=Erika&excludedAccountId=' . $account1->getId());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->_embedded->contacts));

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&search=Erika&excludedAccountId=' . $account2->getId());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->_embedded->contacts));

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&search=Erika&excludedAccountId=' . $account3->getId());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(1, \count($response->_embedded->contacts));
        $this->assertEquals('Erika Mustermann', $response->_embedded->contacts[0]->fullName);
    }

    public function testGetListByAccountId(): void
    {
        $account1 = $this->createAccount('Musterfirma 1');
        $this->em->persist($account1);

        $account2 = $this->createAccount('Musterfirma 2');
        $this->em->persist($account2);

        $contact1 = new Contact();
        $contact1->setFirstName('Erika');
        $contact1->setLastName('Mustermann');
        $accountContact1 = new AccountContact();
        $accountContact1->setMain(true);
        $accountContact1->setContact($contact1);
        $accountContact1->setAccount($account1);
        $contact1->addAccountContact($accountContact1);
        $accountContact2 = new AccountContact();
        $accountContact2->setMain(false);
        $accountContact2->setContact($contact1);
        $accountContact2->setAccount($account2);
        $contact1->addAccountContact($accountContact2);
        $this->em->persist($accountContact1);
        $this->em->persist($accountContact2);
        $this->em->persist($contact1);

        $contact2 = new Contact();
        $contact2->setFirstName('Max');
        $contact2->setLastName('Mustermann');
        $accountContact3 = new AccountContact();
        $accountContact3->setMain(true);
        $accountContact3->setContact($contact2);
        $accountContact3->setAccount($account2);
        $contact2->addAccountContact($accountContact3);
        $this->em->persist($accountContact3);
        $this->em->persist($contact2);

        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&accountId=' . $account1->getId());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, \count($response->_embedded->contacts));
        $this->assertEquals('Erika Mustermann', $response->_embedded->contacts[0]->fullName);
    }

    public function testPut(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('max.mustermann@muster.at', $faxType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
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
            null,
            null,
            $address,
            $note,
            null,
            [$category1, $category2]
        );

        $this->em->flush();

        $putData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'note' => 'A small notice',
            'title' => $title->getId(),
            'position' => $position->getId(),
            'account' => ['id' => $account->getId()],
            'avatar' => [
                'id' => $media->getId(),
            ],
            'birthday' => '1980-01-01T00:00:00',
            'contactDetails' => [
                'emails' => [
                    [
                        'id' => $email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => $emailType->getId(),
                    ],
                    [
                        'email' => 'john.doe@muster.de',
                        'emailType' => $emailType->getId(),
                    ],
                ],
                'phones' => [
                    [
                        'id' => $phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => $phoneType->getId(),
                    ],
                    [
                        'phone' => '789456123',
                        'phoneType' => $phoneType->getId(),
                    ],
                ],
                'faxes' => [
                    [
                        'id' => $fax->getId(),
                        'fax' => '321654987-1',
                        'faxType' => $faxType->getId(),
                    ],
                    [
                        'fax' => '789456123-1',
                        'faxType' => $faxType->getId(),
                    ],
                ],
                'socialMedia' => [],
                'websites' => [],
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
                    'countryCode' => 'ML',
                    'addressType' => $addressType->getId(),
                    'billingAddress' => true,
                    'primaryAddress' => true,
                    'deliveryAddress' => false,
                    'postboxCity' => 'Dornbirn',
                    'postboxPostcode' => '6850',
                    'postboxNumber' => '4711',
                    'note' => 'note',
                    'addition' => 'addition',
                    'latitude' => 20.5,
                    'longitude' => 30.7,
                ],
            ],
            'notes' => [
                [
                    'id' => $note->getId(),
                    'value' => 'Note 1_1',
                ],
            ],
            'salutation' => 'Sehr geehrter John',
            'formOfAddress' => 0,
            'categories' => [
                $category3->getId(),
            ],
            'tags' => ['Tag A', 'Tag B'],
        ];

        $this->client->jsonRequest(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            $putData,
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        $this->assertIsArray($response);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        unset($response['_hash']);
        unset($response['id']);
        $accountId = $response['account']['id'] ?? null;
        unset($response['account']);
        $response['account']['id'] = $accountId;
        unset($response['created']);
        unset($response['changed']);
        unset($response['locales']);
        unset($response['middleName']);
        unset($response['fullName']);
        unset($response['titleName']);
        unset($response['avatar']['url']);
        unset($response['avatar']['thumbnails']);
        // TODO add more data do tests:
        unset($response['bankAccounts']);
        unset($response['newsletter']);
        unset($response['gender']);
        unset($response['mainEmail']);
        unset($response['mainPhone']);
        unset($response['mainFax']);
        unset($response['mainUrl']);
        unset($response['mainAddress']);
        unset($response['positionName']);
        unset($response['medias']);
        unset($response['contactDetails']['emails'][1]['id']);
        unset($response['contactDetails']['phones'][1]['id']);
        unset($response['contactDetails']['faxes'][1]['id']);

        $this->assertEquals($putData, $response);
    }

    public function testPutEmptyContactDetails(): void
    {
        $emailType = $this->createEmailType('Private');
        $faxType = $this->createFaxType('Private');
        $phoneType = $this->createPhoneType('Private');
        $socialMediaType = $this->createSocialMediaType('Private');
        $websiteType = $this->createWebsiteType('Private');

        $contact = $this->createContact(
            'Max',
            'Mustermann',
            'CEO',
            new \DateTime(),
            0,
            'Sehr geehrter Herr'
        );

        $this->em->flush();

        $this->client->jsonRequest(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'contactDetails' => [
                    'emails' => [
                        [
                            'email' => null,
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'phone' => null,
                            'phoneType' => $phoneType->getId(),
                        ],
                    ],
                    'faxes' => [
                        [
                            'fax' => null,
                            'faxType' => $faxType->getId(),
                        ],
                    ],
                    'socialMedia' => [
                        [
                            'socialMediaType' => $socialMediaType->getId(),
                            'username' => null,
                        ],
                    ],
                    'websites' => [
                        [
                            'website' => null,
                            'websiteType' => $websiteType->getId(),
                        ],
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEmpty($response->contactDetails->emails);
        $this->assertEmpty($response->contactDetails->phones);
        $this->assertEmpty($response->contactDetails->faxes);
        $this->assertEmpty($response->contactDetails->socialMedia);
        $this->assertEmpty($response->contactDetails->websites);
    }

    public function testPutEmptyContactDetailsOnExistingValues(): void
    {
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('max.mustermann@muster.at', $faxType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $socialMediaType = $this->createSocialMediaType('Private');
        $socialMedia = $this->createSocialMedia('muster', $socialMediaType);
        $websiteType = $this->createWebsiteType('Private');
        $website = $this->createWebsite('http://www.muster.at', $websiteType);

        $contact = $this->createContact(
            'Max',
            'Mustermann',
            'CEO',
            new \DateTime(),
            0,
            'Sehr geehrter Herr',
            null,
            null,
            $email,
            $phone,
            $fax,
            $socialMedia,
            $website
        );

        $this->em->flush();

        $this->client->jsonRequest(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'contactDetails' => [
                    'emails' => [
                        [
                            'id' => $email->getId(),
                            'email' => null,
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'id' => $phone->getId(),
                            'phone' => null,
                            'phoneType' => $phoneType->getId(),
                        ],
                    ],
                    'faxes' => [
                        [
                            'id' => $fax->getId(),
                            'fax' => null,
                            'faxType' => $faxType->getId(),
                        ],
                    ],
                    'socialMedia' => [
                        [
                            'id' => $socialMedia->getId(),
                            'socialMediaType' => $socialMediaType->getId(),
                            'username' => null,
                        ],
                    ],
                    'websites' => [
                        [
                            'id' => $website->getId(),
                            'website' => null,
                            'websiteType' => $websiteType->getId(),
                        ],
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEmpty($response->contactDetails->emails);
        $this->assertEmpty($response->contactDetails->phones);
        $this->assertEmpty($response->contactDetails->faxes);
        $this->assertEmpty($response->contactDetails->socialMedia);
        $this->assertEmpty($response->contactDetails->websites);
    }

    public function testPutDeleteAndAddWithoutId(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('max.mustermann@muster.at', $faxType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
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
            null,
            null,
            $address,
            $note
        );

        $this->em->flush();

        $this->client->jsonRequest(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'contactDetails' => [
                    'emails' => [
                        [
                            'email' => 'john.doe@muster.de',
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'phone' => '789456123',
                            'phoneType' => $phoneType->getId(),
                        ],
                    ],
                    'faxes' => [
                        [
                            'fax' => '147258369-1',
                            'faxType' => $faxType->getId(),
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals('john.doe@muster.de', $response->contactDetails->emails[0]->email);
        $this->assertEquals('789456123', $response->contactDetails->phones[0]->phone);
        $this->assertEquals('147258369-1', $response->contactDetails->faxes[0]->fax);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, \count($response->notes));

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
    }

    public function testPutNoEmail(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
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
            null,
            null,
            $address,
            $note
        );

        $this->em->flush();

        $this->client->jsonRequest(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'contactDetails' => [
                    'emails' => [],
                    'phones' => [
                        [
                            'id' => $phone->getId(),
                            'phone' => '321654987',
                            'phoneType' => $phoneType->getId(),
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals(0, \count($response->contactDetails->emails));

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

    public function testPutNewCountry(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
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
            null,
            null,
            $address,
            $note
        );
        $this->em->flush();

        $this->client->jsonRequest(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $title->getId(),
                'position' => $position->getId(),
                'contactDetails' => [
                    'emails' => [],
                    'phones' => [
                        [
                            'id' => $phone->getId(),
                            'phone' => '321654987',
                            'phoneType' => $phoneType->getId(),
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals(0, \count($response->contactDetails->emails));

        $this->assertEquals('ML', $response->addresses[0]->countryCode);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
    }

    public function testPutNewAccount(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
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
            null,
            null,
            $address,
            $note
        );
        $this->em->flush();

        $this->client->jsonRequest(
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
                'contactDetails' => [
                    'emails' => [],
                    'phones' => [
                        [
                            'id' => $phone->getId(),
                            'phone' => '321654987',
                            'phoneType' => $phoneType->getId(),
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals(0, \count($response->contactDetails->emails));

        $this->assertEquals($account->getId(), $response->account->id);

        $this->assertEquals('ML', $response->addresses[0]->countryCode);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
    }

    public function testPutReplaceAccount(): void
    {
        $accountOld = $this->createAccount('Musterfirma Old');
        $accountNew = $this->createAccount('Musterfirma New');
        $contact = $this->createContact(
            'Max',
            'Mustermann'
        );
        $accountContact = new AccountContact();
        $accountContact->setAccount($accountOld);
        $accountContact->setContact($contact);
        $accountContact->setMain(true);
        $contact->addAccountContact($accountContact);

        $this->em->flush();

        $this->client->jsonRequest(
            'PUT',
            '/api/contacts/' . $contact->getId(),
            [
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
                'account' => [
                    'id' => $accountNew->getId(),
                ],
            ]
        );

        $contact = $this->getEntityManager()->find(ContactInterface::class, $contact->getId());
        $this->assertSame('Musterfirma New', $contact->getMainAccount()->getName());
        $this->assertCount(1, $contact->getAccountContacts());
    }

    public function testPutNotExisting(): void
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/contacts/10113',
            [
                'firstName' => 'John',
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGetListFields(): void
    {
        $contact = $this->createContact('Max', 'Mustermann');
        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&fields=id,fullName');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals($contact->getId(), $response->_embedded->contacts[0]->id);
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&fields=id,fullName');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals($contact->getId(), $response->_embedded->contacts[0]->id);
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);
    }

    public function testGetListIds(): void
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

        $ids = \sprintf('%s,%s,%s', $contact1->getId(), $contact2->getId(), $contact3->getId());

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&ids=' . $ids . '&fields=id');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);

        $this->assertEquals($contact1->getId(), $response->_embedded->contacts[0]->id);
        $this->assertEquals($contact2->getId(), $response->_embedded->contacts[1]->id);
        $this->assertEquals($contact3->getId(), $response->_embedded->contacts[2]->id);
    }

    public function testGetListIdsEmpty(): void
    {
        $this->client->jsonRequest('GET', '/api/contacts?flat=true&ids=');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertCount(0, $response->_embedded->contacts);
    }

    public function testGetListIdsOrder(): void
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

        $ids = \sprintf('%s,%s,%s', $contact3->getId(), $contact1->getId(), $contact2->getId());

        $this->client->jsonRequest('GET', '/api/contacts?flat=true&ids=' . $ids . '&fields=id');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);

        $this->assertEquals($contact3->getId(), $response->_embedded->contacts[0]->id);
        $this->assertEquals($contact1->getId(), $response->_embedded->contacts[1]->id);
        $this->assertEquals($contact2->getId(), $response->_embedded->contacts[2]->id);
    }

    public function testGetListOrderByCategoryNames(): void
    {
        $category1 = $this->createCategory('category-1', 'de', 'Category 1', 'description');
        $category2 = $this->createCategory('category-2', 'de', 'Category 2', 'description');
        $contact1 = $this->createContact('Erika', 'Test', categories: [$category1]);
        $contact2 = $this->createContact('Anne', 'Test', categories: [$category1, $category2]);
        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contacts?sortBy=categoryNames&sortOrder=asc&flat=true&fields=id,categoryNames');

        /**
         * @var array{
         *    total: int,
         *    _embedded: array{
         *        contacts: array<array{
         *            id: string,
         *            categoryNames: string,
         *        }>
         *    }
         * } $response
         */
        $response = \json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertEquals(3, $response['total']); // total is 3 because a test user contact is also created automatically
        $this->assertEquals($contact1->getId(), $response['_embedded']['contacts'][1]['id']);
        $this->assertEquals('Category 1', $response['_embedded']['contacts'][1]['categoryNames']);
        $this->assertEquals($contact2->getId(), $response['_embedded']['contacts'][2]['id']);
        $this->assertStringContainsString('Category 1', $response['_embedded']['contacts'][2]['categoryNames']);
        $this->assertStringContainsString('Category 2', $response['_embedded']['contacts'][2]['categoryNames']);

        $this->client->jsonRequest('GET', '/api/contacts?sortBy=categoryNames&sortOrder=desc&flat=true&fields=id,categoryNames');

        /**
         * @var array{
         *    total: int,
         *    _embedded: array{
         *        contacts: array<array{
         *            id: string,
         *            categoryNames: string,
         *        }>
         *    }
         * } $response
         */
        $response = \json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertEquals(3, $response['total']); // total is 3 because a test user contact is also created automatically
        $this->assertEquals($contact2->getId(), $response['_embedded']['contacts'][0]['id']);
        $this->assertStringContainsString('Category 1', $response['_embedded']['contacts'][0]['categoryNames']);
        $this->assertStringContainsString('Category 2', $response['_embedded']['contacts'][0]['categoryNames']);
        $this->assertEquals($contact1->getId(), $response['_embedded']['contacts'][1]['id']);
        $this->assertEquals('Category 1', $response['_embedded']['contacts'][1]['categoryNames']);
    }

    public function testDelete(): void
    {
        $contact = $this->createContact('Max', 'Mustermann');
        $this->em->flush();

        $contactId = $contact->getId();

        $this->client->jsonRequest('DELETE', '/api/contacts/' . $contactId);

        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/contacts/' . $contactId);

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $trashItemRepository = $this->em->getRepository(TrashItemInterface::class);

        $trashItem = $trashItemRepository->findOneBy(['resourceKey' => 'contacts', 'resourceId' => $contactId]);
        $this->assertNotNull($trashItem);
    }

    public function testDeleteNotExisting(): void
    {
        $this->client->jsonRequest('DELETE', '/api/contacts/4711');

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/contacts?flat=true');
        $response = \json_decode($this->client->getResponse()->getContent());

        // Only the test user should be there
        $this->assertEquals(1, $response->total);
    }

    public function testPutRemovedAccount(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
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
            null,
            null,
            $address,
            $note
        );
        $this->em->flush();

        $this->client->jsonRequest(
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
                'contactDetails' => [
                    'emails' => [
                        [
                            'id' => $email->getId(),
                            'email' => 'john.doe@muster.at',
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'id' => $phone->getId(),
                            'phone' => '321654987',
                            'phoneType' => $phoneType->getId(),
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertEquals($account->getId(), $response->account->id);
        $this->assertEquals('john.doe@muster.at', $response->contactDetails->emails[0]->email);
        $this->assertEquals('321654987', $response->contactDetails->phones[0]->phone);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, \count($response->notes));

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);

        $this->client->jsonRequest(
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
                'contactDetails' => [
                    'emails' => [
                        [
                            'id' => $email->getId(),
                            'email' => 'john.doe@muster.at',
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'id' => $response->contactDetails->phones[0]->id,
                            'phone' => '321654987',
                            'phoneType' => $phoneType->getId(),
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertNull($response->account);
        $this->assertEquals('john.doe@muster.at', $response->contactDetails->emails[0]->email);
        $this->assertEquals('321654987', $response->contactDetails->phones[0]->phone);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('note1', $response->addresses[0]->note);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, \count($response->notes));

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertNull($response->salutation);

        $this->client->jsonRequest('GET', '/api/contacts/' . $response->id);
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals($title->getId(), $response->title);
        $this->assertNull($response->account);
        $this->assertEquals('john.doe@muster.at', $response->contactDetails->emails[0]->email);
        $this->assertEquals('321654987', $response->contactDetails->phones[0]->phone);
        $this->assertEquals('Street', $response->addresses[0]->street);
        $this->assertEquals('2', $response->addresses[0]->number);
        $this->assertEquals('9999', $response->addresses[0]->zip);
        $this->assertEquals('Springfield', $response->addresses[0]->city);
        $this->assertEquals('Colorado', $response->addresses[0]->state);
        $this->assertEquals('Note 1_1', $response->notes[0]->value);
        $this->assertEquals(1, \count($response->notes));

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertNull($response->salutation);
    }

    public function testPatchNotExisting(): void
    {
        $this->client->jsonRequest(
            'PATCH',
            '/api/contacts/101',
            [
                'medias' => [],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPatchAssignedMedias(): void
    {
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);
        $mediaType = $this->createMediaType('image', 'This is an image');
        $media1 = $this->createMedia('media1.jpeg', 'image/jpeg', $mediaType, $collection);
        $media2 = $this->createMedia('media2.jpeg', 'image/jpeg', $mediaType, $collection);
        $contact = $this->createContact('Max', 'Mustermann');
        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contacts/' . $contact->getId());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->medias));

        // add two medias
        $this->client->jsonRequest(
            'PATCH',
            '/api/contacts/' . $contact->getId(),
            [
                'medias' => [
                    $media1->getId(),
                    $media2->getId(),
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $response->medias);

        // remove medias
        $this->client->jsonRequest(
            'PATCH',
            '/api/contacts/' . $contact->getId(),
            [
                'medias' => [],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->medias));

        // missing media
        $this->client->jsonRequest(
            'PATCH',
            '/api/contacts/' . $contact->getId(),
            [
                'medias' => [
                    $media1->getId(),
                    101,
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/contacts/' . $contact->getId());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->medias));
    }

    public function testPrimaryAddressHandlingPost(): void
    {
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $addressType = $this->createAddressType('Private');
        $account = $this->createAccount('Musterfirma');
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
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
                        'countryCode' => 'ML',
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($account->getId(), $response['account']['id']);

        $addresses = $response['addresses'];

        $filterKeys = [
            'primaryAddress',
            'street',
        ];

        $filteredAddresses = \array_map(
            function($address) use ($filterKeys) {
                return \array_intersect_key($address, \array_flip($filterKeys));
            },
            $addresses
        );

        $this->assertContains(
            [
                'street' => 'Musterstraße',
                'primaryAddress' => false,
            ],
            $filteredAddresses
        );

        $this->assertContains(
            [
                'street' => 'Musterstraße 2',
                'primaryAddress' => true,
            ],
            $filteredAddresses
        );
    }

    public function testPrimaryAddressHandlingPut(): void
    {
        $title = $this->createTitle('MSc');
        $position = $this->createPosition('Manager');
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('max.mustermann@muster.at', $emailType);
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
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
            null,
            null,
            $address
        );
        $this->em->flush();

        $this->client->jsonRequest(
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
                        'countryCode' => 'ML',
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
                        'countryCode' => 'ML',
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
                        'countryCode' => 'ML',
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

        $response = \json_decode($this->client->getResponse()->getContent());
        \usort($response->addresses, $this->sortAddressesPrimaryLast());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[1]->primaryAddress);
        $this->assertEquals(true, $response->addresses[2]->primaryAddress);
    }

    public function testPostEmptyBirthday(): void
    {
        $contact = $this->createContact('Max', 'Mustermann', null, new \DateTime());
        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contacts/' . $contact->getId());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotNull($response['birthday']);

        $data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'birthday' => '',
        ];

        $this->client->jsonRequest('PUT', '/api/contacts/' . $contact->getId(), $data);
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNull($response['birthday']);
    }

    public function sortAddressesPrimaryLast()
    {
        return function($a, $b) {
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
        ?SocialMediaProfile $socialMedia = null,
        ?Url $url = null,
        ?Address $address = null,
        ?Note $note = null,
        ?Media $media = null,
        ?array $categories = null
    ) {
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
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

        if ($socialMedia) {
            $contact->addSocialMediaProfile($socialMedia);
        }

        if ($url) {
            $contact->addUrl($url);
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
        $faxType->setName($type);

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

    private function createSocialMediaType(string $type)
    {
        $socialMediaType = new SocialMediaProfileType();
        $socialMediaType->setName($type);

        $this->em->persist($socialMediaType);

        return $socialMediaType;
    }

    private function createSocialMedia(string $username, SocialMediaProfileType $socialMediaType)
    {
        $socialMedia = new SocialMediaProfile();
        $socialMedia->setUsername($username);
        $socialMedia->setSocialMediaProfileType($socialMediaType);

        $this->em->persist($socialMedia);

        return $socialMedia;
    }

    private function createWebsiteType(string $type)
    {
        $websiteType = new UrlType();
        $websiteType->setName($type);

        $this->em->persist($websiteType);

        return $websiteType;
    }

    private function createWebsite(string $url, UrlType $websiteType)
    {
        $website = new Url();
        $website->setUrl($url);
        $website->setUrlType($websiteType);

        $this->em->persist($website);

        return $website;
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
        ?string $countryCode,
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
        $address->setCountryCode($countryCode);
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
        $fileVersion->setChanged(new \DateTimeImmutable('1950-04-20'));
        $fileVersion->setCreated(new \DateTimeImmutable('1950-04-20'));
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

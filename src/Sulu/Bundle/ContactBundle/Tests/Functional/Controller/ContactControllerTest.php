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

use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMeta;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
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
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ContactControllerTest extends SuluTestCase
{
    private $contactPosition = null;
    private $contactTitle = null;

    public function setUp()
    {
        $this->em = $this->db('ORM')->getOm();
        $this->initOrm();
    }

    private function initOrm()
    {
        $this->purgeDatabase();
        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setPosition('CEO');
        $contact->setFormOfAddress(1);
        $contact->setSalutation('Sehr geehrter Herr Dr Mustermann');
        $contact->setDisabled(0);

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

        $contact->addContactAddresse($contactAddress);
        $address->addContactAddresse($contactAddress);

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
        $category = new Category();
        $category->setKey('first-category-key');

        $this->category = $category;

        // name for first category
        $categoryTrans = new CategoryTranslation();
        $categoryTrans->setLocale('en');
        $categoryTrans->setTranslation('First Category');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        // meta for first category
        $categoryMeta = new CategoryMeta();
        $categoryMeta->setLocale('en');
        $categoryMeta->setKey('description');
        $categoryMeta->setValue('Description of Category');
        $categoryMeta->setCategory($category);
        $category->addMeta($categoryMeta);

        $this->em->persist($category);

        /* Second Category
        -------------------------------------*/
        $category2 = new Category();
        $category2->setKey('second-category-key');

        $this->category2 = $category2;

        // name for second category
        $categoryTrans2 = new CategoryTranslation();
        $categoryTrans2->setLocale('de');
        $categoryTrans2->setTranslation('Second Category');
        $categoryTrans2->setCategory($category2);
        $category2->addTranslation($categoryTrans2);

        // meta for second category
        $categoryMeta2 = new CategoryMeta();
        $categoryMeta2->setLocale('de');
        $categoryMeta2->setKey('description');
        $categoryMeta2->setValue('Description of second Category');
        $categoryMeta2->setCategory($category2);
        $category2->addMeta($categoryMeta2);

        $this->em->persist($category2);

        $this->em->flush();

        $this->contactTitle = $title;
        $this->contactPosition = $position;
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

        $this->assertEquals(1, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter Herr Dr Mustermann', $response->salutation);
        $this->assertEquals(0, $response->disabled);
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

    public function testPostAccountIDNull()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            array(
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'account' => array(
                    'id' => null,
                ),
                'emails' => array(
                    array(
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'phones' => array(
                    array(
                        'phone' => '123456789',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '987654321',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                    ),
                ),
                'notes' => array(
                    array('value' => 'Note 1'),
                    array('value' => 'Note 2'),
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

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
        $this->assertEquals(0, $response->disabled);

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
        $this->assertEquals(0, $response->disabled);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);
        $this->assertEquals(0, $response->disabled);
    }

    public function testPost()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            array(
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'account' => array(
                    'id' => $this->account1->getId(),
                ),
                'emails' => array(
                    array(
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'phones' => array(
                    array(
                        'phone' => '123456789',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '987654321',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'faxes' => array(
                    array(
                        'fax' => '123456789-1',
                        'faxType' => array(
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'fax' => '987654321-1',
                        'faxType' => array(
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                    ),
                ),
                'notes' => array(
                    array('value' => 'Note 1'),
                    array('value' => 'Note 2'),
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => array(
                    'id' => 0,
                ),
                'categories' => array(
                    array(
                        'id' => $this->category->getId(),
                    ),
                    array(
                        'id' => $this->category2->getId(),
                    ),
                ),
            )
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

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);
        $this->assertEquals(0, $response->disabled);

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

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);
        $this->assertEquals(0, $response->disabled);

        $this->assertEquals(2, count($response->categories));
    }

    public function testPostWithoutAdditionalData()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            array(
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'disabled' => 0,
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
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

    public function testPostWithoutDisabledFlag()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            array(
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'salutation' => 'Sehr geehrte Frau Mustermann',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals('There is no disabled flag for the contact', $response->message);
    }

    public function testPostWithoutFormOfAddress()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            array(
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'salutation' => 'Sehr geehrte Frau Mustermann',
                'disabled' => 0,
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals('There is no form of address for the contact', $response->message);
    }

    public function testPostWithEmptyAdditionalData()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            array(
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'emails' => array(),
                'phones' => array(),
                'notes' => array(),
                'addresses' => array(),
                'disabled' => 0,
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);
        $this->assertEquals(0, $response->disabled);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotNull($response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);
        $this->assertEquals(0, $response->disabled);
    }

    public function testGetListSearch()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&search=Nothing&searchFields=fullName');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(0, $response->total);
        $this->assertEquals(0, count($response->_embedded->contacts));

        $client->request('GET', '/api/contacts?flat=true&search=Max&searchFields=fullName');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals(2, count($response->_embedded->contacts));
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);
    }

    public function testPut()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'emails' => array(
                    array(
                        'id' => $this->email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'email' => 'john.doe@muster.de',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'phones' => array(
                    array(
                        'id' => $this->phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'faxes' => array(
                    array(
                        'id' => $this->fax->getId(),
                        'fax' => '321654987-1',
                        'faxType' => array(
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'fax' => '789456123-1',
                        'faxType' => array(
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'fax' => '147258369-1',
                        'faxType' => array(
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                    ),
                ),
                'notes' => array(
                    array(
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ),
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0,
                ),
                'categories' => array(
                    array(
                        'id' => $this->category->getId(),
                    ),
                    array(
                        'id' => $this->category2->getId(),
                    ),
                ),
            )
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
        $this->assertEquals(0, $response->disabled);

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

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
        $this->assertEquals(0, $response->disabled);

        $this->assertEquals(2, count($response->categories));
    }

    public function testPutDeleteAndAddWithoutId()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'emails' => array(
                    array(
                        'email' => 'john.doe@muster.de',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'phones' => array(
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'faxes' => array(
                    array(
                        'fax' => '147258369-1',
                        'faxType' => array(
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                    ),
                ),
                'notes' => array(
                    array(
                        'value' => 'Note 1_1',
                    ),
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
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
        $this->assertEquals(0, $response->disabled);

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
        $this->assertEquals(0, $response->disabled);
    }

    public function testPutNoEmail()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'emails' => array(),
                'phones' => array(
                    array(
                        'id' => $this->phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                    ),
                ),
                'notes' => array(
                    array(
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ),
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
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
        $this->assertEquals(0, $response->disabled);
    }

    public function testPutNewCountryOnlyId()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'emails' => array(),
                'phones' => array(
                    array(
                        'id' => $this->phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => $this->country2->getId(),
                            'name' => '',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'notes' => array(
                    array(
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ),
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MSc', $response->title->title);
        $this->assertEquals(0, count($response->emails));

        $this->assertNotNull($response->addresses[0]->country->id);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
        $this->assertEquals(0, $response->disabled);
    }

    public function testPutNewAccount()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'account' => array(
                    'id' => $this->account1->getId(),
                ),
                'emails' => array(),
                'phones' => array(
                    array(
                        'id' => $this->phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => $this->country2->getId(),
                            'name' => '',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'notes' => array(
                    array(
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ),
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
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
        $this->assertEquals(0, $response->disabled);
    }

    public function testPutNotExisting()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/10113',
            array(
                'firstName' => 'John',
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetList()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);

        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);
        $this->assertEquals('MSc', $response->_embedded->contacts[0]->title);

        $this->assertEquals(1, $response->_embedded->contacts[0]->formOfAddress);
        $this->assertEquals('Sehr geehrter Herr Dr Mustermann', $response->_embedded->contacts[0]->salutation);
        $this->assertEquals(0, $response->_embedded->contacts[0]->disabled);
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

    public function testDelete()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', '/api/contacts/' . $this->contact->getId());

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts/' . $this->contact->getId());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteNotExisting()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', '/api/contacts/4711');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

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
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'account' => array(
                    'id' => $this->account1->getId(),
                ),
                'emails' => array(
                    array(
                        'id' => $this->email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'email' => 'john.doe@muster.de',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'phones' => array(
                    array(
                        'id' => $this->phone->getId(),
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'note' => 'note',
                    ),
                ),
                'notes' => array(
                    array(
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ),
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
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
        $this->assertEquals(0, $response->disabled);

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'account' => array(
                    'id' => null,
                ),
                'emails' => array(
                    array(
                        'id' => $this->email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'id' => $response->emails[1]->id,
                        'email' => 'john.doe@muster.de',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'phones' => array(
                    array(
                        'id' => $response->phones[0]->id,
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'id' => $response->phones[1]->id,
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'id' => $response->phones[2]->id,
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'note' => 'note1',
                    ),
                ),
                'notes' => array(
                    array(
                        'id' => $this->note->getId(),
                        'value' => 'Note 1_1',
                    ),
                ),
            )
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
        $this->assertEquals('Sehr geehrter John', $response->salutation);
        $this->assertEquals(0, $response->disabled);

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
        $this->assertEquals('Sehr geehrter John', $response->salutation);
        $this->assertEquals(0, $response->disabled);
    }

    public function testPrimaryAddressHandlingPost()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/contacts',
            array(
                'firstName' => 'Erika',
                'lastName' => 'Mustermann',
                'title' => 'MSc',
                'position' => $this->contactPosition->getId(),
                'account' => array(
                    'id' => $this->account1->getId(),
                ),
                'emails' => array(
                    array(
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ),
                    array(
                        'street' => 'Musterstraße 2',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ),
                ),
                'notes' => array(
                    array('value' => 'Note 1'),
                    array('value' => 'Note 2'),
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($this->account1->getId(), $response->account->id);

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(true, $response->addresses[1]->primaryAddress);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent(), true);

        $addresses = $response['addresses'];

        $filterKeys = array(
            'primaryAddress',
            'street',
        );

        $filteredAddresses = array_map(
            function ($address) use ($filterKeys) {
                return array_intersect_key($address, array_flip($filterKeys));
            },
            $addresses
        );

        $this->assertContains(
            array(
                'primaryAddress' => false,
                'street' => 'Musterstraße',
            ),
            $filteredAddresses
        );

        $this->assertContains(
            array(
                'primaryAddress' => true,
                'street' => 'Musterstraße 2',
            ),
            $filteredAddresses
        );
    }

    public function testPrimaryAddressHandlingPut()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/' . $this->contact->getId(),
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => $this->contactTitle->getId(),
                'position' => $this->contactPosition->getId(),
                'emails' => array(
                    array(
                        'id' => $this->email->getId(),
                        'email' => 'john.doe@muster.at',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'id' => $this->address->getId(),
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ),
                    array(
                        'street' => 'Street 1',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ),
                    array(
                        'street' => 'Street 2',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => $this->addressType->getId(),
                            'name' => 'Private',
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ),
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0,
                ),
            )
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

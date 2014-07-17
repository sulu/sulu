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
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Sulu\Bundle\ContactBundle\Entity\Activity;
use Sulu\Bundle\ContactBundle\Entity\ActivityPriority;
use Sulu\Bundle\ContactBundle\Entity\ActivityStatus;
use Sulu\Bundle\ContactBundle\Entity\ActivityType;

class ContactControllerTest extends DatabaseTestCase
{
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
        $contact->setTitle('Dr');
        $contact->setPosition('CEO');
        $contact->setCreated(new DateTime());
        $contact->setChanged(new DateTime());
        $contact->setFormOfAddress(1);
        $contact->setSalutation("Sehr geehrter Herr Dr Mustermann");
        $contact->setDisabled(0);

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

        self::$em->persist($contact);
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
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    public function testGetById()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts/1');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Max', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('Max Mustermann', $response->fullName);
        $this->assertEquals('Dr', $response->title);
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

        $this->assertEquals(1, $response->formOfAddress);
        $this->assertEquals("Sehr geehrter Herr Dr Mustermann", $response->salutation);
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
                'title' => 'MSc',
                'position' => 'Manager',
                'account' => array(
                    'id' => null
                ),
                'emails' => array(
                    array(
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'phones' => array(
                    array(
                        'phone' => '123456789',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '987654321',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => array(
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711'
                    )
                ),
                'notes' => array(
                    array('value' => 'Note 1'),
                    array('value' => 'Note 2')
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title);
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

        $this->assertEquals(2, $response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title);
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
                'title' => 'MSc',
                'position' => 'Manager',
                'account' => array(
                    'id' => 2
                ),
                'emails' => array(
                    array(
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'phones' => array(
                    array(
                        'phone' => '123456789',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '987654321',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'faxes' => array(
                    array(
                        'fax' => '123456789-1',
                        'faxType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'fax' => '987654321-1',
                        'faxType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => array(
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711'
                    )
                ),
                'notes' => array(
                    array('value' => 'Note 1'),
                    array('value' => 'Note 2')
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->account->id);

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title);
        $this->assertEquals('Manager', $response->position);
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

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);
        $this->assertEquals(0, $response->disabled);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title);
        $this->assertEquals('Manager', $response->position);
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

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrte Frau Dr Mustermann', $response->salutation);
        $this->assertEquals(0, $response->disabled);
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
                'title' => 'MSc',
                'position' => 'Manager',
                'disabled' => 0,
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title);
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
                'title' => 'MSc',
                'position' => 'Manager',
                'salutation' => 'Sehr geehrte Frau Mustermann',
                'formOfAddress' => array(
                    'id' => 0
                )
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
                'title' => 'MSc',
                'position' => 'Manager',
                'salutation' => 'Sehr geehrte Frau Mustermann',
                'disabled' => 0
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
                'title' => 'MSc',
                'position' => 'Manager',
                'emails' => array(),
                'phones' => array(),
                'notes' => array(),
                'addresses' => array(),
                'disabled' => 0,
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals("Sehr geehrte Frau Dr Mustermann", $response->salutation);
        $this->assertEquals(0, $response->disabled);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals("Sehr geehrte Frau Dr Mustermann", $response->salutation);
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

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, count($response->_embedded->contacts));
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);
    }

    public function testPut()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/1',
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => 'MBA',
                'position' => 'Manager',
                'emails' => array(
                    array(
                        'id' => 1,
                        'email' => 'john.doe@muster.at',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'email' => 'john.doe@muster.de',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'phones' => array(
                    array(
                        'id' => 1,
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'faxes' => array(
                    array(
                        'id' => 1,
                        'fax' => '321654987-1',
                        'faxType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'fax' => '789456123-1',
                        'faxType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'fax' => '147258369-1',
                        'faxType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'id' => 1,
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    )
                ),
                'notes' => array(
                    array(
                        'id' => 1,
                        'value' => 'Note 1_1'
                    )
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MBA', $response->title);
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

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MBA', $response->title);
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
    }

    public function testPutDeleteAndAddWithoutId()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/1',
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => 'MBA',
                'position' => 'Manager',
                'emails' => array(
                    array(
                        'email' => 'john.doe@muster.de',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'phones' => array(
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'faxes' => array(
                    array(
                        'fax' => '147258369-1',
                        'faxType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    )
                ),
                'notes' => array(
                    array(
                        'value' => 'Note 1_1'
                    )
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MBA', $response->title);
        $this->assertEquals('john.doe@muster.de', $response->emails[0]->email);
        $this->assertEquals('789456123', $response->phones[0]->phone);
        $this->assertEquals('147258369-1', $response->faxes[0]->fax);
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

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MBA', $response->title);
        $this->assertEquals('john.doe@muster.de', $response->emails[0]->email);
        $this->assertEquals('789456123', $response->phones[0]->phone);
        $this->assertEquals('147258369-1', $response->faxes[0]->fax);
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
    }

    public function testPutNoEmail()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/1',
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => 'MBA',
                'position' => 'Manager',
                'emails' => array(),
                'phones' => array(
                    array(
                        'id' => 1,
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'id' => 1,
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    )
                ),
                'notes' => array(
                    array(
                        'id' => 1,
                        'value' => 'Note 1_1'
                    )
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MBA', $response->title);
        $this->assertEquals(0, count($response->emails));

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

    public function testPutNewCountryOnlyId()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/1',
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => 'MBA',
                'position' => 'Manager',
                'emails' => array(),
                'phones' => array(
                    array(
                        'id' => 1,
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'id' => 1,
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => 2,
                            'name' => '',
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'notes' => array(
                    array(
                        'id' => 1,
                        'value' => 'Note 1_1'
                    )
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MBA', $response->title);
        $this->assertEquals(0, count($response->emails));

        $this->assertEquals(2, $response->addresses[0]->country->id);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
        $this->assertEquals(0, $response->disabled);
    }

    public function testPutNewAccount()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/1',
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => 'MBA',
                'position' => 'Manager',
                'account' => array(
                    'id' => 2
                ),
                'emails' => array(),
                'phones' => array(
                    array(
                        'id' => 1,
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'id' => 1,
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => 2,
                            'name' => '',
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'notes' => array(
                    array(
                        'id' => 1,
                        'value' => 'Note 1_1'
                    )
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MBA', $response->title);
        $this->assertEquals(0, count($response->emails));

        $this->assertEquals(2, $response->account->id);

        $this->assertEquals(2, $response->addresses[0]->country->id);

        $this->assertEquals(0, $response->formOfAddress);
        $this->assertEquals('Sehr geehrter John', $response->salutation);
        $this->assertEquals(0, $response->disabled);
    }

    public function testPutNotExisting()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/10',
            array(
                'firstName' => 'John'
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetList()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);

        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);
        $this->assertEquals('Dr', $response->_embedded->contacts[0]->title);

        $this->assertEquals(1, $response->_embedded->contacts[0]->formOfAddress);
        $this->assertEquals('Sehr geehrter Herr Dr Mustermann', $response->_embedded->contacts[0]->salutation);
        $this->assertEquals(0, $response->_embedded->contacts[0]->disabled);
    }

    public function testGetListFields()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&fields=id,fullName');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, $response->_embedded->contacts[0]->id);
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts?flat=true&fields=id,fullName');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, $response->_embedded->contacts[0]->id);
        $this->assertEquals('Max Mustermann', $response->_embedded->contacts[0]->fullName);
    }

    public function testDelete()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', '/api/contacts/1');

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client = $this->createTestClient();
        $client->request('GET', '/api/contacts/1');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteNotExisting()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', '/api/contacts/4711');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
    }

    public function testPutRemovedAccount()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/1',
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => 'MBA',
                'position' => 'Manager',
                'account' => array(
                    'id' => 2
                ),
                'emails' => array(
                    array(
                        'id' => 1,
                        'email' => 'john.doe@muster.at',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'email' => 'john.doe@muster.de',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'phones' => array(
                    array(
                        'id' => 1,
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'id' => 1,
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'notes' => array(
                    array(
                        'id' => 1,
                        'value' => 'Note 1_1'
                    )
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MBA', $response->title);
        $this->assertEquals('2', $response->account->id);
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

        $client->request(
            'PUT',
            '/api/contacts/1',
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => 'MBA',
                'position' => 'Manager',
                'account' => array(
                    'id' => null
                ),
                'emails' => array(
                    array(
                        'id' => 1,
                        'email' => 'john.doe@muster.at',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'id' => 2,
                        'email' => 'john.doe@muster.de',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'phones' => array(
                    array(
                        'id' => 1,
                        'phone' => '321654987',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'id' => 2,
                        'phone' => '789456123',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'id' => 3,
                        'phone' => '147258369',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'id' => 1,
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'notes' => array(
                    array(
                        'id' => 1,
                        'value' => 'Note 1_1'
                    )
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MBA', $response->title);
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

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('John', $response->firstName);
        $this->assertEquals('Doe', $response->lastName);
        $this->assertEquals('MBA', $response->title);
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
                'position' => 'Manager',
                'account' => array(
                    'id' => 2
                ),
                'emails' => array(
                    array(
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => array(
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711'
                    ),
                    array(
                        'street' => 'Musterstraße 2',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'country' => array(
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711'
                    )
                ),
                'notes' => array(
                    array('value' => 'Note 1'),
                    array('value' => 'Note 2')
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrte Frau Dr Mustermann',
                'formOfAddress' => array(
                    'id' => 0
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->account->id);

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(true, $response->addresses[1]->primaryAddress);

        $client->request('GET', '/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(true, $response->addresses[1]->primaryAddress);
    }

    public function testPrimaryAddressHandlingPut()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/contacts/1',
            array(
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => 'MBA',
                'position' => 'Manager',
                'emails' => array(
                    array(
                        'id' => 1,
                        'email' => 'john.doe@muster.at',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'id' => 1,
                        'street' => 'Street',
                        'number' => '2',
                        'zip' => '9999',
                        'city' => 'Springfield',
                        'state' => 'Colorado',
                        'country' => array(
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
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
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
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
                            'id' => 1,
                            'name' => 'Musterland',
                            'code' => 'ML'
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        ),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    )
                ),
                'disabled' => 0,
                'salutation' => 'Sehr geehrter John',
                'formOfAddress' => array(
                    'id' => 0
                )
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

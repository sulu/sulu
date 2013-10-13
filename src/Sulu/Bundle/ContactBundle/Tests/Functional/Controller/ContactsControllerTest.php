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
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;

class ContactsControllerTest extends DatabaseTestCase
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
		$contact->addAddresse($address);

		$note = new Note();
		$note->setValue('Note');
		$contact->addNote($note);

		self::$em->persist($contact);
		self::$em->persist($account);
		self::$em->persist($account1);
		self::$em->persist($phoneType);
		self::$em->persist($phone);
		self::$em->persist($emailType);
		self::$em->persist($email);
		self::$em->persist($country1);
		self::$em->persist($country2);
		self::$em->persist($addressType);
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
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Account'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Activity'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityStatus'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Address'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AddressType'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactLocale'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Country'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Email'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\EmailType'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Note'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Phone'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\PhoneType'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Url'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\UrlType'),
		);

		self::$tool->dropSchema(self::$entities);
		self::$tool->createSchema(self::$entities);
	}

	public function testGetById()
	{
		$client = static::createClient();
		$client->request('GET', '/api/contact/contacts/1');

		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals('Max', $response->firstName);
		$this->assertEquals('Mustermann', $response->lastName);
		$this->assertEquals('Dr', $response->title);
		$this->assertEquals('CEO', $response->position);
		$this->assertEquals('123456789', $response->phones[0]->phone);
		$this->assertEquals('Private', $response->phones[0]->phoneType->name);
		$this->assertEquals('max.mustermann@muster.at', $response->emails[0]->email);
		$this->assertEquals('Private', $response->emails[0]->emailType->name);
		$this->assertEquals('Musterstraße', $response->addresses[0]->street);
		$this->assertEquals('1', $response->addresses[0]->number);
		$this->assertEquals('0000', $response->addresses[0]->zip);
		$this->assertEquals('Musterstadt', $response->addresses[0]->city);
		$this->assertEquals('Musterland', $response->addresses[0]->state);
		$this->assertEquals('Note', $response->notes[0]->value);
	}

	public function testPostAccountIDNull()
	{
		$client = static::createClient();

		$client->request(
			'POST',
			'/api/contact/contacts',
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
						)
					)
				),
				'notes' => array(
					array('value' => 'Note 1'),
					array('value' => 'Note 2')
				)
			)
		);

		$response = json_decode($client->getResponse()->getContent());
		$this->assertEquals(200, $client->getResponse()->getStatusCode());

		$this->assertEquals('Erika', $response->firstName);
		$this->assertEquals('Mustermann', $response->lastName);
		$this->assertEquals('MSc', $response->title);
		$this->assertEquals('Manager', $response->position);
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

		$client->request('GET', '/api/contact/contacts/' . $response->id);
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
		$this->assertEquals('Musterstraße', $response->addresses[0]->street);
		$this->assertEquals('1', $response->addresses[0]->number);
		$this->assertEquals('0000', $response->addresses[0]->zip);
		$this->assertEquals('Musterstadt', $response->addresses[0]->city);
		$this->assertEquals('Musterstate', $response->addresses[0]->state);
		$this->assertEquals('Note 1', $response->notes[0]->value);
		$this->assertEquals('Note 2', $response->notes[1]->value);
	}

	public function testPost()
	{
		$client = static::createClient();

		$client->request(
			'POST',
			'/api/contact/contacts',
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
						)
					)
				),
				'notes' => array(
					array('value' => 'Note 1'),
					array('value' => 'Note 2')
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
		$this->assertEquals('Musterstraße', $response->addresses[0]->street);
		$this->assertEquals('1', $response->addresses[0]->number);
		$this->assertEquals('0000', $response->addresses[0]->zip);
		$this->assertEquals('Musterstadt', $response->addresses[0]->city);
		$this->assertEquals('Musterstate', $response->addresses[0]->state);
		$this->assertEquals('Note 1', $response->notes[0]->value);
		$this->assertEquals('Note 2', $response->notes[1]->value);

		$client->request('GET', '/api/contact/contacts/' . $response->id);
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
		$this->assertEquals('Musterstraße', $response->addresses[0]->street);
		$this->assertEquals('1', $response->addresses[0]->number);
		$this->assertEquals('0000', $response->addresses[0]->zip);
		$this->assertEquals('Musterstadt', $response->addresses[0]->city);
		$this->assertEquals('Musterstate', $response->addresses[0]->state);
		$this->assertEquals('Note 1', $response->notes[0]->value);
		$this->assertEquals('Note 2', $response->notes[1]->value);
	}


	public function testPostWithoutAdditionalData()
	{
		$client = static::createClient();

		$client->request(
			'POST',
			'/api/contact/contacts',
			array(
				'firstName' => 'Erika',
				'lastName' => 'Mustermann',
				'title' => 'MSc',
				'position' => 'Manager',
			)
		);

		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals('Erika', $response->firstName);
		$this->assertEquals('Mustermann', $response->lastName);
		$this->assertEquals('MSc', $response->title);
		$this->assertEquals('Manager', $response->position);

		$client->request('GET', '/api/contact/contacts/' . $response->id);
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals(2, $response->id);
		$this->assertEquals('Erika', $response->firstName);
		$this->assertEquals('Mustermann', $response->lastName);
		$this->assertEquals('MSc', $response->title);
		$this->assertEquals('Manager', $response->position);
	}

	public function testPostWithEmptyAdditionalData()
	{
		$client = static::createClient();

		$client->request(
			'POST',
			'/api/contact/contacts',
			array(
				'firstName' => 'Erika',
				'lastName' => 'Mustermann',
				'title' => 'MSc',
				'position' => 'Manager',
				'emails' => array(),
				'phones' => array(),
				'notes' => array(),
				'addresses' => array()
			)
		);

		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals('Erika', $response->firstName);
		$this->assertEquals('Mustermann', $response->lastName);
		$this->assertEquals('MSc', $response->title);
		$this->assertEquals('Manager', $response->position);

		$client->request('GET', '/api/contact/contacts/' . $response->id);
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals(2, $response->id);
		$this->assertEquals('Erika', $response->firstName);
		$this->assertEquals('Mustermann', $response->lastName);
		$this->assertEquals('MSc', $response->title);
		$this->assertEquals('Manager', $response->position);
	}

	public function testPut()
	{
		$client = static::createClient();

		$client->request(
			'PUT',
			'/api/contact/contacts/1',
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
		$this->assertEquals('Manager', $response->position);
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

		$client->request('GET', '/api/contact/contacts/' . $response->id);
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals('John', $response->firstName);
		$this->assertEquals('Doe', $response->lastName);
		$this->assertEquals('MBA', $response->title);
		$this->assertEquals('Manager', $response->position);
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
	}

	public function testPutNoEmail()
	{
		$client = static::createClient();

		$client->request(
			'PUT',
			'/api/contact/contacts/1',
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
		$this->assertEquals('Manager', $response->position);
		$this->assertEquals(0, count($response->emails));
	}

	public function testPutNewCountryOnlyId()
	{
		$client = static::createClient();

		$client->request(
			'PUT',
			'/api/contact/contacts/1',
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
				)
			)
		);

		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals('John', $response->firstName);
		$this->assertEquals('Doe', $response->lastName);
		$this->assertEquals('MBA', $response->title);
		$this->assertEquals('Manager', $response->position);
		$this->assertEquals(0, count($response->emails));

		$this->assertEquals(2, $response->addresses[0]->country->id);
	}

	public function testPutNewAccount()
	{
		$client = static::createClient();

		$client->request(
			'PUT',
			'/api/contact/contacts/1',
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
				)
			)
		);

		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals('John', $response->firstName);
		$this->assertEquals('Doe', $response->lastName);
		$this->assertEquals('MBA', $response->title);
		$this->assertEquals('Manager', $response->position);
		$this->assertEquals(0, count($response->emails));

		$this->assertEquals(2, $response->account->id);

		$this->assertEquals(2, $response->addresses[0]->country->id);
	}

	public function testPutNotExisting()
	{
		$client = static::createClient();

		$client->request(
			'PUT',
			'/api/contact/contacts/10',
			array(
				'firstName' => 'John'
			)
		);

		$this->assertEquals(404, $client->getResponse()->getStatusCode());
	}

	public function testGetList()
	{
		$client = static::createClient();
		$client->request('GET', '/api/contact/contacts/list');
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals(1, $response->total);

		$this->assertEquals('Max', $response->items[0]->firstName);
		$this->assertEquals('Mustermann', $response->items[0]->lastName);
		$this->assertEquals('Dr', $response->items[0]->title);
		$this->assertEquals('CEO', $response->items[0]->position);
	}

	public function testGetListFields()
	{
		$client = static::createClient();
		$client->request('GET', '/api/contact/contacts/list?fields=id,firstName,lastName');
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals(1, $response->total);
		$this->assertEquals(1, $response->items[0]->id);
		$this->assertEquals('Max', $response->items[0]->firstName);
		$this->assertEquals('Mustermann', $response->items[0]->lastName);

		$client = static::createClient();
		$client->request('GET', '/api/contact/contacts/list?fields=id,firstName');
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals(1, $response->total);
		$this->assertEquals(1, $response->items[0]->id);
		$this->assertEquals('Max', $response->items[0]->firstName);
		$this->assertFalse(isset($response->items[0]->lastName));
	}

	public function testDelete()
	{
		$client = static::createClient();
		$client->request('DELETE', '/api/contact/contacts/1');

		$this->assertEquals(204, $client->getResponse()->getStatusCode());

		$client = static::createClient();
		$client->request('GET', '/api/contact/contacts/1');

		$this->assertEquals(404, $client->getResponse()->getStatusCode());
	}

	public function testDeleteNotExisting()
	{
		$client = static::createClient();
		$client->request('DELETE', '/api/contact/contacts/4711');

		$this->assertEquals(404, $client->getResponse()->getStatusCode());

		$client->request('GET', '/api/contact/contacts/list');
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals(1, $response->total);
	}
}

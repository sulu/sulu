<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Controller;


use DateTime;
use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;

class AccountsControllerTest extends DatabaseTestCase
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
		self::$account->setCreated(new DateTime());
		self::$account->setChanged(new DateTime());

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
		self::$account->addAddresse($address);

		$note = new Note();
		$note->setValue('Note');
		self::$account->addNote($note);

		self::$em->persist(self::$account);
		self::$em->persist($urlType);
		self::$em->persist($url);
		self::$em->persist($emailType);
		self::$em->persist($email);
		self::$em->persist($phoneType);
		self::$em->persist($phone);
		self::$em->persist($country);
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
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Account'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Activity'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityStatus'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Address'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AddressType'),
			self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
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
		$client = self::createClient();

		$client->request(
			'GET',
			'/contact/api/accounts/1'
		);

		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals(200, $client->getResponse()->getStatusCode());

		$this->assertEquals('Company', $response->name);
		$this->assertEquals('http://www.company.example', $response->urls[0]->url);
		$this->assertEquals('Private', $response->urls[0]->urlType->name);
		$this->assertEquals('office@company.example', $response->emails[0]->email);
		$this->assertEquals('Private', $response->emails[0]->emailType->name);
		$this->assertEquals('123456789', $response->phones[0]->phone);
		$this->assertEquals('Private', $response->phones[0]->phoneType->name);
		$this->assertEquals('Note', $response->notes[0]->value);
		$this->assertEquals('Musterstraße', $response->addresses[0]->street);
		$this->assertEquals('1', $response->addresses[0]->number);
		$this->assertEquals('0000', $response->addresses[0]->zip);
		$this->assertEquals('Musterstadt', $response->addresses[0]->city);
		$this->assertEquals('Musterland', $response->addresses[0]->state);
		$this->assertEquals('Musterland', $response->addresses[0]->country->name);
		$this->assertEquals('ML', $response->addresses[0]->country->code);
		$this->assertEquals('Private', $response->addresses[0]->addressType->name);
	}

	public function testGetByIdNotExisting()
	{
		$client = self::createClient();
		$client->request(
			'GET',
			'/contact/api/accounts/10'
		);

		$this->assertEquals(404, $client->getResponse()->getStatusCode());

		$response = json_decode($client->getResponse()->getContent());
		$this->assertEquals(0, $response->code);
		$this->assertTrue(isset($response->message));
	}

	public function testPost()
	{
		$client = static::createClient();
		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
				'idParent' => self::$account->getId(),
				'urls' => array(
					array(
						'url' => 'http://example.company.com',
						'urlType' => array(
							'id' => '1',
							'name' => 'Private'
						)
					)
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

		$this->assertEquals('ExampleCompany', $response->name);
		$this->assertEquals(2, $response->lft);
		$this->assertEquals(3, $response->rgt);
		$this->assertEquals(1, $response->depth);
		$this->assertEquals(self::$account->getId(), $response->parent->id);
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

		$client->request('GET', '/contact/api/accounts/' . $response->id);
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals('ExampleCompany', $response->name);
		$this->assertEquals(2, $response->lft);
		$this->assertEquals(3, $response->rgt);
		$this->assertEquals(1, $response->depth);
		$this->assertEquals(self::$account->getId(), $response->parent->id);
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

	public function testPostWithIds()
	{
		$client = static::createClient();
		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
				'urls' => array(
					array(
						'id' => 15,
						'url' => 'http://example.company.com',
						'urlType' => array(
							'id' => '1',
							'name' => 'Private'
						)
					)
				)
			)
		);

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
		$response = json_decode($client->getResponse()->getContent());
		$this->assertContains('15', $response->message);

		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
				'emails' => array(
					array(
						'id' => 16,
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
							'name' => 'Work'
						)
					)
				),
			)
		);

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
		$response = json_decode($client->getResponse()->getContent());
		$this->assertContains('16', $response->message);

		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
				'phones' => array(
					array(
						'id' => 17,
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
			)
		);

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
		$response = json_decode($client->getResponse()->getContent());
		$this->assertContains('17', $response->message);

		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
				'addresses' => array(
					array(
						'id' => 18,
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
			)
		);

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
		$response = json_decode($client->getResponse()->getContent());
		$this->assertContains('18', $response->message);

		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
				'notes' => array(
					array(
						'id' => 19,
						'value' => 'Note'
					)
				),
			)
		);

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
		$response = json_decode($client->getResponse()->getContent());
		$this->assertContains('19', $response->message);
	}

	public function testPostWithNotExistingUrlType()
	{
		$client = static::createClient();
		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
				'urls' => array(
					array(
						'url' => 'http://example.company.com',
						'urlType' => array(
							'id' => '2',
							'name' => 'Work'
						)
					)
				)
			)
		);

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
		$response = json_decode($client->getResponse()->getContent());
		$this->assertTrue(isset($response->message));
	}

	public function testPostWithNotExistingEmailType()
	{
		$client = static::createClient();
		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
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
							'id' => 2,
							'name' => 'Work'
						)
					)
				),
			)
		);

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
		$response = json_decode($client->getResponse()->getContent());
		$this->assertTrue(isset($response->message));
	}

	public function testPostWithNotExistingPhoneType()
	{
		$client = static::createClient();
		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
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
							'id' => 2,
							'name' => 'Work'
						)
					)
				),
			)
		);

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
		$response = json_decode($client->getResponse()->getContent());
		$this->assertTrue(isset($response->message));
	}

	public function testPostWithNotExistingAddressType()
	{
		$client = static::createClient();
		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
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
							'id' => 2,
							'name' => 'Work'
						)
					)
				),
			)
		);

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
		$response = json_decode($client->getResponse()->getContent());
		$this->assertTrue(isset($response->message));
	}

	public function testPostWithNotExistingCountry()
	{
		$client = static::createClient();
		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
				'addresses' => array(
					array(
						'street' => 'Musterstraße',
						'number' => '1',
						'zip' => '0000',
						'city' => 'Musterstadt',
						'state' => 'Musterstate',
						'country' => array(
							'id' => 2,
							'name' => 'Österreich',
							'code' => 'AT'
						),
						'addressType' => array(
							'id' => 1,
							'name' => 'Private'
						)
					)
				),
			)
		);

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
		$response = json_decode($client->getResponse()->getContent());
		$this->assertTrue(isset($response->message));
	}

	public function testGetList()
	{
		$client = static::createClient();
		$client->request('GET', '/contact/api/accounts/list');
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals(1, $response->total);

		$this->assertEquals('Company', $response->items[0]->name);
	}

	public function testGetListSearch()
	{
		$client = static::createClient();
		$client->request('GET', '/contact/api/accounts/list?search=Nothing&searchFields=name,emails_emailType_name');
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals(0, $response->total);
		$this->assertEquals(0, count($response->items));

		$client->request('GET', '/contact/api/accounts/list?search=Comp&searchFields=name,emails_emailType_name');
		$response = json_decode($client->getResponse()->getContent());

		$this->assertEquals(1, $response->total);
		$this->assertEquals(1, count($response->items));
		$this->assertEquals('Company', $response->items[0]->name);
	}

	public function testPut()
	{
		$client = static::createClient();
		$client->request(
			'POST',
			'/contact/api/accounts',
			array(
				'name' => 'ExampleCompany',
				'idParent' => self::$account->getId(),
				'urls' => array(
					array(
						'url' => 'http://example.company.com',
						'urlType' => array(
							'id' => '1',
							'name' => 'Private'
						)
					)
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

	}
}

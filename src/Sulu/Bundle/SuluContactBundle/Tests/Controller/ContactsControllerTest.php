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
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Country;
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

        $emailType = new EmailType();
        $emailType->setName('Private');

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setTitle('Dr');
        $contact->setPosition('CEO');
        $contact->setLocaleSystem('en');
        $contact->setUsername('max');
        $contact->setPassword('password');
        $contact->setCreated(new DateTime());
        $contact->setChanged(new DateTime());

        $account = new Account();
        $account->setLft(0);
        $account->setRgt(1);
        $account->setDepth(0);
        $account->setName('Musterfirma');
        $account->setCreated(new DateTime());
        $account->setChanged(new DateTime());
        $account->setCreator($contact);
        $account->setChanger($contact);

        $phoneType = new PhoneType();
        $phoneType->setName('Private');

        $phone = new Phone();
        $phone->setPhone('123456789');
        $phone->setPhoneType($phoneType);
        $contact->addPhone($phone);

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
        $contact->addAddresse($address);

        $note = new Note();
        $note->setValue('Note');
        $contact->addNote($note);

        self::$em->persist($contact);
        self::$em->persist($account);
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

        self::$tool->createSchema(self::$entities);
    }

    public function testGet()
    {

    }

    public function testPost()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/contacts/api/contacts',
            array(
                'fname' => 'Erika',
                'lname' => 'Mustermann',
                'title' => 'MSc',
                'position' => 'Manager',
                'localeSystem' => 'de',
                'emails' => array(
                    array(
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array('email' => 'erika.mustermann@muster.de')
                ),
                'phones' => array(
                    array(
                        'phone' => '123456789',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array('phone' => '987654321')
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

        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title);
        $this->assertEquals('Manager', $response->position);
        $this->assertEquals('localeSystem', $response->localeSystem);
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

        $client->request('GET', '/contacts/api/contacts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->id);
        $this->assertEquals('Erika', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('MSc', $response->title);
        $this->assertEquals('Manager', $response->position);
        $this->assertEquals('localeSystem', $response->localeSystem);
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
}
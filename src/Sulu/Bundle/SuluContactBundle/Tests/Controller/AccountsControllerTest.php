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

    public function setUp()
    {
        $this->setUpSchema();

        $account = new Account();
        $account->setLft(0);
        $account->setRgt(0);
        $account->setDepth(0);
        $account->setName('Company');
        $account->setCreated(new DateTime());
        $account->setChanged(new DateTime());

        $urlType = new UrlType();
        $urlType->setName('Private');

        $url = new Url();
        $url->setUrl('http://www.company.example');
        $url->setUrlType($urlType);
        $account->addUrl($url);

        $emailType = new EmailType();
        $emailType->setName('Private');

        $email = new Email();
        $email->setEmail('office@company.example');
        $email->setEmailType($emailType);
        $account->addEmail($email);

        $phoneType = new PhoneType();
        $phoneType->setName('Private');

        $phone = new Phone();
        $phone->setPhone('123456789');
        $phone->setPhoneType($phoneType);
        $account->addPhone($phone);

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
        $account->addAddresse($address);

        $note = new Note();
        $note->setValue('Note');
        $account->addNote($note);

        self::$em->persist($account);
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
}

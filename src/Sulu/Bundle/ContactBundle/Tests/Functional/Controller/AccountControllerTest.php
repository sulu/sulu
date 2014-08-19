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
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Sulu\Bundle\ContactBundle\Entity\Activity;
use Sulu\Bundle\ContactBundle\Entity\ActivityPriority;
use Sulu\Bundle\ContactBundle\Entity\ActivityStatus;
use Sulu\Bundle\ContactBundle\Entity\ActivityType;

class AccountControllerTest extends DatabaseTestCase
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
        self::$account->setType(Account::TYPE_BASIC);
        self::$account->setDisabled(0);
        self::$account->setCreated(new DateTime());
        self::$account->setChanged(new DateTime());
        self::$account->setPlaceOfJurisdiction('Feldkirch');

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

        $faxType = new FaxType();
        $faxType->setName('Private');

        $fax = new Fax();
        $fax->setFax('123654789');
        $fax->setFaxType($faxType);
        self::$account->addFax($fax);

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
        $address->setAddition('');
        $address->setAddressType($addressType);
        $address->setBillingAddress(true);
        $address->setPrimaryAddress(true);
        $address->setDeliveryAddress(false);
        $address->setPostboxCity("Dornbirn");
        $address->setPostboxPostcode("6850");
        $address->setPostboxNumber("4711");

        $accountAddress = new AccountAddress();
        $accountAddress->setAddress($address);
        $accountAddress->setAccount(self::$account);
        $accountAddress->setMain(true);
        self::$account->addAccountAddresse($accountAddress);
        $address->addAccountAddresse($accountAddress);

        $contact = new Contact();
        $contact->setFirstName("Vorname");
        $contact->setLastName("Nachname");
        $contact->setMiddleName("Mittelname");
        $contact->setCreated(new \DateTime());
        $contact->setChanged(new \DateTime());
        $contact->setDisabled(0);
        $contact->setFormOfAddress(0);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount(self::$account);
        $accountContact->setMain(true);
        self::$account->addAccountContact($accountContact);

        $note = new Note();
        $note->setValue('Note');
        self::$account->addNote($note);

        self::$em->persist(self::$account);
        self::$em->persist($urlType);
        self::$em->persist($url);
        self::$em->persist($emailType);
        self::$em->persist($accountContact);
        self::$em->persist($email);
        self::$em->persist($phoneType);
        self::$em->persist($phone);
        self::$em->persist($country);
        self::$em->persist($addressType);
        self::$em->persist($address);
        self::$em->persist($accountAddress);
        self::$em->persist($note);
        self::$em->persist($faxType);
        self::$em->persist($fax);
        self::$em->persist($contact);

        $accountCategory = new AccountCategory();
        $accountCategory->setCategory("Test");
        self::$em->persist($accountCategory);

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
            self::$em->getClassMetadata('Sulu\Bundle\TestBundle\Entity\TestUser'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Account'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Activity'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityPriority'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Address'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AddressType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactTitle'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Position'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\BankAccount'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactLocale'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Country'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Email'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\EmailType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Note'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Fax'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\FaxType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Phone'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\PhoneType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Url'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\UrlType'),
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountCategory'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountContact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfPayment'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\Collection'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\CollectionType'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\CollectionMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\Media'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\MediaType'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\File'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersion'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Category'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Category'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryMeta')
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
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

    public function testGetById()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/accounts/1'
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
        $this->assertEquals('123654789', $response->faxes[0]->fax);
        $this->assertEquals('Private', $response->faxes[0]->faxType->name);
        $this->assertEquals('Note', $response->notes[0]->value);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterland', $response->addresses[0]->state);
        $this->assertEquals('Musterland', $response->addresses[0]->country->name);
        $this->assertEquals('ML', $response->addresses[0]->country->code);
        $this->assertEquals('Private', $response->addresses[0]->addressType->name);
        $this->assertEquals('Feldkirch', $response->placeOfJurisdiction);

        $this->assertEquals(true,$response->addresses[0]->billingAddress);
        $this->assertEquals(true,$response->addresses[0]->primaryAddress);
        $this->assertEquals(false,$response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn',$response->addresses[0]->postboxCity);
        $this->assertEquals('6850',$response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711',$response->addresses[0]->postboxNumber);
    }

    public function testGetByIdNotExisting()
    {
        $client = $this->createTestClient();
        $client->request(
            'GET',
            '/api/accounts/10'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertTrue(isset($response->message));
    }


    public function testGetEmptyAccountContacts()
    {
        $account = new Account();
        $account->setName('test');
        $account->setChanged(new DateTime());
        $account->setCreated(new DateTime());

        self::$em->persist($account);
        self::$em->flush();

        $client = $this->createTestClient();
        $client->request('GET', '/api/accounts/' . $account->getId() . '/contacts?flat=true');

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(0, $response->total);
        $this->assertCount(0, $response->_embedded->contacts);
    }

    public function testPost()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'parent' => array('id' => self::$account->getId()),
                'type' => Account::TYPE_BASIC,
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
                        'postboxNumber' => '4711',
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
        $this->assertEquals(1, $response->depth);
        $this->assertEquals(self::$account->getId(), $response->parent->id);
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

        $this->assertEquals(true,$response->addresses[0]->billingAddress);
        $this->assertEquals(true,$response->addresses[0]->primaryAddress);
        $this->assertEquals(false,$response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn',$response->addresses[0]->postboxCity);
        $this->assertEquals('6850',$response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711',$response->addresses[0]->postboxNumber);

        $client->request('GET', '/api/accounts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals(self::$account->getId(), $response->parent->id);
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

        $this->assertEquals(true,$response->addresses[0]->billingAddress);
        $this->assertEquals(true,$response->addresses[0]->primaryAddress);
        $this->assertEquals(false,$response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn',$response->addresses[0]->postboxCity);
        $this->assertEquals('6850',$response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711',$response->addresses[0]->postboxNumber);
    }

    public function testPostWithCategory()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'parent' => array('id' => self::$account->getId()),
                'type' => Account::TYPE_BASIC,
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
                'accountCategory' => array(
                    'id' => '1',
                    'category' => 'test'
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals(self::$account->getId(), $response->parent->id);
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
        $this->assertEquals(1, $response->accountCategory->id);

        $this->assertEquals(true,$response->addresses[0]->billingAddress);
        $this->assertEquals(true,$response->addresses[0]->primaryAddress);
        $this->assertEquals(false,$response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn',$response->addresses[0]->postboxCity);
        $this->assertEquals('6850',$response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711',$response->addresses[0]->postboxNumber);

        $client->request('GET', '/api/accounts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals(self::$account->getId(), $response->parent->id);
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
        $this->assertEquals(1, $response->accountCategory->id);

        $this->assertEquals(true,$response->addresses[0]->billingAddress);
        $this->assertEquals(true,$response->addresses[0]->primaryAddress);
        $this->assertEquals(false,$response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn',$response->addresses[0]->postboxCity);
        $this->assertEquals('6850',$response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711',$response->addresses[0]->postboxNumber);
    }

    public function testPostWithIds()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            '/api/accounts',
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
            '/api/accounts',
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
            '/api/accounts',
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
            '/api/accounts',
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
            '/api/accounts',
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
        $client = $this->createTestClient();
        $client->request(
            'POST',
            '/api/accounts',
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

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingEmailType()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            '/api/accounts',
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

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingPhoneType()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            '/api/accounts',
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

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingAddressType()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            '/api/accounts',
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

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingFaxType()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'faxes' => array(
                    array(
                        'fax' => '12345',
                        'faxType' => array(
                            'id' => 2,
                            'name' => 'Work'
                        )
                    )
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingCountry()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            '/api/accounts',
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

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testGetList()
    {
        $client = $this->createTestClient();
        $client->request('GET', '/api/accounts?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);

        $this->assertEquals('Company', $response->_embedded->accounts[0]->name);
    }

    public function testGetListSearch()
    {
        $client = $this->createTestClient();
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
        $client = $this->createTestClient();
        $client->request(
            'PUT',
            '/api/accounts/1',
            array(
                'name' => 'ExampleCompany',
                'urls' => array(
                    array(
                        'id' => 1,
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => '1',
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'url' => 'http://test.company.com',
                        'urlType' => array(
                            'id' => '1',
                            'name' => 'Private'
                        )
                    )
                ),
                'emails' => array(
                    array(
                        'email' => 'office@company.com',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'email' => 'erika.mustermann@company.com',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'phones' => array(
                    array(
                        'phone' => '4567890',
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
                    )
                ),
                'faxes' => array(
                    array(
                        'fax' => '4567890-1',
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
                    )
                ),
                'addresses' => array(
                    array(
                        'street' => 'Bahnhofstraße',
                        'number' => '2',
                        'zip' => '0022',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                        'street' => 'Rathausgasse',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                    array('value' => 'Note1'),
                    array('value' => 'Note2')
                )
            )
        );

        //$this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('ExampleCompany', $response->name);

        $this->assertEquals(2, sizeof($response->urls));
        $this->assertEquals('http://example.company.com', $response->urls[0]->url);
        $this->assertEquals('Private', $response->urls[0]->urlType->name);
        $this->assertEquals('http://test.company.com', $response->urls[1]->url);
        $this->assertEquals('Private', $response->urls[1]->urlType->name);

        $this->assertEquals(2, sizeof($response->emails));
        $this->assertEquals('office@company.com', $response->emails[0]->email);
        $this->assertEquals('Private', $response->emails[0]->emailType->name);
        $this->assertEquals('erika.mustermann@company.com', $response->emails[1]->email);
        $this->assertEquals('Private', $response->emails[1]->emailType->name);

        $this->assertEquals(2, sizeof($response->phones));
        $this->assertEquals('4567890', $response->phones[0]->phone);
        $this->assertEquals('Private', $response->phones[0]->phoneType->name);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('Private', $response->phones[1]->phoneType->name);

        $this->assertEquals(2, sizeof($response->faxes));
        $this->assertEquals('4567890-1', $response->faxes[0]->fax);
        $this->assertEquals('Private', $response->faxes[0]->faxType->name);
        $this->assertEquals('789456123-1', $response->faxes[1]->fax);
        $this->assertEquals('Private', $response->faxes[1]->faxType->name);

        $this->assertEquals(2, sizeof($response->notes));
        $this->assertEquals('Note1', $response->notes[0]->value);
        $this->assertEquals('Note2', $response->notes[1]->value);

        if($response->addresses[0]->street === 'Bahnhofstraße') {
            $this->assertEquals(2, sizeof($response->addresses));
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
            $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

            $this->assertEquals('Rathausgasse', $response->addresses[1]->street);
            $this->assertEquals('3', $response->addresses[1]->number);
            $this->assertEquals('2222', $response->addresses[1]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[1]->city);
            $this->assertEquals('state1', $response->addresses[1]->state);
            $this->assertEquals('Musterland', $response->addresses[1]->country->name);
            $this->assertEquals('ML', $response->addresses[1]->country->code);
            $this->assertEquals('Private', $response->addresses[1]->addressType->name);
        } else {
            $this->assertEquals(2, sizeof($response->addresses));
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

            $this->assertEquals('Rathausgasse', $response->addresses[0]->street);
            $this->assertEquals('3', $response->addresses[0]->number);
            $this->assertEquals('2222', $response->addresses[0]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[0]->city);
            $this->assertEquals('state1', $response->addresses[0]->state);
            $this->assertEquals('Musterland', $response->addresses[0]->country->name);
            $this->assertEquals('ML', $response->addresses[0]->country->code);
            $this->assertEquals('Private', $response->addresses[0]->addressType->name);
        }

        $client->request(
            'GET',
            '/api/accounts/1'
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('ExampleCompany', $response->name);

        $this->assertEquals(2, sizeof($response->urls));
        $this->assertEquals('http://example.company.com', $response->urls[0]->url);
        $this->assertEquals('Private', $response->urls[0]->urlType->name);
        $this->assertEquals('http://test.company.com', $response->urls[1]->url);
        $this->assertEquals('Private', $response->urls[1]->urlType->name);

        $this->assertEquals(2, sizeof($response->emails));
        $this->assertEquals('office@company.com', $response->emails[0]->email);
        $this->assertEquals('Private', $response->emails[0]->emailType->name);
        $this->assertEquals('erika.mustermann@company.com', $response->emails[1]->email);
        $this->assertEquals('Private', $response->emails[1]->emailType->name);

        $this->assertEquals(2, sizeof($response->phones));
        $this->assertEquals('4567890', $response->phones[0]->phone);
        $this->assertEquals('Private', $response->phones[0]->phoneType->name);
        $this->assertEquals('789456123', $response->phones[1]->phone);
        $this->assertEquals('Private', $response->phones[1]->phoneType->name);

        $this->assertEquals(2, sizeof($response->faxes));
        $this->assertEquals('4567890-1', $response->faxes[0]->fax);
        $this->assertEquals('Private', $response->faxes[0]->faxType->name);
        $this->assertEquals('789456123-1', $response->faxes[1]->fax);
        $this->assertEquals('Private', $response->faxes[1]->faxType->name);

        $this->assertEquals(2, sizeof($response->notes));
        $this->assertEquals('Note1', $response->notes[0]->value);
        $this->assertEquals('Note2', $response->notes[1]->value);

        if($response->addresses[0]->street === 'Bahnhofstraße') {
            $this->assertEquals(2, sizeof($response->addresses));
            $this->assertEquals('Bahnhofstraße', $response->addresses[0]->street);
            $this->assertEquals('2', $response->addresses[0]->number);
            $this->assertEquals('0022', $response->addresses[0]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[0]->city);
            $this->assertEquals('state1', $response->addresses[0]->state);
            $this->assertEquals('Musterland', $response->addresses[0]->country->name);
            $this->assertEquals('ML', $response->addresses[0]->country->code);
            $this->assertEquals('Private', $response->addresses[0]->addressType->name);
    
            $this->assertEquals(true,$response->addresses[0]->billingAddress);
            $this->assertEquals(true,$response->addresses[0]->primaryAddress);
            $this->assertEquals(false,$response->addresses[0]->deliveryAddress);
            $this->assertEquals('Dornbirn',$response->addresses[0]->postboxCity);
            $this->assertEquals('6850',$response->addresses[0]->postboxPostcode);
            $this->assertEquals('4711',$response->addresses[0]->postboxNumber);
    
            $this->assertEquals('Rathausgasse', $response->addresses[1]->street);
            $this->assertEquals('3', $response->addresses[1]->number);
            $this->assertEquals('2222', $response->addresses[1]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[1]->city);
            $this->assertEquals('state1', $response->addresses[1]->state);
            $this->assertEquals('Musterland', $response->addresses[1]->country->name);
            $this->assertEquals('ML', $response->addresses[1]->country->code);
            $this->assertEquals('Private', $response->addresses[1]->addressType->name);
        } else {
            $this->assertEquals(2, sizeof($response->addresses));
            $this->assertEquals('Bahnhofstraße', $response->addresses[1]->street);
            $this->assertEquals('2', $response->addresses[1]->number);
            $this->assertEquals('0022', $response->addresses[1]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[1]->city);
            $this->assertEquals('state1', $response->addresses[1]->state);
            $this->assertEquals('Musterland', $response->addresses[1]->country->name);
            $this->assertEquals('ML', $response->addresses[1]->country->code);
            $this->assertEquals('Private', $response->addresses[1]->addressType->name);
    
            $this->assertEquals(true,$response->addresses[1]->billingAddress);
            $this->assertEquals(true,$response->addresses[1]->primaryAddress);
            $this->assertEquals(false,$response->addresses[1]->deliveryAddress);
            $this->assertEquals('Dornbirn',$response->addresses[1]->postboxCity);
            $this->assertEquals('6850',$response->addresses[1]->postboxPostcode);
            $this->assertEquals('4711',$response->addresses[1]->postboxNumber);
    
            $this->assertEquals('Rathausgasse', $response->addresses[0]->street);
            $this->assertEquals('3', $response->addresses[0]->number);
            $this->assertEquals('2222', $response->addresses[0]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[0]->city);
            $this->assertEquals('state1', $response->addresses[0]->state);
            $this->assertEquals('Musterland', $response->addresses[0]->country->name);
            $this->assertEquals('ML', $response->addresses[0]->country->code);
            $this->assertEquals('Private', $response->addresses[0]->addressType->name);
        }
    }

    public function testPutNoDetails()
    {
        $client = $this->createTestClient();
        $client->request(
            'PUT',
            '/api/accounts/1',
            array(
                'name' => 'ExampleCompany',
                'urls' => array(),
                'emails' => array(),
                'phones' => array(),
                'addresses' => array(),
                'faxes' => array(),
                'notes' => array()
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/accounts/1'
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('ExampleCompany', $response->name);

        $this->assertEquals(0, sizeof($response->urls));
        $this->assertEquals(0, sizeof($response->emails));
        $this->assertEquals(0, sizeof($response->phones));
        $this->assertEquals(0, sizeof($response->faxes));
        $this->assertEquals(0, sizeof($response->notes));
        $this->assertEquals(0, sizeof($response->addresses));
    }

    public function testPutNotExisting()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/accounts/4711',
            array(
                'name' => 'TestCompany'
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteById()
    {

        $client = $this->createTestClient();

        $client->request('DELETE', '/api/accounts/1');
        $this->assertEquals('204', $client->getResponse()->getStatusCode());
    }

    public function testAccountAddresses()
    {

        $client = $this->createTestClient();

        $client->request('GET', '/api/accounts/1/addresses');
        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $address = $response->_embedded->addresses[0];
        $this->assertEquals('Musterstraße', $address->street);
        $this->assertEquals('1', $address->number);

        $client->request('GET', '/api/accounts/1/addresses?flat=true');
        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);

        $address = $response->_embedded->addresses[0];
        $this->assertEquals('Musterstraße 1 , 0000, Musterstadt, Musterland, Musterland, 4711', $address->address);
        $this->assertEquals('1', $address->id);
    }

    public function testDeleteByIdAndNotDeleteContacts()
    {

        $client = $this->createTestClient();

        $client->request(
            'DELETE',
            '/api/accounts/1',
            array(
                'removeContacts' => 'false'
            )
        );
        // check if contacts are still there
        $this->assertEquals('204', $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }

    public function testDeleteByIdAndDeleteContacts()
    {
        $contact = new Contact();
        $contact->setFirstName("Vorname");
        $contact->setLastName("Nachname");
        $contact->setMiddleName("Mittelname");
        $contact->setCreated(new \DateTime());
        $contact->setChanged(new \DateTime());
        $contact->setDisabled(0);
        $contact->setFormOfAddress(0);
        self::$em->persist($contact);
        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount(self::$account);
        $accountContact->setMain(true);
        self::$account->addAccountContact($accountContact);
        self::$em->persist($accountContact);

        self::$em->flush();

        $client = $this->createTestClient();

        $client->request(
            'DELETE',
            '/api/accounts/1',
            array(
                'removeContacts' => 'true'
            )
        );
        // check if contacts are still there
        $this->assertEquals('204', $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->total);


    }

    public function testDeleteByIdNotExisting()
    {
        $client = $this->createTestClient();

        $client->request('DELETE', '/api/accounts/4711');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());


        $client->request('GET', '/api/accounts?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }


    /*
     * test if deleteinfo returns correct data
     */
    public function testMultipleDeleteInfo()
    {
        // modify test data
        $acc = new Account();
        $acc->setName("Test Account");
        $acc->setChanged(new \DateTime());
        $acc->setCreated(new \DateTime());
        self::$em->persist($acc);

        // add 5 contacts to account
        for ($i = 0; $i < 5; $i++) {
            $contact = new Contact();
            $contact->setFirstName("Vorname " . $i);
            $contact->setLastName("Nachname " . $i);
            $contact->setMiddleName("Mittelname " . $i);
            $contact->setCreated(new \DateTime());
            $contact->setChanged(new \DateTime());
            $contact->setDisabled(0);
            $contact->setFormOfAddress(0);
            self::$em->persist($contact);

            $accountContact = new AccountContact();
            $accountContact->setContact($contact);
            $accountContact->setAccount(self::$account);
            $accountContact->setMain(true);
            self::$em->persist($accountContact);
            self::$account->addAccountContact($accountContact);
        }

        // add subaccount to self::$account
        $subacc = new Account();
        $subacc->setName("Subaccount");
        $subacc->setChanged(new \DateTime());
        $subacc->setCreated(new \DateTime());
        $subacc->setParent(self::$account);

        self::$em->persist($subacc);

        self::$em->flush();

        // get number of contacts from both accounts
        $numContacts = self::$account->getAccountContacts()->count() + $acc->getAccountContacts()->count();

        $client = $this->createTestClient();
        $client->request(
            'GET',
            '/api/accounts/multipledeleteinfo',
            array(
                "ids" => array(1, 2)
            )
        );

        // asserts

        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        // return full number of contacts related to account
        $this->assertEquals($numContacts, $response->numContacts);

        // allowed if no subaccount exists
        $this->assertEquals(1, $response->numChildren);

    }


    /*
     * test if deleteinfo returns correct data
     */
    public function testGetDeleteInfoById()
    {
        // modify test data

        for ($i = 0; $i < 5; $i++) {
            $contact = new Contact();
            $contact->setFirstName("Vorname " . $i);
            $contact->setLastName("Nachname " . $i);
            $contact->setMiddleName("Mittelname " . $i);
            $contact->setCreated(new \DateTime());
            $contact->setChanged(new \DateTime());
            $contact->setDisabled(0);
            $contact->setFormOfAddress(0);
            self::$em->persist($contact);

            $accountContact = new AccountContact();
            $accountContact->setContact($contact);
            $accountContact->setAccount(self::$account);
            $accountContact->setMain(true);
            self::$em->persist($accountContact);
            self::$account->addAccountContact($accountContact);
        }

        self::$em->flush();

        $numContacts = self::$account->getAccountContacts()->count();

        $client = $this->createTestClient();

        $client->request('GET', '/api/accounts/1/deleteinfo');
        $this->assertEquals('200', $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        // number of returned contacts has to be less or equal 3
        $this->assertEquals(3, sizeof($response->contacts));

        // return full number of contacts related to account
        $this->assertEquals($numContacts, $response->numContacts);

        // allowed if no subaccount exists
        $this->assertEquals(0, $response->numChildren);

    }

    /*
     * test if delete info returns right isAllowed, when there is a superaccount
     */
    public function testGetDeletInfoByIdWithSuperAccount()
    {

        // changing test data: adding child accounts
        for ($i = 0; $i < 5; $i++) {
            $childAccount = new Account();
            $childAccount->setName("child num#" . $i);
            $childAccount->setChanged(new \DateTime());
            $childAccount->setCreated(new \DateTime());
            $childAccount->setParent(self::$account);

            self::$em->persist($childAccount);
        }
        self::$em->flush();

        $client = $this->createTestClient();

        $client->request('GET', '/api/accounts/1/deleteinfo');
        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        // deletion not allowed if children existent
        $this->assertGreaterThan(0, $response->numChildren);

        // number of returned contacts has to be less or equal 3
        $this->assertLessThanOrEqual(3, sizeof($response->children));

    }

    public function testGetDeleteInfoByIdNotExisting()
    {

        $client = $this->createTestClient();
        $client->request('GET', '/api/accounts/4711/deleteinfo');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/accounts/1/deleteinfo');
        $this->assertEquals('200', $client->getResponse()->getStatusCode());
    }

    public function testPutRemovedParentAccount()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'parent' => array('id' => self::$account->getId()),
                'type' => Account::TYPE_BASIC,
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
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(self::$account->getId(), $response->parent->id);
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

        $this->assertEquals(true,$response->addresses[0]->billingAddress);
        $this->assertEquals(true,$response->addresses[0]->primaryAddress);
        $this->assertEquals(false,$response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn',$response->addresses[0]->postboxCity);
        $this->assertEquals('6850',$response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711',$response->addresses[0]->postboxNumber);

        $client->request(
            'PUT',
            '/api/accounts/2',
            array(
                'id' => 2,
                'name' => 'ExampleCompany 222',
                'parent' => array('id' => null),
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
                        'id' => 2,
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'id' => 3,
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'phones' => array(
                    array(
                        'id' => 2,
                        'phone' => '123456789',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'id' => 3,
                        'phone' => '987654321',
                        'phoneType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'faxes' => array(
                    array(
                        'id' => 2,
                        'fax' => '123456789-1',
                        'faxType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'id' => 3,
                        'fax' => '987654321-1',
                        'faxType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'id' => 2,
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
                    array('id' => 2, 'value' => 'Note 1'),
                    array('id' => 3, 'value' => 'Note 2')
                )
            )
        );

        $client->request(
            'GET',
            '/api/accounts/2'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

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

        $this->assertEquals(true,$response->addresses[0]->billingAddress);
        $this->assertEquals(true,$response->addresses[0]->primaryAddress);
        $this->assertEquals(false,$response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn',$response->addresses[0]->postboxCity);
        $this->assertEquals('6850',$response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711',$response->addresses[0]->postboxNumber);
    }

    public function testPrimaryAddressHandlingPost()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'parent' => array('id' => self::$account->getId()),
                'type' => Account::TYPE_BASIC,
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
                        'postboxNumber' => '4711',
                    ),
                    array(
                        'street' => 'Musterstraße',
                        'number' => '2',
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
                        'postboxNumber' => '4711',
                    )
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(false,$response->addresses[0]->primaryAddress);
        $this->assertEquals(true,$response->addresses[1]->primaryAddress);

        $client->request('GET', '/api/accounts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(false,$response->addresses[0]->primaryAddress);
        $this->assertEquals(true,$response->addresses[1]->primaryAddress);

    }

    public function testPrimaryAddressHandlingPut()
    {
        $client = $this->createTestClient();
        $client->request(
            'PUT',
            '/api/accounts/1',
            array(
                'name' => 'ExampleCompany',
                'urls' => array(
                    array(
                        'id' => 1,
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => '1',
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'url' => 'http://test.company.com',
                        'urlType' => array(
                            'id' => '1',
                            'name' => 'Private'
                        )
                    )
                ),
                'emails' => array(
                    array(
                        'email' => 'office@company.com',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    ),
                    array(
                        'email' => 'erika.mustermann@company.com',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Private'
                        )
                    )
                ),
                'addresses' => array(
                    array(
                        'id' => '1',
                        'street' => 'Bahnhofstraße',
                        'number' => '2',
                        'zip' => '0022',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                        'street' => 'Rathausgasse 1',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                        'street' => 'Rathausgasse 2',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        usort($response->addresses, $this->sortAddressesPrimaryLast());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[1]->primaryAddress);
        $this->assertEquals(true, $response->addresses[2]->primaryAddress);

        $client->request(
            'GET',
            '/api/accounts/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
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

    public function testTriggerAction()
    {

        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/accounts/1?action=convertAccountType&type=lead'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(self::$account->getId(), $response->id);
        $this->assertEquals(1, $response->type);

    }

    public function testTriggerActionUnknownTrigger()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/accounts/1?action=xyz&type=lead'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testTriggerActionUnknownEntity()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            '/api/accounts/999?action=convertAccountType&type=lead'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}

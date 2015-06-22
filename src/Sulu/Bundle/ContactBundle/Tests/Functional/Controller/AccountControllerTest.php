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

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
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
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AccountControllerTest extends SuluTestCase
{
    /**
     * @var Account
     */
    private $account;

    /**
     * @var Account
     */
    private $childAccount;

    /**
     * @var Account
     */
    private $parentAccount;

    public function setUp()
    {
        $this->purgeDatabase();
        $this->em = $this->db('ORM')->getOm();
        $this->initOrm();
    }

    private function initOrm()
    {
        $account = new Account();
        $account->setName('Company');
        $account->setDisabled(0);
        $account->setPlaceOfJurisdiction('Feldkirch');

        $parentAccount = new Account();
        $parentAccount->setName('Parent');
        $parentAccount->setDisabled(0);
        $parentAccount->setPlaceOfJurisdiction('Feldkirch');

        $childAccount = new Account();
        $childAccount->setName('Child');
        $childAccount->setDisabled(0);
        $childAccount->setPlaceOfJurisdiction('Feldkirch');
        $childAccount->setParent($parentAccount);

        $this->account = $account;
        $this->childAccount = $childAccount;
        $this->parentAccount = $parentAccount;

        $urlType = new UrlType();
        $urlType->setName('Private');

        $this->urlType = $urlType;

        $url = new Url();
        $url->setUrl('http://www.company.example');

        $this->url = $url;
        $url->setUrlType($urlType);
        $account->addUrl($url);

        $this->emailType = new EmailType();
        $this->emailType->setName('Private');

        $this->email = new Email();
        $this->email->setEmail('office@company.example');
        $this->email->setEmailType($this->emailType);
        $account->addEmail($this->email);

        $phoneType = new PhoneType();
        $phoneType->setName('Private');

        $this->phoneType = $phoneType;

        $phone = new Phone();
        $phone->setPhone('123456789');
        $phone->setPhoneType($phoneType);
        $account->addPhone($phone);

        $faxType = new FaxType();
        $faxType->setName('Private');

        $this->faxType = $faxType;

        $fax = new Fax();
        $fax->setFax('123654789');
        $fax->setFaxType($faxType);
        $account->addFax($fax);

        $country = new Country();
        $country->setName('Musterland');
        $country->setCode('ML');

        $this->country = $country;

        $addressType = new AddressType();
        $addressType->setName('Private');

        $this->addressType = $addressType;

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
        $address->setPostboxCity('Dornbirn');
        $address->setPostboxPostcode('6850');
        $address->setPostboxNumber('4711');
        $address->setNote('note');

        $this->address = $address;

        $accountAddress = new AccountAddress();
        $accountAddress->setAddress($address);
        $accountAddress->setAccount($account);
        $accountAddress->setMain(true);
        $account->addAccountAddresse($accountAddress);
        $address->addAccountAddresse($accountAddress);

        $contact = new Contact();
        $contact->setFirstName('Vorname');
        $contact->setLastName('Nachname');
        $contact->setMiddleName('Mittelname');
        $contact->setDisabled(0);
        $contact->setFormOfAddress(0);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $accountContact->setMain(true);
        $account->addAccountContact($accountContact);

        $note = new Note();
        $note->setValue('Note');
        $account->addNote($note);

        $this->em->persist($account);
        $this->em->persist($childAccount);
        $this->em->persist($parentAccount);
        $this->em->persist($urlType);
        $this->em->persist($url);
        $this->em->persist($this->emailType);
        $this->em->persist($accountContact);
        $this->em->persist($this->email);
        $this->em->persist($phoneType);
        $this->em->persist($phone);
        $this->em->persist($country);
        $this->em->persist($addressType);
        $this->em->persist($address);
        $this->em->persist($accountAddress);
        $this->em->persist($note);
        $this->em->persist($faxType);
        $this->em->persist($fax);
        $this->em->persist($contact);

        $this->em->flush();
    }

    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
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
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterland', $response->addresses[0]->state);
        $this->assertEquals('Musterland', $response->addresses[0]->country->name);
        $this->assertEquals('ML', $response->addresses[0]->country->code);
        $this->assertEquals('Private', $response->addresses[0]->addressType->name);
        $this->assertEquals('Feldkirch', $response->placeOfJurisdiction);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);
    }

    public function testGetByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/accounts/11230'
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

        $this->em->persist($account);
        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/accounts/' . $account->getId() . '/contacts?flat=true');

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(0, $response->total);
        $this->assertCount(0, $response->_embedded->contacts);
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'parent' => array('id' => $this->account->getId()),
                'urls' => array(
                    array(
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ),
                    ),
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
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals($this->account->getId(), $response->parent->id);
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

        $client->request('GET', '/api/accounts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals($this->account->getId(), $response->parent->id);
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
    }

    public function testPostWithCategory()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'parent' => array('id' => $this->account->getId()),
                'urls' => array(
                    array(
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ),
                    ),
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
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals($this->account->getId(), $response->parent->id);
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

        $client->request('GET', '/api/accounts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals($this->account->getId(), $response->parent->id);
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
    }

    public function testPostWithIds()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'urls' => array(
                    array(
                        'id' => 1512312312313,
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
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
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => array(
                            'id' => 1,
                            'name' => 'Work',
                        ),
                    ),
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
                            'id' => $this->country->getId(),
                            'name' => 'Musterland',
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private',
                        ),
                    ),
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
                        'value' => 'Note',
                    ),
                ),
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertContains('19', $response->message);
    }

    public function testPostWithNotExistingUrlType()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'urls' => array(
                    array(
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => '12312',
                            'name' => 'Work',
                        ),
                    ),
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingEmailType()
    {
        $client = $this->createAuthenticatedClient();
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
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => array(
                            'id' => 2,
                            'name' => 'Work',
                        ),
                    ),
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingPhoneType()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
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
                            'id' => '1233',
                            'name' => 'Work',
                        ),
                    ),
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingAddressType()
    {
        $client = $this->createAuthenticatedClient();
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
                            'code' => 'ML',
                        ),
                        'addressType' => array(
                            'id' => 2,
                            'name' => 'Work',
                        ),
                    ),
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingFaxType()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'faxes' => array(
                    array(
                        'fax' => '12345',
                        'faxType' => array(
                            'id' => '123123',
                            'name' => 'Work',
                        ),
                    ),
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testPostWithNotExistingCountry()
    {
        $client = $this->createAuthenticatedClient();
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
                            'id' => 12393,
                            'name' => 'Österreich',
                            'code' => 'AT',
                        ),
                        'addressType' => array(
                            'id' => 1,
                            'name' => 'Private',
                        ),
                    ),
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertTrue(isset($response->message));
    }

    public function testGetList()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/accounts?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);

        $this->assertEquals('Company', $response->_embedded->accounts[0]->name);
    }

    public function testGetListSearch()
    {
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
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/accounts/' . $this->account->getId(),
            array(
                'name' => 'ExampleCompany',
                'urls' => array(
                    array(
                        'id' => $this->url->getId(),
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'url' => 'http://test.company.com',
                        'urlType' => array(
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'emails' => array(
                    array(
                        'email' => 'office@company.com',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'email' => 'erika.mustermann@company.com',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'phones' => array(
                    array(
                        'phone' => '4567890',
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
                ),
                'faxes' => array(
                    array(
                        'fax' => '4567890-1',
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
                ),
                'addresses' => array(
                    array(
                        'street' => 'Bahnhofstraße',
                        'number' => '2',
                        'zip' => '0022',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                    array(
                        'street' => 'Rathausgasse',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                    array('value' => 'Note1'),
                    array('value' => 'Note2'),
                ),
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

        if ($response->addresses[0]->street === 'Bahnhofstraße') {
            $this->assertEquals(2, sizeof($response->addresses));
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
            $this->assertEquals(2, sizeof($response->addresses));
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

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
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

        if ($response->addresses[0]->street === 'Bahnhofstraße') {
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
            $this->assertEquals('note', $response->addresses[0]->note);
            $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

            $this->assertEquals('Rathausgasse', $response->addresses[1]->street);
            $this->assertEquals('3', $response->addresses[1]->number);
            $this->assertEquals('2222', $response->addresses[1]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[1]->city);
            $this->assertEquals('state1', $response->addresses[1]->state);
            $this->assertEquals('Musterland', $response->addresses[1]->country->name);
            $this->assertEquals('ML', $response->addresses[1]->country->code);
            $this->assertEquals('Private', $response->addresses[1]->addressType->name);
            $this->assertEquals('note1', $response->addresses[1]->note);
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
            $this->assertEquals('note', $response->addresses[1]->note);

            $this->assertEquals('Rathausgasse', $response->addresses[0]->street);
            $this->assertEquals('3', $response->addresses[0]->number);
            $this->assertEquals('2222', $response->addresses[0]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[0]->city);
            $this->assertEquals('state1', $response->addresses[0]->state);
            $this->assertEquals('Musterland', $response->addresses[0]->country->name);
            $this->assertEquals('ML', $response->addresses[0]->country->code);
            $this->assertEquals('Private', $response->addresses[0]->addressType->name);
            $this->assertEquals('note1', $response->addresses[0]->note);
        }
    }

    public function testPutNoDetails()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/accounts/' . $this->account->getId(),
            array(
                'name' => 'ExampleCompany',
                'urls' => array(),
                'emails' => array(),
                'phones' => array(),
                'addresses' => array(),
                'faxes' => array(),
                'notes' => array(),
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
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
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/accounts/4711',
            array(
                'name' => 'TestCompany',
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/accounts/' . $this->account->getId());
        $this->assertEquals('204', $client->getResponse()->getStatusCode());
    }

    public function testAccountAddresses()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/addresses');
        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $address = $response->_embedded->addresses[0];
        $this->assertEquals('Musterstraße', $address->street);
        $this->assertEquals('1', $address->number);

        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/addresses?flat=true');
        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);

        $address = $response->_embedded->addresses[0];
        $this->assertEquals('Musterstraße 1 , 0000, Musterstadt, Musterland, Musterland, 4711', $address->address);
        $this->assertNotNull($address->id);
    }

    public function testDeleteByIdAndNotDeleteContacts()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/api/accounts/' . $this->account->getId(),
            array(
                'removeContacts' => 'false',
            )
        );
        $this->assertEquals('204', $client->getResponse()->getStatusCode());

        // check if contacts are still there
        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(2, $response->total);
    }

    public function testDeleteByIdAndDeleteContacts()
    {
        $contact = new Contact();
        $contact->setFirstName('Vorname');
        $contact->setLastName('Nachname');
        $contact->setMiddleName('Mittelname');
        $contact->setDisabled(0);
        $contact->setFormOfAddress(0);
        $this->em->persist($contact);
        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($this->account);
        $accountContact->setMain(true);
        $this->account->addAccountContact($accountContact);
        $this->em->persist($accountContact);

        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/api/accounts/' . $this->account->getId(),
            array(
                'removeContacts' => 'true',
            )
        );
        // check if contacts are still there
        $this->assertEquals('204', $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/contacts?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }

    public function testDeleteByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/accounts/4711');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/accounts?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(3, $response->total);
    }

    /*
     * test if deleteinfo returns correct data
     */
    public function testMultipleDeleteInfo()
    {
        // modify test data
        $acc = new Account();
        $acc->setName('Test Account');
        $this->em->persist($acc);

        // add 5 contacts to account
        for ($i = 0; $i < 5; $i++) {
            $contact = new Contact();
            $contact->setFirstName('Vorname ' . $i);
            $contact->setLastName('Nachname ' . $i);
            $contact->setMiddleName('Mittelname ' . $i);
            $contact->setDisabled(0);
            $contact->setFormOfAddress(0);
            $this->em->persist($contact);

            $accountContact = new AccountContact();
            $accountContact->setContact($contact);
            $accountContact->setAccount($this->account);
            $accountContact->setMain(true);
            $this->em->persist($accountContact);
            $this->account->addAccountContact($accountContact);
        }

        // add subaccount to $this->account
        $subacc = new Account();
        $subacc->setName('Subaccount');
        $subacc->setParent($this->account);

        $this->em->persist($subacc);

        $this->em->flush();

        // get number of contacts from both accounts
        $numContacts = $this->account->getAccountContacts()->count() + $acc->getAccountContacts()->count();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/accounts/multipledeleteinfo',
            array(
                'ids' => array($this->account->getId(), $acc->getId()),
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
            $contact->setFirstName('Vorname ' . $i);
            $contact->setLastName('Nachname ' . $i);
            $contact->setMiddleName('Mittelname ' . $i);
            $contact->setDisabled(0);
            $contact->setFormOfAddress(0);
            $this->em->persist($contact);

            $accountContact = new AccountContact();
            $accountContact->setContact($contact);
            $accountContact->setAccount($this->account);
            $accountContact->setMain(true);
            $this->em->persist($accountContact);
            $this->account->addAccountContact($accountContact);
        }

        $this->em->flush();

        $numContacts = $this->account->getAccountContacts()->count();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/deleteinfo');
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
            $childAccount->setName('child num#' . $i);
            $childAccount->setParent($this->account);

            $this->em->persist($childAccount);
        }
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/deleteinfo');
        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        // deletion not allowed if children existent
        $this->assertGreaterThan(0, $response->numChildren);

        // number of returned contacts has to be less or equal 3
        $this->assertLessThanOrEqual(3, sizeof($response->children));
    }

    public function testGetDeleteInfoByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/accounts/4711/deleteinfo');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/deleteinfo');
        $this->assertEquals('200', $client->getResponse()->getStatusCode());
    }

    public function testPutRemovedParentAccount()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'parent' => array('id' => $this->account->getId()),
                'urls' => array(
                    array(
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ),
                    ),
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
                    ),
                ),
                'notes' => array(
                    array('value' => 'Note 1'),
                    array('value' => 'Note 2'),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals($this->account->getId(), $response->parent->id);
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

        $account2Id = $response->id;

        $client->request(
            'PUT',
            '/api/accounts/' . $account2Id,
            array(
                'id' => $account2Id,
                'name' => 'ExampleCompany 222',
                'parent' => array('id' => null),
                'urls' => array(
                    array(
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'emails' => array(
                    array(
                        'id' => $response->emails[0]->id,
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'id' => $response->emails[1]->id,
                        'email' => 'erika.mustermann@muster.de',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'phones' => array(
                    array(
                        'id' => $response->phones[0]->id,
                        'phone' => '123456789',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'id' => $response->phones[1]->id,
                        'phone' => '987654321',
                        'phoneType' => array(
                            'id' => $this->phoneType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'faxes' => array(
                    array(
                        'id' => $response->faxes[0]->id,
                        'fax' => '123456789-1',
                        'faxType' => array(
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'id' => $response->faxes[1]->id,
                        'fax' => '987654321-1',
                        'faxType' => array(
                            'id' => $this->faxType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'id' => $response->addresses[0]->id,
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
                ),
                'notes' => array(
                    array('id' => $response->notes[0]->id, 'value' => 'Note 1'),
                    array('id' => $response->notes[1]->id, 'value' => 'Note 2'),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/accounts/' . $account2Id
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

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);
    }

    public function testPrimaryAddressHandlingPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/accounts',
            array(
                'name' => 'ExampleCompany',
                'parent' => array('id' => $this->account->getId()),
                'urls' => array(
                    array(
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ),
                    ),
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
                        'street' => 'Musterstraße',
                        'number' => '2',
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
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(true, $response->addresses[1]->primaryAddress);

        $client->request('GET', '/api/accounts/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        if ($response->addresses[0]->number == 1) {
            $this->assertEquals(false, $response->addresses[0]->primaryAddress);
            $this->assertEquals(true, $response->addresses[1]->primaryAddress);
        } else {
            $this->assertEquals(false, $response->addresses[1]->primaryAddress);
            $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        }
    }

    public function testPrimaryAddressHandlingPut()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/accounts/' . $this->account->getId(),
            array(
                'name' => 'ExampleCompany',
                'urls' => array(
                    array(
                        'id' => $this->url->getId(),
                        'url' => 'http://example.company.com',
                        'urlType' => array(
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'url' => 'http://test.company.com',
                        'urlType' => array(
                            'id' => $this->urlType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'emails' => array(
                    array(
                        'email' => 'office@company.com',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                    array(
                        'email' => 'erika.mustermann@company.com',
                        'emailType' => array(
                            'id' => $this->emailType->getId(),
                            'name' => 'Private',
                        ),
                    ),
                ),
                'addresses' => array(
                    array(
                        'id' => $this->address->getId(),
                        'street' => 'Bahnhofstraße',
                        'number' => '2',
                        'zip' => '0022',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                        'street' => 'Rathausgasse 1',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
                        'street' => 'Rathausgasse 2',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
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
            '/api/accounts/' . $this->account->getId()
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

    public function testGetAccountsWithNoParent()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/accounts?flat=true&hasNoParent=true'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(2, $response->total);
    }
}

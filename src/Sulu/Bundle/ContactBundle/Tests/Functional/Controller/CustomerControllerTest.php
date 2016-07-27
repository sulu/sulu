<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CustomerControllerTest extends SuluTestCase
{
    private $contacts = [];
    private $accounts = [];

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->initOrm();
    }

    private function initOrm()
    {
        $this->purgeDatabase();

        $this->contacts[] = $this->createContact('Max', 'Mustermann');
        $this->contacts[] = $this->createContact('Erika', 'Mustermann');

        $this->accounts[] = $this->createAccount('MASSIVE ART WebServices GmbH');
        $this->accounts[] = $this->createAccount('Apple');

        $this->em->flush();
    }

    private function createContact($firstName, $lastName)
    {
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);

        $this->em->persist($contact);

        return $contact;
    }

    private function createAccount($name)
    {
        $account = new Account();
        $account->setName($name);

        $this->em->persist($account);

        return $account;
    }

    public function testCGet()
    {
        $ids = sprintf(
            'c%s,a%s,c%s,a%s',
            $this->contacts[0]->getId(),
            $this->accounts[0]->getId(),
            $this->contacts[1]->getId(),
            $this->accounts[1]->getId()
        );

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/customers?ids=' . $ids);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('c' . $this->contacts[0]->getId(), $response['_embedded']['customers'][0]['id']);
        $this->assertEquals($this->contacts[0]->getFullName(), $response['_embedded']['customers'][0]['name']);

        $this->assertEquals('a' . $this->accounts[0]->getId(), $response['_embedded']['customers'][1]['id']);
        $this->assertEquals($this->accounts[0]->getName(), $response['_embedded']['customers'][1]['name']);

        $this->assertEquals('c' . $this->contacts[1]->getId(), $response['_embedded']['customers'][2]['id']);
        $this->assertEquals($this->contacts[1]->getFullName(), $response['_embedded']['customers'][2]['name']);

        $this->assertEquals('a' . $this->accounts[1]->getId(), $response['_embedded']['customers'][3]['id']);
        $this->assertEquals($this->accounts[1]->getName(), $response['_embedded']['customers'][3]['name']);
    }
}

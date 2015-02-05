<?php

namespace Sulu\Bundle\DeveloperBundle\DataFixtures\ORM;

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Faker;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Address;

class LoadContactData implements FixtureInterface, OrderedFixtureInterface
{
    private $phoneType;
    private $emailType;
    private $faxType;
    private $addressType;
    private $country1;
    private $country2;
    private $titles = array();

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('en_GB');

        $account = new Account();
        $account->setLft(0);
        $account->setRgt(1);
        $account->setDepth(0);
        $account->setName('Musterfirma');
        $account->setCreated(new \DateTime());
        $account->setChanged(new \DateTime());

        $this->phoneType = new PhoneType();
        $this->phoneType->setName('Private');
        $manager->persist($this->phoneType);
        $this->emailType = new EmailType();
        $this->emailType->setName('Private');
        $manager->persist($this->emailType);
        $this->faxType = new FaxType();
        $this->faxType->setName('Private');
        $manager->persist($this->faxType);
        $this->addressType = new AddressType();
        $this->addressType->setName('Private');
        $manager->persist($this->addressType);
        $this->country1 = new Country();
        $this->country1->setName('Austria');
        $this->country1->setCode('AT');
        $manager->persist($this->country1);
        $this->country2 = new Country();
        $this->country2->setName('France');
        $this->country2->setCode('FR');
        $manager->persist($this->country2);
        $this->position = new Position();
        $this->position->setPosition('Manager');
        $manager->persist($this->position);

        foreach (array('Mr', 'Miss', 'Mrs') as $titleString) {
            $title = new ContactTitle();
            $title->setTitle($titleString);
            $this->titles[] = $title;
            $manager->persist($title);
        }

        for ($i = 0; $i < 10; $i++) {
            $contact = $this->createContact($faker, $manager);
        }

        $manager->flush();
    }

    private function createContact($faker, $manager)
    {
        $phone = new Phone();
        $phone->setPhone($faker->phoneNumber);
        $phone->setPhoneType($this->phoneType);
        $manager->persist($phone);
        $email = new Email();
        $email->setEmail($faker->email);
        $email->setEmailType($this->emailType);
        $manager->persist($email);
        $fax = new Fax();
        $fax->setFax($faker->phoneNumber);
        $fax->setFaxType($this->faxType);
        $manager->persist($fax);

        $contact = new Contact();
        $contact->setFirstName($faker->firstName);
        $contact->setLastName($faker->lastName);
        $contact->setPosition('CEO');
        $contact->setCreated(new \DateTime());
        $contact->setChanged(new \DateTime());
        $contact->setSalutation("Hello");
        $contact->setDisabled(0);
        $contact->setTitle($this->titles[rand(0, 2)]);
        $contact->addPhone($phone);
        $contact->addEmail($email);
        $contact->addFax($fax);
        $manager->persist($contact);

        for ($i = 0; $i<= rand(0, 5); $i++) {
            $address = $this->createAddress($faker, $manager);

            $contactAddress = new ContactAddress();
            $contactAddress->setAddress($address);
            $contactAddress->setContact($contact);
            $contactAddress->setMain(true);
            $manager->persist($contactAddress);
            $contact->addContactAddresse($contactAddress);
            $address->addContactAddresse($contactAddress);
        }

        for ($i = 0; $i <= rand(0, 20); $i++) {
            $note = new Note();
            $note->setValue($faker->realText);
            $contact->addNote($note);
            $manager->persist($note);
        }

        return $contact;
    }

    private function createAddress($faker, $manager)
    {
        $address = new Address();
        $address->setStreet($faker->streetName);
        $address->setNumber($number = $faker->buildingNumber);
        $address->setZip($zip = $faker->postcode);
        $address->setCity($city = $faker->city);
        $address->setState($faker->county);
        $address->setCountry($this->country1);
        $address->setAddressType($this->addressType);
        $address->setBillingAddress(true);
        $address->setPrimaryAddress(true);
        $address->setDeliveryAddress(false);
        $address->setPostboxCity($city);
        $address->setPostboxPostcode($zip);
        $address->setPostboxNumber($number);
        $address->setNote($faker->realText);
        $manager->persist($address);

        return $address;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 100;
    }
}

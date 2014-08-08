<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Import;

use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Import\Import;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory;
use Sulu\Bundle\ContactBundle\Entity\TermsOfPayment;
use Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery;
use Sulu\Bundle\ContactBundle\Entity\Activity;
use Sulu\Bundle\ContactBundle\Entity\ActivityPriority;
use Sulu\Bundle\ContactBundle\Entity\ActivityStatus;
use Sulu\Bundle\ContactBundle\Entity\ActivityType;

class ImportTest extends DatabaseTestCase
{
    private static $fixturePath;

    /**
     * @var Import
     */
    protected $import;

    /**
     * mappings for test files
     * @var array
     */
    protected $mappings = array();

    /**
     * @var array
     */
    protected static $entities;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$fixturePath = __DIR__ . '/../../Resources/Resources/DataFixtures/Files/';
    }

    public function setUp()
    {
        $this->setUpSchema();

        $type = new EmailType();
        $type->setId(1);
        $type->setName('Business');
        self::$em->persist($type);

        $type = new AddressType();
        $type->setId(1);
        $type->setName('Business');
        self::$em->persist($type);

        $type = new PhoneType();
        $type->setId(1);
        $type->setName('Business');
        self::$em->persist($type);
        $type = new PhoneType();
        $type->setId(2);
        $type->setName('ISDN');
        self::$em->persist($type);
        $type = new PhoneType();
        $type->setId(3);
        $type->setName('Mobile');
        self::$em->persist($type);

        $type = new FaxType();
        $type->setId(1);
        $type->setName('Business');
        self::$em->persist($type);

        $type = new UrlType();
        $type->setId(1);
        $type->setName('Business');
        self::$em->persist($type);

        $type = new Country();
        $type->setId(1);
        $type->setName('Austria');
        $type->setCode('AT');
        self::$em->persist($type);
        $type = new Country();
        $type->setId(1);
        $type->setName('Germany');
        $type->setCode('DE');
        self::$em->persist($type);
        $type = new Country();
        $type->setId(1);
        $type->setName('United Kingdom');
        $type->setCode('UK');
        self::$em->persist($type);

        self::$em->flush();

        // TODO: use fixtures
        $this->import = new Import(self::$em,
            new AccountManager(self::$em),
            new ContactManager(self::$em),
            array(
                'emailType' => 1,
                'phoneType' => 1,
                'phoneTypeIsdn' => 2,
                'phoneTypeMobile' => 3,
                'addressType' => 1,
                'urlType' => 1,
                'faxType' => 1,
                'country' => 1,
            ),
            array(), // FIXME: this is not beeing used by import currently (fill in when needed)
            array(
                'male' => array(
                    'id' => 0,
                    'name' => 'male',
                    'translation' => 'male'
                ),
                'female' => array(
                    'id' => 1,
                    'name' => 'female',
                    'translation' => 'female'
                ),
            )
        );
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function setUpSchema()
    {
        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Account'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Activity'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityPriority'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactTitle'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Position'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\BankAccount'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Phone'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\PhoneType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Email'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\EmailType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Fax'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\FaxType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Url'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\UrlType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Address'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AddressType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Country'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Note'),
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountCategory'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountContact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfPayment'),
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
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation')
        );

        self::$tool->createSchema(self::$entities);
    }

    public function testImport()
    {
        // accounts file
        $this->import->setAccountFile(self::$fixturePath . 'accounts.csv');
        // contacts file
        $this->import->setContactFile(self::$fixturePath . 'contacts.csv');

        $this->import->execute();

        // check account data
        $this->checkAccountData();

        // test contact import
        $this->checkContactData();

        // http://stackoverflow.com/questions/18268464/doctrine-lazy-loading-in-symfony-test-environment
        self::$em->clear();

    }

    public function testAccountImportWithMappingsFile()
    {
        // accounts file
        $this->import->setAccountFile(self::$fixturePath . 'accounts_mapping_needed.csv');
        // mappings
        $this->import->setMappingsFile(self::$fixturePath . 'mappings.json');

        $this->import->execute();

        // check account data
        $this->checkAccountData();

        // FIXME needed because of strange doctrine behaviour
        // http://stackoverflow.com/questions/18268464/doctrine-lazy-loading-in-symfony-test-environment
        self::$em->clear();
    }

    /**
     * @expectedException \Symfony\Component\Translation\Exception\NotFoundResourceException
     */
    public function testAccountFileNotFound()
    {
        $this->import->setAccountFile('this-file-does-not-exist.csv');
        $this->import->execute();
    }

    /**
     * @expectedException \Symfony\Component\Translation\Exception\NotFoundResourceException
     */
    public function testContactFileNotFound()
    {
        $this->import->setAccountFile(self::$fixturePath . 'accounts_mapping_needed.csv');
        $this->import->setContactFile('this-file-does-not-exist.csv');
        $this->import->execute();
    }

    /**
     * @expectedException \Symfony\Component\Translation\Exception\NotFoundResourceException
     */
    public function testMappingsFileNotFound()
    {
        $this->import->setAccountFile(self::$fixturePath . 'accounts_mapping_needed.csv');
        $this->import->setMappingsFile('this-file-does-not-exist.json');
        $this->import->execute();
    }

    public function testImportWithoutFiles()
    {
        $this->import->execute();
    }

    private function checkAccountData()
    {
        /** @var Account $account */
        $accounts = self::$em->getRepository('SuluContactBundle:Account')->findAll();
        $this->assertEquals(2, sizeof($accounts));

        // accounts
        $account = $accounts[0];

        // first account
        $this->assertEquals(1, $account->getId());
        $this->assertEquals('Test Company 1', $account->getName());
        $this->assertEquals('Office', $account->getCorporation());
        $this->assertEquals(Account::TYPE_SUPPLIER, $account->getType());
        $this->assertEquals('ATU 1234 5678', $account->getUid());
        $this->assertNull($account->getParent());
        $this->assertEquals('0', $account->getDisabled());

        // addresss
        /** @var Address $address */
        $this->assertEquals(1, sizeof($account->getAddresses()));
        $address = $account->getAccountAddresses()->get(0)->getAddress();
        $this->assertEquals('Street', $address->getStreet());
        $this->assertEquals('1', $address->getNumber());
        $this->assertEquals('AT', $address->getCountry()->getCode());
        $this->assertEquals('6850', $address->getZip());
        $this->assertEquals('Dornbirn', $address->getCity());

        // phones
        $this->assertEquals(3, sizeof($account->getPhones()));
        $this->assertEquals('+43 (123) 456-0', $account->getPhones()->get(0)->getPhone());
        $this->assertEquals('Business', $account->getPhones()->get(0)->getPhoneType()->getName());
        $this->assertEquals('+43 (123) 456-78', $account->getPhones()->get(1)->getPhone());
        $this->assertEquals('Business', $account->getPhones()->get(1)->getPhoneType()->getName());
        $this->assertEquals('+43 (123) 456-1', $account->getPhones()->get(2)->getPhone());
        $this->assertEquals('ISDN', $account->getPhones()->get(2)->getPhoneType()->getName());
        // notes
        $this->assertEquals(1, sizeof($account->getNotes()));
        $this->assertEquals('just a simple note', $account->getNotes()->get(0)->getValue());
        // faxes
        $this->assertEquals(1, sizeof($account->getFaxes()));
        $this->assertEquals('+43 (123) 456-78', $account->getFaxes()->get(0)->getFax());
        $this->assertEquals('Business', $account->getFaxes()->get(0)->getFaxType()->getName());
        // emails
        $this->assertEquals(1, sizeof($account->getEmails()));
        $this->assertEquals('test@test.com', $account->getEmails()->get(0)->getEmail());
        $this->assertEquals('Business', $account->getEmails()->get(0)->getEmailType()->getName());
        // urls
        $this->assertEquals(1, sizeof($account->getUrls()));
        $this->assertEquals('www.test.com', $account->getUrls()->get(0)->getUrl());
        $this->assertEquals('Business', $account->getUrls()->get(0)->getUrlType()->getName());

        // accounts
        $account = $accounts[1];

        // second account
        $this->assertEquals(2, $account->getId());
        $this->assertEquals('Child Customer', $account->getName());
        $this->assertEquals(null, $account->getCorporation());
        $this->assertEquals(Account::TYPE_CUSTOMER, $account->getType());
        $this->assertEquals('DEU 5678 1234', $account->getUid());
        $this->assertEquals(1, $account->getParent()->getId());
        $this->assertEquals('0', $account->getDisabled());

        // addresss
        /** @var Address $address */
        $this->assertEquals(1, sizeof($account->getAccountAddresses()));
        $address = $account->getAccountAddresses()->get(0)->getAddress();
        $this->assertEquals('Street', $address->getStreet());
        $this->assertEquals('2', $address->getNumber());
        $this->assertEquals('DE', $address->getCountry()->getCode());
        $this->assertEquals('88131', $address->getZip());
        $this->assertEquals('Lindau', $address->getCity());

        // phones
        $this->assertEquals(1, sizeof($account->getPhones()));
        $this->assertEquals('+43 (123) 789', $account->getPhones()->get(0)->getPhone());
        $this->assertEquals('Business', $account->getPhones()->get(0)->getPhoneType()->getName());
        // notes
        $this->assertEquals(0, sizeof($account->getNotes()));
        // faxes
        $this->assertEquals(1, sizeof($account->getFaxes()));
        $this->assertEquals('+43 (123) 456-98', $account->getFaxes()->get(0)->getFax());
        $this->assertEquals('Business', $account->getFaxes()->get(0)->getFaxType()->getName());
        // emails
        $this->assertEquals(1, sizeof($account->getEmails()));
        $this->assertEquals('test@company.com', $account->getEmails()->get(0)->getEmail());
        $this->assertEquals('Business', $account->getEmails()->get(0)->getEmailType()->getName());
        // urls
        $this->assertEquals(1, sizeof($account->getUrls()));
        $this->assertEquals('www.company.com', $account->getUrls()->get(0)->getUrl());
        $this->assertEquals('Business', $account->getUrls()->get(0)->getUrlType()->getName());
    }

    private function checkContactData()
    {
        /** @var Contact $contact */
        $contacts = self::$em->getRepository('SuluContactBundle:Contact')->findAll();
        $this->assertEquals(2, sizeof($contacts));

        $contact = $contacts[0];

        $this->assertEquals(1, $contact->getId());
        $this->assertEquals('John', $contact->getFirstName());
        $this->assertEquals('Doe', $contact->getLastName());
        $this->assertEquals('Secretary', $contact->getPosition()->getPosition());
        $this->assertEquals(1, $contact->getAccountContacts()[0]->getAccount()->getId());

        // addresss
        /** @var Address $address */
        $this->assertEquals(1, sizeof($contact->getContactAddresses()));
        $address = $contact->getContactAddresses()->get(0)->getAddress();
        $this->assertEquals('Some Street', $address->getStreet());
        $this->assertEquals('3', $address->getNumber());
        $this->assertEquals('AT', $address->getCountry()->getCode());
        $this->assertEquals('6900', $address->getZip());
        $this->assertEquals('Bregenz', $address->getCity());

        // phones
        $this->assertEquals(1, sizeof($contact->getPhones()));
        $this->assertEquals('+43 (123) 456', $contact->getPhones()->get(0)->getPhone());
        $this->assertEquals('Business', $contact->getPhones()->get(0)->getPhoneType()->getName());
        // notes
        $this->assertEquals(1, sizeof($contact->getNotes()));
        $this->assertEquals('Simple Note', $contact->getNotes()->get(0)->getValue());
        // faxes
        $this->assertEquals(1, sizeof($contact->getFaxes()));
        $this->assertEquals('+43 (123) 456-78', $contact->getFaxes()->get(0)->getFax());
        $this->assertEquals('Business', $contact->getFaxes()->get(0)->getFaxType()->getName());
        // emails
        $this->assertEquals(1, sizeof($contact->getEmails()));
        $this->assertEquals('john@doe.com', $contact->getEmails()->get(0)->getEmail());
        $this->assertEquals('Business', $contact->getEmails()->get(0)->getEmailType()->getName());

        $contact = $contacts[1];

        $this->assertEquals(2, $contact->getId());
        $this->assertEquals('Nicole', $contact->getFirstName());
        $this->assertEquals('Exemplary', $contact->getLastName());
        $this->assertEquals('CEO', $contact->getPosition()->getPosition());
        $this->assertEquals('Master', $contact->getTitle()->getTitle());
        $this->assertEquals(2, $contact->getAccountContacts()[0]->getAccount()->getId());

        // addresss
        /** @var Address $address */
        $this->assertEquals(1, sizeof($contact->getContactAddresses()));
        $address = $contact->getContactAddresses()->get(0)->getAddress();
        $this->assertEquals('New Street', $address->getStreet());
        $this->assertEquals('5', $address->getNumber());
        $this->assertEquals('DE', $address->getCountry()->getCode());
        $this->assertEquals('89087', $address->getZip());
        $this->assertEquals('Berlin', $address->getCity());

        // phones
        $this->assertEquals(1, sizeof($contact->getPhones()));
        $this->assertEquals('+43 (123) 654', $contact->getPhones()->get(0)->getPhone());
        $this->assertEquals('Business', $contact->getPhones()->get(0)->getPhoneType()->getName());
        // notes
        $this->assertEquals(0, sizeof($contact->getNotes()));
        // faxes
        $this->assertEquals(1, sizeof($contact->getFaxes()));
        $this->assertEquals('+43 (123) 654-87', $contact->getFaxes()->get(0)->getFax());
        $this->assertEquals('Business', $contact->getFaxes()->get(0)->getFaxType()->getName());
        // emails
        $this->assertEquals(1, sizeof($contact->getEmails()));
        $this->assertEquals('nicole@exemplary.com', $contact->getEmails()->get(0)->getEmail());
        $this->assertEquals('Business', $contact->getEmails()->get(0)->getEmailType()->getName());
    }
}

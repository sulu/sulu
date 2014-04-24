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

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Import\Import;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;

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

        $this->import = new Import(self::$em, array(
            'emailType' => 1,
            'phoneType' => 1,
            'phoneTypeIsdn' => 2,
            'phoneTypeMobile' => 3,
            'addressType' => 1,
            'urlType' => 1,
            'faxType' => 1,
            'country' => 1,
        ));
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
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
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
        );

        self::$tool->createSchema(self::$entities);
    }

    public function testImport()
    {
        // accounts file
        $this->import->setAccountFile(self::$fixturePath . 'accounts.csv');
        // TODO: contacts
        // contacts file
//        $this->import->setAccountFile(self::$fixturePath . 'contacts.csv');
        // mappings
//        $this->import->setMappingsFile(self::$fixturePath . 'mappings.json');

        $this->import->execute();

        /** @var Account $account */
        $account = self::$em->getRepository('SuluContactBundle:Account')->find(1);
        $this->assertEquals(1, $account->getId());

        // FIXME needed because of strange doctrine behaviour
        // http://stackoverflow.com/questions/18268464/doctrine-lazy-loading-in-symfony-test-environment
        self::$em->clear();

        // TODO: test contact import
    }

    public function testWithMappingsFile()
    {
        // accounts file
        $this->import->setAccountFile(self::$fixturePath . 'accounts_mapping_needed.csv');
        // TODO: contacts
        // contacts file
//        $this->import->setAccountFile(self::$fixturePath . 'contacts.csv');
        // mappings
        $this->import->setMappingsFile(self::$fixturePath . 'mappings.json');

        $this->import->execute();

        /** @var Account $account */
        $account = self::$em->getRepository('SuluContactBundle:Account')->find(1);
        $this->assertEquals(1, $account->getId());

        // FIXME needed because of strange doctrine behaviour
        // http://stackoverflow.com/questions/18268464/doctrine-lazy-loading-in-symfony-test-environment
        self::$em->clear();

        // TODO: test contact import

    }

    /**
     * @expectedException \Symfony\Component\Translation\Exception\NotFoundResourceException
     */
    public function testXliffNoFile()
    {
        $this->import->setFile('this-file-does-not-exist.xliff');
        $this->import->execute();
    }

    /**
     * @expectedException \Symfony\Component\Translation\Exception\InvalidResourceException
     */
    public function testXliffFailFile()
    {
        $this->import->setFile(self::$fixturePath . '/import_fail.xlf');
        $this->import->execute();
    }

}

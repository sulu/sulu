<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Code;
use Sulu\Bundle\TranslateBundle\Entity\Location;
use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\TranslateBundle\Entity\Translation;

class CodesControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;
    /**
     * @var SchemaTool
     */
    protected static $tool;
    /**
     * @var Package
     */
    private $package;
    /**
     * @var Location
     */
    private $location;
    /**
     * @var Catalogue
     */
    private $catalogue;
    /**
     * @var Code
     */
    private $code1;
    /**
     * @var Code
     */
    private $code2;
    /**
     * @var Code
     */
    private $code3;

    public function setUp()
    {
        $this->setUpSchema();

        // TODO create entities
        $this->package = new Package();
        $this->package->setName('Package1');
        self::$em->persist($this->package);

        $this->location = new Location();
        $this->location->setName('Location1')
            ->setPackage($this->package);
        self::$em->persist($this->location);

        $this->catalogue = new Catalogue();
        $this->catalogue->setLocale('EN')
            ->setPackage($this->package);
        self::$em->persist($this->catalogue);

        $this->code1 = new Code();
        $this->code1->setCode('test.code.1')
            ->setFrontend(0)
            ->setBackend(1)
            ->setLength(11)
            ->setPackage($this->package)
            ->setLocation($this->location);
        self::$em->persist($this->code1);

        self::$em->flush();

        $t1 = new Translation();
        $t1->setValue('Test Code 1')
            ->setCatalogue($this->catalogue)
            ->setCode($this->code1);
        self::$em->persist($t1);

        $this->code2 = new Code();
        $this->code2->setCode('test.code.2')
            ->setFrontend(1)
            ->setBackend(0)
            ->setLength(11)
            ->setPackage($this->package)
            ->setLocation($this->location);
        self::$em->persist($this->code2);

        self::$em->flush();

        $t2 = new Translation();
        $t2->setValue('Test Code 2')
            ->setCatalogue($this->catalogue)
            ->setCode($this->code2);
        self::$em->persist($t2);

        $this->code3 = new Code();
        $this->code3->setCode('test.code.3')
            ->setFrontend(1)
            ->setBackend(1)
            ->setLength(11)
            ->setPackage($this->package)
            ->setLocation($this->location);
        self::$em->persist($this->code3);

        self::$em->flush();

        $t3 = new Translation();
        $t3->setValue('Test Code 3')
            ->setCatalogue($this->catalogue)
            ->setCode($this->code3);
        self::$em->persist($t3);

        self::$em->flush();
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Catalogue'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Code'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Location'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Package'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Translation'),
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function testGetAll()
    {
        $client = static::createClient();
    }

    public function testGetAllSorted()
    {
        $client = static::createClient();
    }

    public function testGetAllPageSize()
    {
        $pageSize = 2;
        $client = static::createClient();
    }

    public function testGetAllFields()
    {
        $client = static::createClient();
    }

    public function testGetId()
    {
        $client = static::createClient();
    }

    public function testPost()
    {
        $client = static::createClient();
    }

    public function testPostWithoutName()
    {
        $client = static::createClient();
    }

    public function testPut()
    {
        $client = static::createClient();
    }

    public function testPutNotExisting()
    {
        $client = static::createClient();
    }

    // TODO test a few bad requests
}

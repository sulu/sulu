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
use Symfony\Component\HttpKernel\Client;

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
    /**
     * @var Client
     */
    private $client;
    /**
     * @var integer
     */
    private $pageSize;

    public function setUp()
    {
        // config section
        $this->client = static::createClient();
        $this->pageSize = 2;

        $this->setUpSchema();

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
            ->setLength(9)
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
            ->setLength(10)
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
        $this->client->request('GET', '/translate/api/codes');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);
        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->items[2]->code);
    }

    public function testGetAllSorted()
    {
        $this->client->request('GET', '/translate/api/codes?sortBy=id&sortOrder=asc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->items[2]->code);

        $this->client->request('GET', '/translate/api/codes?sortBy=id&sortOrder=desc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code3->getCode(), $response->items[2]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);

        $this->client->request('GET', '/translate/api/codes?sortBy=code&sortOrder=asc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->items[2]->code);

        $this->client->request('GET', '/translate/api/codes?sortBy=code&sortOrder=desc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code3->getCode(), $response->items[2]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);

        $this->client->request('GET', '/translate/api/codes?sortBy=length&sortOrder=asc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->items[2]->code);

        $this->client->request('GET', '/translate/api/codes?sortBy=length&sortOrder=desc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code3->getCode(), $response->items[2]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);
    }

    public function testGetAllPageSize()
    {
        $this->client->request('GET', '/translate/api/codes?pageSize=' . $this->pageSize);
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->pageSize, count($response->items));
        $this->assertEquals($this->pageSize, $response->total);
        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);

        $this->client->request('GET', '/translate/api/codes?pageSize=' . $this->pageSize . '&page=2');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, count($response->items)); // only 1 item remaining
        $this->assertEquals(1, $response->total); // only 1 item remaining
        $this->assertEquals($this->code3->getCode(), $response->items[0]->name);
    }

    public function testGetAllFields()
    {
        $this->client->request('GET', '/translate/api/codes?fields=code');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);
        $this->assertFalse(isset($response->items[0]->id));
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertFalse(isset($response->items[1]->id));
        $this->assertEquals($this->code3->getCode(), $response->items[2]->code);
        $this->assertFalse(isset($response->items[2]->id));

        $this->client->request('GET', '/translate/api/codes?fields=code,id');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);
        $this->assertEquals(1, $response->items[0]->id);
        $this->assertEquals($this->code1->getCode(), $response->items[1]->code);
        $this->assertEquals(2, $response->items[1]->id);
        $this->assertEquals($this->code1->getCode(), $response->items[2]->code);
        $this->assertEquals(3, $response->items[2]->id);
    }

    public function testGetId()
    {

    }

    public function testPost()
    {

    }

    public function testPostWithoutName()
    {

    }

    public function testPut()
    {

    }

    public function testPutNotExisting()
    {

    }

    // TODO test a few bad requests
}

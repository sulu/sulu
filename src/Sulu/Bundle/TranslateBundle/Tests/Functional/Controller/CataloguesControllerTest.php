<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Functional\Controller;


use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;

class CataloguesControllerTest extends DatabaseTestCase
{

    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var SchemaTool
     */
    protected static $tool;

    public function setUp()
    {

        $this->setUpSchema();

        $package = new Package();
        $package->setName("Sulu");
        self::$em->persist($package);

        $catalogue = new Catalogue();
        $catalogue->setPackage($package);
        $catalogue->setLocale('EN');
        self::$em->persist($catalogue);

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
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Catalogue'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Code'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Location'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Package'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Translation'),
        );

        self::$tool->createSchema(self::$entities);
    }

    public function testGet()
    {
        $client = static::createClient();

        $client->request('GET', '/translate/api/catalogues');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('EN', $response->items[0]->locale);
    }

    public function testGetByPackage()
    {
        $client = static::createClient();

        $client->request('GET', '/translate/api/catalogues?package=1');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('EN', $response->items[0]->locale);
    }

    public function testGetById()
    {
        $client = static::createClient();

        $client->request('GET', '/translate/api/catalogues/1');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('EN', $response->locale);
    }

    public function testDeleteById()
    {

        $client = static::createClient();

        $client->request('DELETE', '/translate/api/catalogues/1');
        $this->assertEquals('204', $client->getResponse()->getStatusCode());


        //$client->request('GET', '/translate/api/catalogues/1');
        //$response = json_decode($client->getResponse()->getContent());
        //$this->assertEquals('404', $client->getResponse()->getStatusCode());
    }

    public function testDeleteByIdNotExisting()
    {

        $client = static::createClient();

        $client->request('DELETE', '/translate/api/catalogues/4711');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());


        $client->request('GET', '/translate/api/catalogues');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }
}

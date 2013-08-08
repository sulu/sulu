<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Controller;

use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;

class PackagesControllerTest extends DatabaseTestCase
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
        $package->setName('Sulu');
        $catalogue = new Catalogue();
        $catalogue->setPackage($package);
        $catalogue->setCode('EN');
        self::$em->persist($catalogue);
        self::$em->persist($package);

        $package = new Package();
        $package->setName('Global');
        self::$em->persist($package);

        $package = new Package();
        $package->setName('Portal');

        self::$em->persist($package);

        self::$em->flush();
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function setUpSchema() {
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

    public function testGetAll()
    {
        $client = static::createClient();
        $client->request('GET', '/translate/api/packages');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);
        $this->assertEquals('Sulu', $response->items[0]->name);
        $this->assertEquals('Global', $response->items[1]->name);
        $this->assertEquals('Portal', $response->items[2]->name);
    }

    public function testGetAllSorted()
    {
        $client = static::createClient();

        $client->request('GET', '/translate/api/packages?sortBy=name&sortOrder=asc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Global', $response->items[0]->name);
        $this->assertEquals('Portal', $response->items[1]->name);
        $this->assertEquals('Sulu', $response->items[2]->name);

        $client->request('GET', '/translate/api/packages?sortBy=name&sortOrder=desc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Global', $response->items[2]->name);
        $this->assertEquals('Portal', $response->items[1]->name);
        $this->assertEquals('Sulu', $response->items[0]->name);

        $client->request('GET', '/translate/api/packages?sortBy=id&sortOrder=asc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->items[0]->name);
        $this->assertEquals('Global', $response->items[1]->name);
        $this->assertEquals('Portal', $response->items[2]->name);

        $client->request('GET', '/translate/api/packages?sortBy=id&sortOrder=desc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->items[2]->name);
        $this->assertEquals('Global', $response->items[1]->name);
        $this->assertEquals('Portal', $response->items[0]->name);
    }

    public function testGetAllPageSize()
    {
        $pageSize = 2;

        $client = static::createClient();
        $client->request('GET', '/translate/api/packages?pageSize=' . $pageSize);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($pageSize, count($response->items));
        $this->assertEquals($pageSize, $response->total);
        $this->assertEquals('Sulu', $response->items[0]->name);
        $this->assertEquals('Global', $response->items[1]->name);

        $client->request('GET', '/translate/api/packages?pageSize=' . $pageSize . '&page=2');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, count($response->items)); // only 1 item remaining
        $this->assertEquals(1, $response->total); // only 1 item remaining
        $this->assertEquals('Portal', $response->items[0]->name);
    }

    public function testGetAllFields()
    {
        $client = static::createClient();

        $client->request('GET', '/translate/api/packages?fields=name');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->items[0]->name);
        $this->assertEquals('Global', $response->items[1]->name);
        $this->assertEquals('Portal', $response->items[2]->name);

        $this->assertFalse(isset($response->items[0]->id));
        $this->assertFalse(isset($response->items[1]->id));
        $this->assertFalse(isset($response->items[2]->id));

        $client->request('GET', '/translate/api/packages?fields=name,id');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->items[0]->name);
        $this->assertEquals(1, $response->items[0]->id);
        $this->assertEquals('Global', $response->items[1]->name);
        $this->assertEquals(2, $response->items[1]->id);
        $this->assertEquals('Portal', $response->items[2]->name);
        $this->assertEquals(3, $response->items[2]->id);
    }

    public function testGetId()
    {
        $client = static::createClient();
        $client->request('GET', '/translate/api/packages/1');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->id);
        $this->assertEquals('Sulu', $response->name);
        $this->assertEquals('EN', $response->catalogues[0]->code);
    }

    public function testPost()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/translate/api/packages',
            array(
                'name' => 'Portal',
                'catalogues' => array(
                    array('code' => 'EN'),
                    array('code' => 'DE'),
                    array('code' => 'ES')
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);

        $client->request('GET', '/translate/api/packages/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('EN', $response->catalogues[0]->code);
        $this->assertEquals('DE', $response->catalogues[1]->code);
        $this->assertEquals('ES', $response->catalogues[2]->code);
    }

    public function testPostWithoutLanguages()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/translate/api/packages',
            array(
                'name' => 'Portal'
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);

        $client->request(
            'GET',
            '/translate/api/packages/' . $response->id
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);
    }

    public function testPostWithoutName()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/translate/api/packages',
            array()
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testPut()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/translate/api/packages/1',
            array(
                'name' => 'Portal',
                'catalogues' => array(
                    array('id' => 1, 'code' => 'DE'),
                    array('code' => 'EN'),
                    array('code' => 'ES')
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);
        $this->assertEquals(1, $response->id);
        $this->assertContains('DE', $response->catalogues[0]->code);
        $this->assertContains('EN', $response->catalogues[1]->code);
        $this->assertContains('ES', $response->catalogues[2]->code);

        $client->request(
            'PUT',
            '/translate/api/packages/1',
            array(
                'name' => 'Portal',
                'catalogues' => array(
                    array('id' => 2, 'code' => 'ES'),
                    array('id' => 3, 'code' => 'DE')
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);
        $this->assertEquals(1, $response->id);
        $this->assertEquals(2, count($response->catalogues));
        $this->assertContains('ES', $response->catalogues[0]->code);
        $this->assertContains('DE', $response->catalogues[1]->code);
    }

    public function testPutWithoutLanguages()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/translate/api/packages/1',
            array(
                'name' => 'Portal'
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);
        $this->assertEquals(1, $response->id);

        $client->request(
            'GET',
            '/translate/api/packages'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->items[0]->name);
        $this->assertEquals(1, $response->items[0]->id);
    }

    public function testPutNotExisting()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/translate/api/packages/10',
            array('name' => 'Portal')
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }
}

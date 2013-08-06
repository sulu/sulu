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

class PackagesControllerTest extends DatabaseTestCase
{
    public function setUp()
    {
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
        self::$em->getConnection()->query(
            "START TRANSACTION; SET FOREIGN_KEY_CHECKS=0;
                        TRUNCATE TABLE tr_packages;
                        TRUNCATE TABLE tr_catalogues;
                        SET FOREIGN_KEY_CHECKS=1;COMMIT;
                    "
        );
    }

    public function testGetAll()
    {
        $client = static::createClient();
        $client->request('GET', '/translate/packages');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);
        $this->assertEquals('Sulu', $response->items[0]->name);
        $this->assertEquals('Global', $response->items[1]->name);
        $this->assertEquals('Portal', $response->items[2]->name);
    }

    public function testGetAllSorted()
    {
        $client = static::createClient();

        $client->request('GET', '/translate/packages?sortBy=name&sortOrder=asc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Global', $response->items[0]->name);
        $this->assertEquals('Portal', $response->items[1]->name);
        $this->assertEquals('Sulu', $response->items[2]->name);

        $client->request('GET', '/translate/packages?sortBy=name&sortOrder=desc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Global', $response->items[2]->name);
        $this->assertEquals('Portal', $response->items[1]->name);
        $this->assertEquals('Sulu', $response->items[0]->name);

        $client->request('GET', '/translate/packages?sortBy=id&sortOrder=asc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->items[0]->name);
        $this->assertEquals('Global', $response->items[1]->name);
        $this->assertEquals('Portal', $response->items[2]->name);

        $client->request('GET', '/translate/packages?sortBy=id&sortOrder=desc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->items[2]->name);
        $this->assertEquals('Global', $response->items[1]->name);
        $this->assertEquals('Portal', $response->items[0]->name);
    }

    public function testGetAllPageSize()
    {
        $pageSize = 2;

        $client = static::createClient();
        $client->request('GET', '/translate/packages?pageSize=' . $pageSize);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($pageSize, count($response->items));
        $this->assertEquals($pageSize, $response->total);
        $this->assertEquals('Sulu', $response->items[0]->name);
        $this->assertEquals('Global', $response->items[1]->name);

        $client->request('GET', '/translate/packages?pageSize=' . $pageSize . '&page=2');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, count($response->items)); // only 1 item remaining
        $this->assertEquals(1, $response->total); // only 1 item remaining
        $this->assertEquals('Portal', $response->items[0]->name);
    }

    public function testGetAllFields()
    {
        $client = static::createClient();

        $client->request('GET', '/translate/packages?fields=name');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->items[0]->name);
        $this->assertEquals('Global', $response->items[1]->name);
        $this->assertEquals('Portal', $response->items[2]->name);

        $this->assertFalse(isset($response->items[0]->id));
        $this->assertFalse(isset($response->items[1]->id));
        $this->assertFalse(isset($response->items[2]->id));

        $client->request('GET', '/translate/packages?fields=name,id');
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
        $client->request('GET', '/translate/packages/1');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->id);
        $this->assertEquals('Sulu', $response->name);
        $this->assertEquals(array('EN'), $response->codes);
        $this->assertFalse(isset($response->catalogues));
    }

    public function testPost()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/translate/packages',
            array(
                'name' => 'Portal',
                'codes' => array('EN', 'DE', 'ES')
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);

        $client->request('GET', '/translate/catalogues?package=' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('EN', $response->items[0]->code);
        $this->assertEquals('DE', $response->items[1]->code);
        $this->assertEquals('ES', $response->items[2]->code);
    }

    public function testPostWithoutLanguages()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/translate/packages',
            array(
                'name' => 'Portal'
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);

        $client->request(
            'GET',
            '/translate/packages/' . $response->id
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);
    }

    public function testPostWithoutName()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/translate/packages',
            array()
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testPut()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/translate/packages/1',
            array(
                'name' => 'Portal',
                'languages' => array('EN', 'DE', 'ES')
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);
        $this->assertEquals(1, $response->id);

        $client->request(
            'GET',
            '/translate/packages'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->items[0]->name);
        $this->assertEquals(1, $response->items[0]->id);
    }

    public function testPutWithoutLanguages()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/translate/packages/1',
            array(
                'name' => 'Portal'
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);
        $this->assertEquals(1, $response->id);

        $client->request(
            'GET',
            '/translate/packages'
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
            '/translate/packages/10',
            array('name' => 'Portal')
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }
}

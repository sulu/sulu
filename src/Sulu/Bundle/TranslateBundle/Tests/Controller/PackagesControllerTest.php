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

use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\TranslateBundle\Tests\DatabaseTestCase;

class PackagesControllerTest extends DatabaseTestCase
{
    public function setUp()
    {
        $package = new Package();
        $package->setName('Sulu');

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

    public function testGetId()
    {
        $client = static::createClient();
        $client->request('GET', '/translate/packages/1');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->id);
        $this->assertEquals('Sulu', $response->name);
    }

    public function testPost()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/translate/packages',
            array(
                'name' => 'Portal',
                'languages' => array('EN', 'DE', 'ES')
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);

        $client->request('GET', '/translate/catalogues?package='.$response->id);
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
            '/translate/packages/'.$response->id
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);
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
}

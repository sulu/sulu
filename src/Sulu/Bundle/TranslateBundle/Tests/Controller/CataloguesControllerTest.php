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

class CataloguesControllerTest extends DatabaseTestCase
{
    public function setUp()
    {
        $package = new Package();
        $package->setName("Sulu");
        self::$em->persist($package);

        $catalogue = new Catalogue();
        $catalogue->setPackage($package);
        $catalogue->setCode('EN');
        self::$em->persist($catalogue);

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

    public function testGet()
    {
        $client = static::createClient();

        $client->request('GET', '/translate/api/catalogues');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('EN', $response->items[0]->code);
    }

    public function testGetByPackage()
    {
        $client = static::createClient();

        $client->request('GET', '/translate/api/catalogues?package=1');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('EN', $response->items[0]->code);
    }

    public function testGetById()
    {
        $client = static::createClient();

        $client->request('GET', '/translate/api/catalogues/1');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('EN', $response->code);
    }
}

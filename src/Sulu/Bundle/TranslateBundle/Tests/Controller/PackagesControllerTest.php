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
        $package->setName("Sulu");

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
        $crawler = $client->request('GET', '/translate/packages.xml');

        $this->assertEquals(1, $crawler->filter('name:contains("Sulu")')->count());
    }

    public function testGetId()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/translate/packages/1.xml');

        $this->assertEquals(1, $crawler->filter('name:contains("Sulu")')->count());
    }

    public function testPost()
    {
        $client = static::createClient();

        $crawler = $client->request(
            'POST',
            '/translate/packages.xml',
            array(
                'name' => 'Portal',
                'languages' => array('EN', 'DE', 'ES')
            )
        );

        $packageId = $crawler->filterXPath('//result/id')->text();
        $this->assertEquals(1, $crawler->filter('name:contains("Portal")')->count());

        $crawler = $client->request('GET', '/translate/catalogues.xml?package='.$packageId);
        $this->assertEquals('EN', $crawler->filterXPath('//entry[1]/code')->text());
        $this->assertEquals('DE', $crawler->filterXPath('//entry[2]/code')->text());
        $this->assertEquals('ES', $crawler->filterXPath('//entry[3]/code')->text());
    }
}

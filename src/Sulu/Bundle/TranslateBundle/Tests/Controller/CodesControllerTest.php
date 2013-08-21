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
    private $package1;
    /**
     * @var Package
     */
    private $package2;
    /**
     * @var Location
     */
    private $location1;
    /**
     * @var Location
     */
    private $location2;
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

        $this->package1 = new Package();
        $this->package1->setName('Package1');
        self::$em->persist($this->package1);

        $this->package2 = new Package();
        $this->package2->setName('Package2');
        self::$em->persist($this->package2);

        $this->location1 = new Location();
        $this->location1->setName('Location1')
            ->setPackage($this->package1);
        self::$em->persist($this->location1);

        $this->location2 = new Location();
        $this->location2->setName('Location2')
            ->setPackage($this->package2);
        self::$em->persist($this->location2);

        $this->catalogue = new Catalogue();
        $this->catalogue->setLocale('EN')
            ->setPackage($this->package1);
        self::$em->persist($this->catalogue);

        $this->code1 = new Code();
        $this->code1->setCode('test.code.1')
            ->setFrontend(0)
            ->setBackend(1)
            ->setLength(9)
            ->setPackage($this->package1)
            ->setLocation($this->location1);
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
            ->setPackage($this->package1)
            ->setLocation($this->location1);
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
            ->setPackage($this->package1)
            ->setLocation($this->location1);
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

        $this->assertEquals($this->code3->getCode(), $response->items[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code1->getCode(), $response->items[2]->code);

        $this->client->request('GET', '/translate/api/codes?sortBy=code&sortOrder=asc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->items[2]->code);

        $this->client->request('GET', '/translate/api/codes?sortBy=code&sortOrder=desc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code3->getCode(), $response->items[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code1->getCode(), $response->items[2]->code);

        $this->client->request('GET', '/translate/api/codes?sortBy=length&sortOrder=asc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code1->getCode(), $response->items[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->items[2]->code);

        $this->client->request('GET', '/translate/api/codes?sortBy=length&sortOrder=desc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code3->getCode(), $response->items[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->items[1]->code);
        $this->assertEquals($this->code1->getCode(), $response->items[2]->code);
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
        $this->client->request('GET', '/translate/api/codes/' . $this->code1->getId());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code1->getId(), $response->id);
        $this->assertEquals($this->code1->getCode(), $response->code);
        $this->assertEquals($this->code1->getBackend(), $response->backend);
        $this->assertEquals($this->code1->getFrontend(), $response->frontend);
        $this->assertEquals($this->code1->getLength(), $response->length);
        $this->assertEquals($this->code1->getLocation()->getId(), $response->location->id);
        $this->assertEquals($this->code1->getPackage()->getId(), $response->package->id);
        $this->assertEquals($this->code1->getTranslations()->first()->getId(), $response->translations[0]->id);
    }

    public function testPost()
    {
        $request = array(
            'code' => 'test.code.4',
            'frontend' => '0',
            'backend' => '0',
            'length' => '12',
            'package' => array(
                'id' => $this->package2->getId()
            ),
            'location' => array(
                'id' => $this->location2->getId()
            )
        );
        $this->client->request(
            'POST',
            '/translate/api/codes',
            $request
        );
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('test.code.4', $response->name);

        $this->client->request('GET', '/translate/api/codes/' . $response->id);
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($request['code'], $response->code);
        $this->assertEquals($request['backend'], $response->backend);
        $this->assertEquals($request['frontend'], $response->frontend);
        $this->assertEquals($request['length'], $response->length);
        $this->assertEquals($request['location']['id'], $response->location->id);
        $this->assertEquals($request['package']['id'], $response->package->id);
    }

    public function testPostNullValues()
    {
        $r1 = array(
            'frontend' => '0',
            'backend' => '0',
            'length' => '12',
            'package' => array(
                'id' => $this->package2->getId()
            ),
            'location' => array(
                'id' => $this->location2->getId()
            )
        );
        $this->client->request(
            'POST',
            '/translate/api/codes',
            $r1
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $r2 = array(
            'code' => 'test.code.5',
            'backend' => '0',
            'length' => '12',
            'package' => array(
                'id' => $this->package2->getId()
            ),
            'location' => array(
                'id' => $this->location2->getId()
            )
        );
        $this->client->request(
            'POST',
            '/translate/api/codes',
            $r2
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $r3 = array(
            'code' => 'test.code.6',
            'frontend' => '0',
            'length' => '12',
            'package' => array(
                'id' => $this->package2->getId()
            ),
            'location' => array(
                'id' => $this->location2->getId()
            )
        );
        $this->client->request(
            'POST',
            '/translate/api/codes',
            $r3
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $r4 = array(
            'code' => 'test.code.7',
            'frontend' => '0',
            'backend' => '0',
            'length' => '12',
            'location' => array(
                'id' => $this->location2->getId()
            )
        );
        $this->client->request(
            'POST',
            '/translate/api/codes',
            $r4
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $r5 = array(
            'code' => 'test.code.8',
            'frontend' => '0',
            'backend' => '0',
            'package' => array(
                'id' => $this->package2->getId()
            )
        );
        $this->client->request(
            'POST',
            '/translate/api/codes',
            $r5
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testPut()
    {
        $request = array(
            'code' => 'test.code.4',
            'frontend' => '1',
            'backend' => '0',
            'length' => '20',
            'package' => array(
                'id' => $this->package2->getId()
            ),
            'location' => array(
                'id' => $this->location2->getId()
            )
        );
        $this->client->request(
            'PUT',
            '/translate/api/packages/1',
            $request
        );

        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, $response->id);
        $this->assertEquals($request['code'], $response->code);
        $this->assertEquals($request['frontend'], $response->frontend);
        $this->assertEquals($request['backend'], $response->backend);
        $this->assertEquals($request['length'], $response->length);
        $this->assertEquals($request['package']['id'], $response->package->id);
        $this->assertEquals($request['location']['id'], $response->location->id);
    }

    public function testPutNotExisting()
    {
        $request = array(
            'code' => 'test.code.4',
            'frontend' => '1',
            'backend' => '0',
            'length' => '20',
            'package' => array(
                'id' => $this->package2->getId()
            ),
            'location' => array(
                'id' => $this->location2->getId()
            )
        );
        $this->client->request(
            'PUT',
            '/translate/api/packages/125',
            $request
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    public function testPutNotExistingPackage()
    {
        $request = array(
            'code' => 'test.code.1',
            'frontend' => '1',
            'backend' => '0',
            'length' => '20',
            'package' => array(
                'id' => 5
            ),
            'location' => array(
                'id' => $this->location2->getId()
            )
        );
        $this->client->request(
            'PUT',
            '/translate/api/packages/1',
            $request
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/translate/api/codes/1');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($request['code'], $response->code);
        $this->assertEquals($request['backend'], $response->backend);
        $this->assertEquals($request['frontend'], $response->frontend);
        $this->assertEquals($request['length'], $response->length);
        $this->assertEquals($request['location']['id'], $response->location->id);
        $this->assertEquals($this->package1->getId(), $response->package->id);
    }

    public function testPutNotExistingLocation()
    {
        $request = array(
            'code' => 'test.code.4',
            'frontend' => '1',
            'backend' => '0',
            'length' => '20',
            'package' => array(
                'id' => $this->package2->getId()
            ),
            'location' => array(
                'id' => 5
            )
        );
        $this->client->request(
            'PUT',
            '/translate/api/packages/1',
            $request
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/translate/api/codes/1');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($request['code'], $response->code);
        $this->assertEquals($request['backend'], $response->backend);
        $this->assertEquals($request['frontend'], $response->frontend);
        $this->assertEquals($request['length'], $response->length);
        $this->assertEquals($this->location1->getId(), $response->location->id);
        $this->assertEquals($request['package']['id'], $response->package->id);
    }

}

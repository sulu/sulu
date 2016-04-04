<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Code;
use Sulu\Bundle\TranslateBundle\Entity\Location;
use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\TranslateBundle\Entity\Translation;
use Symfony\Component\HttpKernel\Client;

class CodeControllerTest extends SuluTestCase
{
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
    private $catalogue1;

    /**
     * @var Catalogue
     */
    private $catalogue2;

    /**
     * @var Catalogue
     */
    private $catalogue3;

    /**
     * @var Code
     */
    private $code1;

    /**
     * @var Translation
     */
    private $code1_t1;

    /**
     * @var Code
     */
    private $code2;

    /**
     * @var Translation
     */
    private $code2_t1;

    /**
     * @var Code
     */
    private $code3;

    /**
     * @var Translation
     */
    private $code3_t1;

    /**
     * @var Translation
     */
    private $code3_t2;

    /**
     * @var Code
     */
    private $code4;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var int
     */
    private $limit;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        // config section
        $this->client = $this->createAuthenticatedClient();
        $this->limit = 3;

        $this->package1 = new Package();
        $this->package1->setName('Package1');
        $this->em->persist($this->package1);

        $this->package2 = new Package();
        $this->package2->setName('Package2');
        $this->em->persist($this->package2);

        $this->location1 = new Location();
        $this->location1->setName('Location1')
            ->setPackage($this->package1);
        $this->em->persist($this->location1);

        $this->location2 = new Location();
        $this->location2->setName('Location2')
            ->setPackage($this->package2);
        $this->em->persist($this->location2);

        $this->catalogue1 = new Catalogue();
        $this->catalogue1->setLocale('EN')
            ->setIsDefault(false)
            ->setPackage($this->package1);
        $this->em->persist($this->catalogue1);

        $this->catalogue2 = new Catalogue();
        $this->catalogue2->setLocale('DE')
            ->setIsDefault(false)
            ->setPackage($this->package1);
        $this->em->persist($this->catalogue2);

        $this->catalogue3 = new Catalogue();
        $this->catalogue3->setLocale('FR')
            ->setIsDefault(false)
            ->setPackage($this->package1);
        $this->em->persist($this->catalogue3);

        $this->code1 = new Code();
        $this->code1->setCode('test.code.1')
            ->setFrontend(0)
            ->setBackend(1)
            ->setLength(9)
            ->setPackage($this->package1)
            ->setLocation($this->location1);
        $this->em->persist($this->code1);

        $this->em->flush();

        $this->code1_t1 = new Translation();
        $this->code1_t1->setValue('Test Code 1')
            ->setCatalogue($this->catalogue2)
            ->setCode($this->code1);
        $this->em->persist($this->code1_t1);

        $this->code2 = new Code();
        $this->code2->setCode('test.code.2')
            ->setFrontend(1)
            ->setBackend(0)
            ->setLength(10)
            ->setPackage($this->package1)
            ->setLocation($this->location1);
        $this->em->persist($this->code2);

        $this->em->flush();

        $this->code2_t1 = new Translation();
        $this->code2_t1->setValue('Test Code 2')
            ->setCatalogue($this->catalogue1)
            ->setCode($this->code2);
        $this->em->persist($this->code2_t1);

        $this->code3 = new Code();
        $this->code3->setCode('test.code.3')
            ->setFrontend(1)
            ->setBackend(1)
            ->setLength(11)
            ->setPackage($this->package2)
            ->setLocation($this->location1);
        $this->em->persist($this->code3);

        $this->em->flush();

        $this->code3_t1 = new Translation();
        $this->code3_t1->setValue('Test Code 3')
            ->setCatalogue($this->catalogue1)
            ->setCode($this->code3);
        $this->em->persist($this->code3_t1);

        $this->code3_t2 = new Translation();
        $this->code3_t2->setValue('Test Code 3.1')
            ->setCatalogue($this->catalogue2)
            ->setCode($this->code3);
        $this->em->persist($this->code3_t2);

        $this->code4 = new Code();
        $this->code4->setCode('test.code.4')
            ->setFrontend(1)
            ->setBackend(1)
            ->setLength(12)
            ->setPackage($this->package1)
            ->setLocation($this->location1);
        $this->em->persist($this->code4);

        $this->em->flush();
    }

    public function testGetAll()
    {
        $this->client->request('GET', '/api/codes');
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(4, $response->total);
        $this->assertEquals(4, count($response->_embedded->codes));

        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals(1, count($response->_embedded->codes[0]->translations));

        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals(1, count($response->_embedded->codes[1]->translations));

        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[2]->code);
        $this->assertEquals(2, count($response->_embedded->codes[2]->translations));

        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[3]->code);
        $this->assertEquals(0, count($response->_embedded->codes[3]->translations));
    }

    public function testGetAllFiltered()
    {
        $this->client->request('GET', '/api/codes?packageId=' . $this->package1->getId());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(3, count($response->_embedded->codes));

        $this->assertNotNull($response->_embedded->codes[0]->id);
        $this->assertEquals(1, count($response->_embedded->codes[0]->translations));

        $this->assertNotNull($response->_embedded->codes[1]->id);
        $this->assertEquals(1, count($response->_embedded->codes[1]->translations));

        $this->assertNotNull($response->_embedded->codes[2]->id);
        $this->assertEquals(0, count($response->_embedded->codes[2]->translations));

        $this->client->request('GET', '/api/codes?catalogueId= ' . $this->catalogue2->getId());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(3, count($response->_embedded->codes));

        $this->assertEquals(1, count($response->_embedded->codes[0]->translations));
        $this->assertNotNull($response->_embedded->codes[0]->id);

        $this->assertEquals(0, count($response->_embedded->codes[1]->translations));
        $this->assertNotNull($response->_embedded->codes[1]->id);

        $this->assertEquals(0, count($response->_embedded->codes[2]->translations));
        $this->assertNotNull($response->_embedded->codes[2]->id);
    }

    public function testGetAllFilteredNonExistingPackage()
    {
        $this->client->request('GET', '/api/codes?packageId=5123');
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(0, count($response->_embedded->codes));
        $this->assertEquals(0, $response->total);
    }

    public function testGetAllFilteredNonExistingCatalogue()
    {
        $this->client->request('GET', '/api/codes?catalogueId=5123');
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(0, count($response->_embedded->codes));
        $this->assertEquals(0, $response->total);
    }

    public function testGetAllPagination()
    {
        $this->client->request('GET', '/api/codes?limit=2&page=1');
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(2, count($response->_embedded->codes));
        $this->assertEquals(2, $response->total);

        $this->client->request('GET', '/api/codes?limit=2&page=2');
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(1, count($response->_embedded->codes));
        $this->assertEquals(1, $response->total);
    }

    public function testGetAllOrder()
    {
        $this->client->request('GET', '/api/codes?sortBy=id&sortOrder=desc');
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotNull($response->_embedded->codes[0]->id);
        $this->assertNotNull($response->_embedded->codes[1]->id);
        $this->assertNotNull($response->_embedded->codes[2]->id);
        $this->assertNotNull($response->_embedded->codes[3]->id);
    }

    public function testGetList()
    {
        $this->client->request('GET', '/api/codes?flat=true');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[2]->code);
        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[3]->code);
    }

    public function testGetListSorted()
    {
        $this->client->request('GET', '/api/codes?flat=true&sortBy=id&sortOrder=asc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[2]->code);
        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[3]->code);

        $this->client->request('GET', '/api/codes?flat=true&sortBy=id&sortOrder=desc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[2]->code);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[3]->code);

        $this->client->request('GET', '/api/codes?flat=true&sortBy=code&sortOrder=asc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[2]->code);
        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[3]->code);

        $this->client->request('GET', '/api/codes?flat=true&sortBy=code&sortOrder=desc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[2]->code);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[3]->code);

        $this->client->request('GET', '/api/codes?flat=true&sortBy=length&sortOrder=asc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[2]->code);
        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[3]->code);

        $this->client->request('GET', '/api/codes?flat=true&sortBy=length&sortOrder=desc');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[2]->code);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[3]->code);
    }

    public function testGetListlimit()
    {
        $this->client->request('GET', '/api/codes?flat=true&limit=' . $this->limit);
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->limit, count($response->_embedded->codes));
        $this->assertEquals(4, $response->total);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[2]->code);

        $this->client->request('GET', '/api/codes?flat=true&limit=' . $this->limit . '&page=2');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, count($response->_embedded->codes)); // only 1 item remaining
        $this->assertEquals(4, $response->total); // only 1 item remaining
        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[0]->code);
    }

    public function testGetListFields()
    {
        $this->client->request('GET', '/api/codes?flat=true&fields=id,code');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[0]->code);
        $this->assertFalse(isset($response->_embedded->codes[0]->packageId));
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[1]->code);
        $this->assertFalse(isset($response->_embedded->codes[1]->packageId));
        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[2]->code);
        $this->assertFalse(isset($response->_embedded->codes[2]->packageId));
        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[3]->code);
        $this->assertFalse(isset($response->_embedded->codes[3]->packageId));

        $this->client->request('GET', '/api/codes?flat=true&fields=id,code,location_name');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertNotNull($response->_embedded->codes[0]->id);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals($this->code1->getLocation()->getName(), $response->_embedded->codes[0]->location_name);

        $this->assertNotNull($response->_embedded->codes[1]->id);
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals($this->code2->getLocation()->getName(), $response->_embedded->codes[1]->location_name);

        $this->assertNotNull($response->_embedded->codes[2]->id);
        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[2]->code);
        $this->assertEquals($this->code3->getLocation()->getName(), $response->_embedded->codes[2]->location_name);

        $this->assertNotNull($response->_embedded->codes[3]->id);
        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[3]->code);
        $this->assertEquals($this->code4->getLocation()->getName(), $response->_embedded->codes[3]->location_name);
    }

    public function testGetListWhere()
    {
        $this->client->request('GET', '/api/codes?flat=true&packageId=' . $this->package1->getId() . '');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(3, count($response->_embedded->codes));
        $this->assertEquals(3, $response->total);

        $this->client->request('GET', '/api/codes?flat=true&packageId=' . $this->package2->getId() . '');
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, count($response->_embedded->codes));
        $this->assertEquals(1, $response->total);

        $this->assertEquals($this->code3->getCode(), $response->_embedded->codes[0]->code);
    }

    public function testGetListCombination()
    {
        $this->client->request(
            'GET',
            '/api/codes?flat=true&fields=id,code&packageId=' . $this->package1->getId() . '&limit=' . $this->limit . '&page=1&sortBy=code&sortOrder=desc'
        );
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(3, count($response->_embedded->codes));
        $this->assertEquals(3, $response->total);

        $this->assertEquals($this->code4->getCode(), $response->_embedded->codes[0]->code);
        $this->assertEquals($this->code2->getCode(), $response->_embedded->codes[1]->code);
        $this->assertEquals($this->code1->getCode(), $response->_embedded->codes[2]->code);
    }

    public function testGetId()
    {
        $this->client->request('GET', '/api/codes/' . $this->code3->getId());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertNotNull($this->code3->getId(), $response->id);
        $this->assertEquals($this->code3->getCode(), $response->code);
        $this->assertEquals($this->code3->getBackend(), $response->backend);
        $this->assertEquals($this->code3->getFrontend(), $response->frontend);
        $this->assertEquals($this->code3->getLength(), $response->length);
        $this->assertNotNull($this->code3->getLocation()->getId(), $response->location->id);
        $this->assertEquals($this->code3_t1->getValue(), $response->translations[0]->value);
        $this->assertEquals($this->code3_t2->getValue(), $response->translations[1]->value);
    }

    public function testGetIdNotExisting()
    {
        $this->client->request('GET', '/api/codes/5123');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPost()
    {
        $request = [
            'code' => 'test.code.4',
            'frontend' => '0',
            'backend' => '0',
            'length' => '12',
            'package' => [
                'id' => $this->package2->getId(),
            ],
            'location' => [
                'id' => $this->location2->getId(),
            ],
            'translations' => [
                ['value' => 'Translation 1', 'catalogue' => ['id' => $this->catalogue1->getId()]],
                ['value' => 'Translation 2', 'catalogue' => ['id' => $this->catalogue2->getId()]],
            ],
        ];
        $this->client->request(
            'POST',
            '/api/codes',
            $request
        );
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('test.code.4', $response->code);

        $this->client->request('GET', '/api/codes/' . $response->id);
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($request['code'], $response->code);
        $this->assertEquals(($request['backend'] == '0') ? false : true, $response->backend);
        $this->assertEquals(($request['frontend'] == '0') ? false : true, $response->frontend);
        $this->assertEquals($request['length'], $response->length);
        $this->assertEquals($request['location']['id'], $response->location->id);
        $this->assertEquals(2, count($response->translations));
        $this->assertEquals($request['translations'][0]['value'], $response->translations[0]->value);
        $this->assertEquals($request['translations'][1]['value'], $response->translations[1]->value);
    }

    public function testPostNullValues()
    {
        $r1 = [
            'frontend' => '0',
            'backend' => '0',
            'length' => '12',
            'package' => [
                'id' => $this->package2->getId(),
            ],
            'location' => [
                'id' => $this->location2->getId(),
            ],
        ];
        $this->client->request(
            'POST',
            '/api/codes',
            $r1
        );
        $this->assertHttpStatusCode(400, $this->client->getResponse());

        $r2 = [
            'code' => 'test.code.5',
            'frontend' => '0',
            'length' => '12',
            'package' => [
                'id' => $this->package2->getId(),
            ],
            'location' => [
                'id' => $this->location2->getId(),
            ],
        ];
        $this->client->request(
            'POST',
            '/api/codes',
            $r2
        );
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(400, $this->client->getResponse());

        $r3 = [
            'code' => 'test.code.6',
            'backend' => '0',
            'frontend' => '0',
            'length' => '12',
            'location' => [
                'id' => $this->location2->getId(),
            ],
        ];
        $this->client->request(
            'POST',
            '/api/codes',
            $r3
        );
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(400, $this->client->getResponse());

        $r4 = [
            'code' => 'test.code.7',
            'frontend' => '0',
            'backend' => '0',
            'length' => '12',
            'package' => [
                'id' => $this->package2->getId(),
            ],
        ];
        $this->client->request(
            'POST',
            '/api/codes',
            $r4
        );
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(400, $this->client->getResponse());

        $r5 = [
            'code' => 'test.code.8',
            'frontend' => '0',
            'backend' => '0',
            'package' => [
                'id' => $this->package2->getId(),
            ],
            'location' => [
                'id' => $this->location2->getId(),
            ],
        ];
        $this->client->request(
            'POST',
            '/api/codes',
            $r5
        );
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testPut()
    {
        $request = [
            'code' => 'test.code.4',
            'frontend' => '1',
            'backend' => '0',
            'length' => '20',
            'package' => [
                'id' => $this->package2->getId(),
            ],
            'location' => [
                'id' => $this->location2->getId(),
            ],
            'translations' => [
                ['value' => 'Test Code 1.1', 'catalogue' => ['id' => $this->catalogue1->getId()]],
                ['value' => 'Test Code 1.2', 'catalogue' => ['id' => $this->catalogue2->getId()]],
                ['value' => 'Test Code 1.3', 'catalogue' => ['id' => $this->catalogue3->getId()]],
            ],
        ];
        $this->client->request(
            'PUT',
            '/api/codes/' . $this->code1->getId(),
            $request
        );
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('GET', '/api/codes/' . $this->code1->getId());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotNull($response->id);
        $this->assertEquals($request['code'], $response->code);
        $this->assertEquals(($request['backend'] == '0') ? false : true, $response->backend);
        $this->assertEquals(($request['frontend'] == '0') ? false : true, $response->frontend);
        $this->assertEquals($request['length'], $response->length);
        $this->assertEquals($request['location']['id'], $response->location->id);
        $this->assertEquals(3, count($response->translations));
        $values = [
            $request['translations'][0]['value'],
            $request['translations'][1]['value'],
            $request['translations'][2]['value'],
        ];
        $this->assertTrue(in_array($response->translations[0]->value, $values));
        $values = array_diff($values, [$response->translations[0]->value]);
        $this->assertTrue(in_array($response->translations[1]->value, $values));
        $values = array_diff($values, [$response->translations[1]->value]);
        $this->assertTrue(in_array($response->translations[2]->value, $values));
        $values = array_diff($values, [$response->translations[2]->value]);
        $this->assertEquals(0, count($values));
    }

    public function testPutNotExisting()
    {
        $request = [
            'code' => 'test.code.4',
            'frontend' => '1',
            'backend' => '0',
            'length' => '20',
            'package' => [
                'id' => $this->package2->getId(),
            ],
            'location' => [
                'id' => $this->location2->getId(),
            ],
        ];
        $this->client->request(
            'PUT',
            '/api/codes/125',
            $request
        );
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPutNotExistingPackage()
    {
        $request = [
            'code' => 'test.code.1',
            'frontend' => '1',
            'backend' => '0',
            'length' => '20',
            'package' => [
                'id' => 512312,
            ],
            'location' => [
                'id' => $this->location2->getId(),
            ],
        ];
        $this->client->request(
            'PUT',
            '/api/codes/' . $this->code1->getId(),
            $request
        );
        $this->assertHttpStatusCode(500, $this->client->getResponse());

        $this->client->request('GET', '/api/codes/' . $this->code1->getId());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code1->getCode(), $response->code);
        $this->assertEquals($this->code1->getBackend(), $response->backend);
        $this->assertEquals($this->code1->getFrontend(), $response->frontend);
        $this->assertEquals($this->code1->getLength(), $response->length);
        $this->assertEquals($this->code1->getLocation()->getId(), $response->location->id);
    }

    public function testPutNotExistingLocation()
    {
        $request = [
            'code' => 'test.code.4',
            'frontend' => '1',
            'backend' => '0',
            'length' => '20',
            'package' => [
                'id' => $this->package2->getId(),
            ],
            'location' => [
                'id' => 5123,
            ],
        ];
        $this->client->request(
            'PUT',
            '/api/codes/' . $this->code1->getId(),
            $request
        );
        $this->assertHttpStatusCode(500, $this->client->getResponse());

        $this->client->request('GET', '/api/codes/' . $this->code1->getId());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->code1->getCode(), $response->code);
        $this->assertEquals($this->code1->getBackend(), $response->backend);
        $this->assertEquals($this->code1->getFrontend(), $response->frontend);
        $this->assertEquals($this->code1->getLength(), $response->length);
        $this->assertEquals($this->code1->getLocation()->getId(), $response->location->id);
    }

    public function testDeleteById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/codes/' . $this->code1->getId());
        $this->assertHttpStatusCode(204, $client->getResponse());
    }

    public function testDeleteByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/codes/4711');
        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request('GET', '/api/codes');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(4, $response->total);
    }
}

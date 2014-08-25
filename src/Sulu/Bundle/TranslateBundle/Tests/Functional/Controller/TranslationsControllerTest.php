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

use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Code;
use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\TranslateBundle\Entity\Translation;

class TranslationsControllerTest extends DatabaseTestCase
{

    /**
     * @var array
     */
    static $entities;

    public function setUp()
    {
        $this->setUpSchema();

        $package1 = new Package();
        $package1->setName('Package1');
        self::$em->persist($package1);

        $package2 = new Package();
        $package2->setName('Package2');
        self::$em->persist($package2);

        $catalogue1 = new Catalogue();
        $catalogue1->setLocale('de');
        $catalogue1->setIsDefault(true);
        $catalogue1->setPackage($package1);
        self::$em->persist($catalogue1);

        $catalogue2 = new Catalogue();
        $catalogue2->setLocale('fr');
        $catalogue2->setIsDefault(false);
        $catalogue2->setPackage($package1);
        self::$em->persist($catalogue2);

        $catalogue3 = new Catalogue();
        $catalogue3->setLocale('fr');
        $catalogue3->setIsDefault(false);
        $catalogue3->setPackage($package2);
        self::$em->persist($catalogue3);

        $code1 = new Code();
        $code1->setCode('code.1');
        $code1->setLength(100);
        $code1->setBackend(1);
        $code1->setFrontend(1);
        $code1->setPackage($package1);
        self::$em->persist($code1);

        $code2 = new Code();
        $code2->setCode('code.2');
        $code2->setLength(100);
        $code2->setBackend(1);
        $code2->setFrontend(1);
        $code2->setPackage($package1);
        self::$em->persist($code2);

        $code3 = new Code();
        $code3->setCode('code.3');
        $code3->setLength(100);
        $code3->setBackend(1);
        $code3->setFrontend(1);
        $code3->setPackage($package2);
        self::$em->persist($code3);

        self::$em->flush();

        $t1_1 = new Translation();
        $t1_1->setValue('Code 1.1');
        $t1_1->setCatalogue($catalogue1);
        $t1_1->setCode($code1);
        self::$em->persist($t1_1);

        $t1_2 = new Translation();
        $t1_2->setValue('Code 1.2');
        $t1_2->setCatalogue($catalogue2);
        $t1_2->setCode($code1);
        self::$em->persist($t1_2);

        $t2_2 = new Translation();
        $t2_2->setValue('Code 2.2');
        $t2_2->setCatalogue($catalogue2);
        $t2_2->setCode($code2);
        self::$em->persist($t2_2);

        self::$em->flush();
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Code'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Catalogue'),
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

    public function testGetAllWithSuggestions()
    {
        $client = static::createClient();
        $client->request('GET', '/api/catalogues/2/translations');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(2, $response->total);
        $this->assertEquals(2, sizeof($response->_embedded->translations));

        $item = $response->_embedded->translations[0];
        $this->assertEquals(1, $item->id);
        $this->assertEquals('Code 1.2', $item->value);
        $this->assertEquals(1, $item->code->id);
        $this->assertEquals('code.1', $item->code->code);
        $this->assertEquals('Code 1.1', $item->suggestion);

        $item = $response->_embedded->translations[1];
        $this->assertEquals(2, $item->id);
        $this->assertEquals('Code 2.2', $item->value);
        $this->assertEquals(2, $item->code->id);
        $this->assertEquals('code.2', $item->code->code);
        // No Suggestion
        $this->assertEquals('', $item->suggestion);
    }

    public function testPatch()
    {
        $request = array(
            array(
                'id' => 1,
                'value' => 'new code value 1.1',
                'code' => array(
                    'id' => 1,
                    'code' => 'code.1',
                    'frontend' => false,
                    'backend' => false,
                    'length' => 100
                )
            ),
            array(
                'id' => null,
                'value' => 'realy new Code',
                'code' => array(
                    'id' => null,
                    'code' => 'new.code',
                    'frontend' => false,
                    'backend' => false,
                    'length' => 101
                )
            )
        );
        $client = static::createClient();
        $client->request('PATCH', '/api/catalogues/1/translations', $request);
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client = static::createClient();
        $client->request('GET', '/api/catalogues/1/translations');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(3, $response->total);
        $this->assertEquals(3, sizeof($response->_embedded->translations));

        if ($response->_embedded->translations[0]->id === 1) {
            $i = 0;
        } elseif ($response->_embedded->translations[1]->id === 1) {
            $i = 1;
        } elseif ($response->_embedded->translations[2]->id === 1) {
            $i = 2;
        } else {
            $i = 0;
        }
        $this->assertEquals(1, $response->_embedded->translations[$i]->id);
        $this->assertEquals('new code value 1.1', $response->_embedded->translations[$i]->value);
        $this->assertEquals(1, $response->_embedded->translations[$i]->code->id);
        $this->assertEquals('code.1', $response->_embedded->translations[$i]->code->code);

        if ($response->_embedded->translations[0]->id === 2) {
            $i = 0;
        } elseif ($response->_embedded->translations[1]->id === 2) {
            $i = 1;
        } elseif ($response->_embedded->translations[2]->id === 2) {
            $i = 2;
        } else {
            $i = 0;
        }
        $this->assertEquals(2, $response->_embedded->translations[$i]->id);
        $this->assertEquals('', $response->_embedded->translations[$i]->value);
        $this->assertEquals(2, $response->_embedded->translations[$i]->code->id);
        $this->assertEquals('code.2', $response->_embedded->translations[$i]->code->code);

        if ($response->_embedded->translations[0]->id === 4) {
            $i = 0;
        } elseif ($response->_embedded->translations[1]->id === 4) {
            $i = 1;
        } elseif ($response->_embedded->translations[2]->id === 4) {
            $i = 2;
        } else {
            $i = 0;
        }
        $this->assertEquals(4, $response->_embedded->translations[$i]->id);
        $this->assertEquals('realy new Code', $response->_embedded->translations[$i]->value);
        $this->assertEquals(4, $response->_embedded->translations[$i]->code->id);
        $this->assertEquals('new.code', $response->_embedded->translations[$i]->code->code);
    }

}

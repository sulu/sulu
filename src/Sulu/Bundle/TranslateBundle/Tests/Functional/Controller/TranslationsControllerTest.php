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
use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\TranslateBundle\Entity\Translation;

class TranslationsControllerTest extends SuluTestCase
{
    protected $package1;
    protected $catalogue1;
    protected $catalogue2;
    protected $code1;
    protected $catalogue1Translation1;
    protected $catalogue1Translation2;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        $package1 = new Package();
        $package1->setName('Package1');
        $this->em->persist($package1);
        $this->package1 = $package1;

        $package2 = new Package();
        $package2->setName('Package2');
        $this->em->persist($package2);

        $catalogue1 = new Catalogue();
        $catalogue1->setLocale('de');
        $catalogue1->setIsDefault(true);
        $catalogue1->setPackage($package1);
        $this->em->persist($catalogue1);
        $this->catalogue1 = $catalogue1;

        $catalogue2 = new Catalogue();
        $catalogue2->setLocale('fr');
        $catalogue2->setIsDefault(false);
        $catalogue2->setPackage($package1);
        $this->em->persist($catalogue2);
        $this->catalogue2 = $catalogue2;

        $catalogue3 = new Catalogue();
        $catalogue3->setLocale('fr');
        $catalogue3->setIsDefault(false);
        $catalogue3->setPackage($package2);
        $this->em->persist($catalogue3);

        $code1 = new Code();
        $code1->setCode('code.1');
        $code1->setLength(100);
        $code1->setBackend(1);
        $code1->setFrontend(1);
        $code1->setPackage($package1);
        $this->em->persist($code1);
        $this->code1 = $code1;

        $code2 = new Code();
        $code2->setCode('code.2');
        $code2->setLength(100);
        $code2->setBackend(1);
        $code2->setFrontend(1);
        $code2->setPackage($package1);
        $this->em->persist($code2);

        $code3 = new Code();
        $code3->setCode('code.3');
        $code3->setLength(100);
        $code3->setBackend(1);
        $code3->setFrontend(1);
        $code3->setPackage($package2);
        $this->em->persist($code3);

        $this->em->flush();

        $t1_1 = new Translation();
        $t1_1->setValue('Code 1.1');
        $t1_1->setCatalogue($catalogue1);
        $t1_1->setCode($code1);
        $this->em->persist($t1_1);
        $this->catalogue1Translation1 = $t1_1;

        $t1_2 = new Translation();
        $t1_2->setValue('Code 1.2');
        $t1_2->setCatalogue($catalogue2);
        $t1_2->setCode($code1);
        $this->em->persist($t1_2);
        $this->catalogue1Translation2 = $t1_2;

        $t2_2 = new Translation();
        $t2_2->setValue('Code 2.2');
        $t2_2->setCatalogue($catalogue2);
        $t2_2->setCode($code2);
        $this->em->persist($t2_2);

        $this->em->flush();
    }

    public function testGetAllWithSuggestions()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/catalogues/' . $this->catalogue2->getId() . '/translations');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals(2, $response->total);
        $this->assertEquals(2, count($response->_embedded->translations));

        $item = $response->_embedded->translations[0];
        $this->assertNotNull($item->id);
        $this->assertEquals('Code 1.2', $item->value);
        $this->assertEquals($this->code1->getId(), $item->code->id);
        $this->assertEquals('code.1', $item->code->code);
        $this->assertEquals('Code 1.1', $item->suggestion);

        $item = $response->_embedded->translations[1];
        $this->assertNotNull($item->id);
        $this->assertEquals('Code 2.2', $item->value);
        $this->assertNotNull($item->code->id);
        $this->assertEquals('code.2', $item->code->code);
        // No Suggestion
        $this->assertEquals('', $item->suggestion);
    }

    public function testPatch()
    {
        $request = [
            [
                'id' => $this->code1->getId(),
                'value' => 'new code value 1.1',
                'code' => [
                    'id' => $this->code1->getId(),
                    'code' => 'code.1',
                    'frontend' => false,
                    'backend' => false,
                    'length' => 100,
                ],
            ],
            [
                'id' => null,
                'value' => 'realy new Code',
                'code' => [
                    'id' => null,
                    'code' => 'new.code',
                    'frontend' => false,
                    'backend' => false,
                    'length' => 101,
                ],
            ],
        ];
        $client = $this->createAuthenticatedClient();
        $client->request('PATCH', '/api/catalogues/' . $this->catalogue1->getId() . '/translations', $request);
        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/catalogues/' . $this->catalogue1->getId() . '/translations');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals(3, $response->total);
        $this->assertEquals(3, count($response->_embedded->translations));

        foreach ($response->_embedded->translations as $index => $code) {
            if ($code->code == 'code.1') {
                $this->assertNotNull($response->_embedded->translations[$index]->id);
                $this->assertEquals('new code value 1.1', $response->_embedded->translations[$index]->value);
            }

            if ($code->code == 'code.2') {
                $this->assertNotNull($response->_embedded->translations[$index]->id);
                $this->assertEquals('', $response->_embedded->translations[$index]->value);
            }

            if ($code->code == 'code.3') {
                $this->assertEquals('realy new Code', $response->_embedded->translations[$index]->value);
                $this->assertEquals('new.code', $response->_embedded->translations[$index]->code->code);
            }
        }

        $this->assertNotNull($index, 'Found translation');
    }
}

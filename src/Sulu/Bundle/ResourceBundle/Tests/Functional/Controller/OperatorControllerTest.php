<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ResourceBundle\Entity\Operator;
use Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation;
use Sulu\Bundle\ResourceBundle\Entity\OperatorValue;
use Sulu\Bundle\ResourceBundle\Entity\OperatorValueTranslation;
use Sulu\Bundle\ResourceBundle\Resource\DataTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpKernel\Client;

class OperatorControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Operator
     */
    protected $op2;

    /**
     * @var Operator
     */
    protected $op1;

    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
        $this->setUpOperators();
        $this->client = $this->createAuthenticatedClient();
    }

    protected function setUpOperators()
    {
        $this->op1 = $this->createSimpleOperator(DataTypes::NUMBER_TYPE, 'input', '>', 'groesser');
        $this->op2 = $this->createSimpleOperator(DataTypes::NUMBER_TYPE, 'input', '<', 'kleiner');
        $this->op3 = $this->createOperator(DataTypes::DATETIME_TYPE, 'datepicker', '<', 'kleiner');

        $this->em->flush();
    }

    protected function createOperator($type, $inputType, $operatorValue, $name, $locale = 'de')
    {
        $operator = new Operator();
        $operator->setType($type);
        $operator->setInputType($inputType);
        $operator->setOperator($operatorValue);

        $translation = new OperatorTranslation();
        $translation->setLocale($locale);
        $translation->setName($name);
        $translation->setOperator($operator);

        $operatorValue = new OperatorValue();
        $operatorValue->setOperator($operator);
        $operatorValue->setValue('value');

        $operatorValueTrans = new OperatorValueTranslation();
        $operatorValueTrans->setLocale($locale);
        $operatorValueTrans->setName('letzte Woche');
        $operatorValueTrans->setOperatorValue($operatorValue);

        $operatorValue1 = new OperatorValue();
        $operatorValue1->setOperator($operator);
        $operatorValue1->setValue('value1');

        $operatorValueTrans1 = new OperatorValueTranslation();
        $operatorValueTrans1->setLocale($locale);
        $operatorValueTrans1->setName('letzte Woche');
        $operatorValueTrans1->setOperatorValue($operatorValue1);

        $operatorValue2 = new OperatorValue();
        $operatorValue2->setOperator($operator);
        $operatorValue2->setValue('value2');

        $operatorValueTrans2 = new OperatorValueTranslation();
        $operatorValueTrans2->setLocale($locale);
        $operatorValueTrans2->setName('letzte Woche');
        $operatorValueTrans2->setOperatorValue($operatorValue2);

        $this->em->persist($operator);
        $this->em->persist($translation);
        $this->em->persist($operatorValue);
        $this->em->persist($operatorValueTrans);
        $this->em->persist($operatorValue1);
        $this->em->persist($operatorValueTrans1);
        $this->em->persist($operatorValue2);
        $this->em->persist($operatorValueTrans2);

        return $operator;
    }

    protected function createSimpleOperator($type, $inputType, $operatorValue, $name, $locale = 'de')
    {
        $operator = new Operator();
        $operator->setType($type);
        $operator->setInputType($inputType);
        $operator->setOperator($operatorValue);

        $translation = new OperatorTranslation();
        $translation->setLocale($locale);
        $translation->setName($name);
        $translation->setOperator($operator);

        $this->em->persist($operator);
        $this->em->persist($translation);

        return $operator;
    }

    public function testCget()
    {
        $this->client->request(
            'GET',
            '/api/operators?locale=de'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);
        $this->assertEquals(3, count($response->_embedded->items));
    }

    public function testCgetWithNoLocale()
    {
        $this->client->request(
            'GET',
            '/api/operators'
        );
        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }
}

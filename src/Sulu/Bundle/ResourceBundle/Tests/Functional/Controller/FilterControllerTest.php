<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use stdClass;
use Sulu\Bundle\ResourceBundle\Entity\Condition;
use Sulu\Bundle\ResourceBundle\Entity\ConditionGroup;
use Sulu\Bundle\ResourceBundle\Entity\Filter;
use Sulu\Bundle\ResourceBundle\Entity\FilterTranslation;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpKernel\Client;

class FilterControllerTest extends SuluTestCase
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
     * @var Filter
     */
    protected $filter1;

    /**
     * @var Filter
     */
    protected $filter2;

    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->db('ORM')->getOm();
        $this->purgeDatabase();
        $this->setUpFilter();
        $this->client = $this->createAuthenticatedClient();
    }

    protected function setUpFilter()
    {
        $this->filter1 = $this->createFilter('filter1', true, 'Contact');
        $this->filter2 = $this->createFilter('filter2', false, 'Product');

        $this->em->flush();
    }

    protected function createFilter($name, $andCombination, $entityName)
    {
        $filter = new Filter();
        $filter->setAndCombination($andCombination);
        $filter->setEntityName($entityName);
        $filter->setChanged(new \DateTime());
        $filter->setCreated(new \DateTime());

        // TODO
        $filter->setCreator($this->getTestUser());
        $filter->setChanger($this->getTestUser());

        $trans = new FilterTranslation();
        $trans->setLocale('de');
        $trans->setName($name);
        $trans->setFilter($filter);

        $filter->addTranslation($trans);

        $conditionGroup1 = new ConditionGroup();
        $conditionGroup1->setFilter($filter);
        $conditionGroup1->addCondition(
            $this->createCondition($conditionGroup1, Condition::TYPE_STRING, 'test', 'LIKE', 'name')
        );

        $conditionGroup2 = new ConditionGroup();
        $conditionGroup2->setFilter($filter);
        $conditionGroup2->addCondition(
            $this->createCondition($conditionGroup2, Condition::TYPE_NUMBER, '2', '=', 'id')
        );

        $conditionGroup3 = new ConditionGroup();
        $conditionGroup3->setFilter($filter);
        $conditionGroup3->addCondition(
            $this->createCondition($conditionGroup3, Condition::TYPE_DATETIME, '2015-01-01', '>', 'created')
        );
        $conditionGroup3->addCondition(
            $this->createCondition($conditionGroup3, Condition::TYPE_DATETIME, '2015-02-02', '<', 'created')
        );

        $filter->addConditionGroup($conditionGroup1);
        $filter->addConditionGroup($conditionGroup2);
        $filter->addConditionGroup($conditionGroup3);

        $this->em->persist($filter);
        $this->em->persist($trans);
        $this->em->persist($conditionGroup1);
        $this->em->persist($conditionGroup2);
        $this->em->persist($conditionGroup3);

        return $filter;
    }

    protected function createCondition($conditionGroup, $type, $value, $operator, $name)
    {
        $condition = new Condition();
        $condition->setType($type);
        $condition->setValue($value);
        $condition->setOperator($operator);
        $condition->setField($name);
        $condition->setConditionGroup($conditionGroup);

        $this->em->persist($condition);

        return $condition;
    }

    /**
     * Test Filter GET by ID
     */
    public function testGetById()
    {
        $this->client->request(
            'GET',
            '/api/filters/'.$this->filter1->getId()
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->filter1->getId(), $response->id);
        $this->assertEquals($this->filter1->getAndCombination(), $response->andCombination);
        $this->assertEquals($this->filter1->getEntityName(), $response->entityName);
        $this->assertNotEmpty($response->conditionGroups);

        $this->assertEquals(count($this->filter1->getConditionGroups()), count($response->conditionGroups));

        /** @var ConditionGroup $cg */
        $cg = $this->filter1->getConditionGroups()[0];
        $cgData = $this->getElementById($cg->getId(), $response->conditionGroups);
        $this->assertEquals($cg->getId(), $cgData->id);
        $this->assertEquals(count($cg->getConditions()), count($cgData->conditions));

        /** @var Condition $condition */
        $condition = $cg->getConditions()[0];
        $conditionData = $this->getElementById($condition->getId(), $cgData->conditions);
        $this->assertEquals($condition->getId(), $conditionData->id);
        $this->assertEquals($condition->getField(), $conditionData->field);
        $this->assertEquals($condition->getOperator(), $conditionData->operator);
        $this->assertEquals($condition->getType(), $conditionData->type);
        $this->assertEquals($condition->getValue(), $conditionData->value);
    }

    /**
     * @param $id
     * @param array $group
     * @return null|stdClass
     */
    protected function getElementById($id, array $group)
    {
        foreach ($group as $el) {
            if ($id === $el->id) {
                return $el;
            }
        }

        return null;
    }

    /**
     * Test GET all filters
     */
    public function testCget()
    {

    }

    /**
     * Test GET for non existing filter (404)
     */
    public function testGetByIdNotExisting()
    {

    }

    /**
     * Test POST to create a new filter with details
     */
    public function testPost()
    {

    }

    /**
     * Test POST to create a new filter without conditions
     */
    public function testPostWithoutConditions()
    {

    }

    /**
     * Test PUT to update an existing filter
     */
    public function testPut()
    {

    }

    /**
     * Test PUT to update an existing filter without conditions
     */
    public function testPutWithoutConditions()
    {

    }

    /**
     * Test DELETE
     */
    public function testDeleteById()
    {
    }

    /**
     * Test DELETE on none existing Object
     */
    public function testDeleteByIdNotExisting()
    {
    }
}

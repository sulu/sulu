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
use stdClass;
use Sulu\Bundle\ResourceBundle\Entity\Condition;
use Sulu\Bundle\ResourceBundle\Entity\ConditionGroup;
use Sulu\Bundle\ResourceBundle\Entity\Filter;
use Sulu\Bundle\ResourceBundle\Entity\FilterTranslation;
use Sulu\Bundle\ResourceBundle\Resource\DataTypes;
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

    /**
     * @var Filter
     */
    protected $filter3;

    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
        $this->setUpFilter();
        $this->client = $this->createAuthenticatedClient();
    }

    protected function setUpFilter()
    {
        $this->filter1 = $this->createFilter('filter1', 'and', 'contact', false);
        $this->filter2 = $this->createFilter('filter2', 'or', 'Product', true, $this->getTestUser());
        $this->filter3 = $this->createFilter('filter3', 'and', 'contact', true, $this->getTestUser());

        $this->em->flush();
    }

    protected function createFilter($name, $conjunction, $context, $private, $user = null)
    {
        $filter = new Filter();
        $filter->setConjunction($conjunction);
        $filter->setContext($context);
        $filter->setChanged(new \DateTime());
        $filter->setCreated(new \DateTime());
        $filter->setPrivate($private);
        $filter->setUser($user);

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
            $this->createCondition($conditionGroup1, DataTypes::STRING_TYPE, 'test', 'LIKE', 'name')
        );

        $conditionGroup2 = new ConditionGroup();
        $conditionGroup2->setFilter($filter);
        $conditionGroup2->addCondition(
            $this->createCondition($conditionGroup2, DataTypes::NUMBER_TYPE, '2', '=', 'id')
        );

        $conditionGroup3 = new ConditionGroup();
        $conditionGroup3->setFilter($filter);
        $conditionGroup3->addCondition(
            $this->createCondition($conditionGroup3, DataTypes::DATETIME_TYPE, '2015-01-01', '>', 'created')
        );
        $conditionGroup3->addCondition(
            $this->createCondition($conditionGroup3, DataTypes::DATETIME_TYPE, '2015-02-02', '<', 'created')
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
     * Test Filter GET by ID.
     */
    public function testGetById()
    {
        $this->client->request(
            'GET',
            '/api/filters/' . $this->filter1->getId() . '?locale=de'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->filter1->getId(), $response->id);
        $this->assertEquals($this->filter1->getConjunction(), $response->conjunction);
        $this->assertEquals($this->filter1->getContext(), $response->context);
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
     * Test Filter GET by ID, without passing a locale.
     */
    public function testGetByIdWithNoLocale()
    {
        $this->client->request(
            'GET',
            '/api/filters/' . $this->filter1->getId()
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    /**
     * @param $id
     * @param array $group
     *
     * @return null|stdClass
     */
    protected function getElementById($id, array $group)
    {
        foreach ($group as $el) {
            if ($id === $el->id) {
                return $el;
            }
        }

        return;
    }

    public function testCget()
    {
        $this->client->request(
            'GET',
            '/api/filters?locale=de&context=contact'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);
        $this->assertEquals(2, count($response->_embedded->filters));
    }

    public function testCgetWithNoLocale()
    {
        $this->client->request(
            'GET',
            '/api/filters?context=contact'
        );
        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testCgetFlat()
    {
        $this->client->request(
            'GET',
            '/api/filters?locale=de&flat=true&context=contact'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);
        $this->assertEquals(2, $response->total);
    }

    /**
     * Test GET for non existing filter (404).
     */
    public function testGetByIdNotExisting()
    {
        $this->client->request(
            'GET',
            '/api/filters/666?locale=de'
        );
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    /**
     * Test POST to create a new filter with details.
     */
    public function testPost()
    {
        $filter = $this->createFilterAsArray('newFilter', false, 'contact');
        $this->client->request('POST', '/api/filters', $filter);
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('GET', '/api/filters/' . $response->id . '?locale=de');
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($filter['conjunction'], $response->conjunction);
        $this->assertEquals($filter['context'], $response->context);
        $this->assertEquals($filter['name'], $response->name);

        $this->assertEquals(count($filter['conditionGroups']), count($response->conditionGroups));
        $this->assertEquals(
            count($filter['conditionGroups'][0]['conditions']),
            count($response->conditionGroups[0]->conditions)
        );
    }

    /**
     * Test POST without passing a locale.
     */
    public function testPostWithNoLocale()
    {
        $filter = $this->createFilterAsArray('newFilter', false, 'contact');
        unset($filter['locale']);
        $this->client->request('POST', '/api/filters', $filter);

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    /**
     * Test POST to create a new filter with details.
     */
    public function testPostWithNotDefinedContext()
    {
        $filter = $this->createFilterAsArray('newFilter', false, 'not defined');
        $this->client->request('POST', '/api/filters?locale=de', $filter);
        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    /**
     * Test POST to create a new filter with invalid data.
     */
    public function testInvalidPost()
    {
        $filter = [
            'conjunction' => false,
            'context' => 'contact',
        ];
        $this->client->request('POST', '/api/filters?locale=de', $filter);

        $this->assertHttpStatusCode(400, $this->client->getResponse());

        $filter = [
            'name' => 'name',
            'context' => 'contact',
        ];
        $this->client->request('POST', '/api/filters', $filter);

        $this->assertHttpStatusCode(400, $this->client->getResponse());

        $filter = [
            'name' => 'name',
            'conjunction' => false,
        ];
        $this->client->request('POST', '/api/filters?locale=de', $filter);

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function createFilterAsArray($name, $conjunction, $context, $partial = false)
    {
        $result = [
            'name' => $name,
            'conjunction' => $conjunction,
            'context' => $context,
            'locale' => 'de',
        ];

        if (!$partial) {
            $result['conditionGroups'] = [
                [
                    'conditions' => [
                        [
                            'value' => '5',
                            'field' => 'id',
                            'operator' => '>',
                            'type' => Condition::TYPE_NUMBER,
                        ],
                        [
                            'value' => 'test',
                            'field' => 'name',
                            'operator' => 'LIKE',
                            'type' => Condition::TYPE_STRING,
                        ],
                    ],
                ],
            ];
        }

        return $result;
    }

    /**
     * Test POST to create a new filter without conditions.
     */
    public function testPostWithoutConditions()
    {
        $filter = $this->createFilterAsArray('newFilter', 'and', 'account', true);
        $this->client->request('POST', '/api/filters', $filter);
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('GET', '/api/filters/' . $response->id . '?locale=de');
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($filter['conjunction'], $response->conjunction);
        $this->assertEquals($filter['context'], $response->context);
        $this->assertEquals($filter['name'], $response->name);
    }

    /**
     * Test PUT to update an existing filter.
     */
    public function testPut()
    {
        $newName = 'The new name';
        $conjunction = false;
        $newContext = 'account';

        // remove old condition group and add a new one
        $this->client->request(
            'PUT',
            '/api/filters/' . $this->filter1->getId(),
            [
                'name' => $newName,
                'locale' => 'de',
                'conjunction' => $conjunction,
                'context' => $newContext,
                'conditionGroups' => [
                    [
                        'conditions' => [
                            [
                                'value' => '6',
                                'field' => 'nr',
                                'operator' => '<',
                                'type' => Condition::TYPE_NUMBER,
                            ],
                            [
                                'value' => 'test',
                                'field' => 'comment',
                                'operator' => '%LIKE%',
                                'type' => Condition::TYPE_STRING,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($newName, $response->name);
        $this->assertEquals($conjunction, $response->conjunction);
        $this->assertEquals($newContext, $response->context);
        $this->assertEquals(1, count($response->conditionGroups));

        $conditionGroupId = $response->conditionGroups[0]->id;

        // remove old condition group and add a new one
        $this->client->request(
            'PUT',
            '/api/filters/' . $this->filter1->getId(),
            [
                'locale' => 'de',
                'conditionGroups' => [
                    [
                        'id' => $conditionGroupId,
                        'conditions' => [
                            [
                                'value' => '7',
                                'field' => 'id',
                                'operator' => 'LIKE',
                                'type' => Condition::TYPE_STRING,
                            ],
                            [
                                'value' => 'test2',
                                'field' => 'nr',
                                'operator' => '>',
                                'type' => Condition::TYPE_NUMBER,
                            ],
                        ],
                    ],
                    [
                        'conditions' => [
                            [
                                'value' => '123',
                                'field' => 'nr',
                                'operator' => '=<',
                                'type' => Condition::TYPE_NUMBER,
                            ],
                            [
                                'value' => 'test17',
                                'field' => 'comment',
                                'operator' => '%LIKE%',
                                'type' => Condition::TYPE_STRING,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($newName, $response->name);
        $this->assertEquals($conjunction, $response->conjunction);
        $this->assertEquals($newContext, $response->context);
        $this->assertEquals(2, count($response->conditionGroups));
    }

    /**
     * Test PUT to update an existing filter.
     */
    public function testPutWithNoLocale()
    {
        $newName = 'The new name';
        $conjunction = false;
        $newContext = 'account';

        $this->client->request(
            'PUT',
            '/api/filters/' . $this->filter1->getId(),
            [
                'name' => $newName,
                'conjunction' => $conjunction,
                'context' => $newContext,
                'conditionGroups' => [],
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    /**
     * Test PUT to update an existing condition group.
     */
    public function testPutNewConditionExistingConditionGroup()
    {
        $newName = 'The new name';
        $conjunction = false;
        $newContext = 'account';

        // remove old condition group and add a new one
        $this->client->request(
            'PUT',
            '/api/filters/' . $this->filter1->getId(),
            [
                'name' => $newName,
                'locale' => 'de',
                'conjunction' => $conjunction,
                'context' => $newContext,
                'conditionGroups' => [
                    [
                        'id' => $this->filter1->getConditionGroups()[0]->getId(),
                        'conditions' => [
                            [
                                'value' => '6',
                                'field' => 'nr',
                                'operator' => '<',
                                'type' => Condition::TYPE_NUMBER,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($newName, $response->name);
        $this->assertEquals($conjunction, $response->conjunction);
        $this->assertEquals($newContext, $response->context);
        $this->assertCount(1, $response->conditionGroups);
        $this->assertCount(1, $response->conditionGroups[0]->conditions);
        $this->assertEquals('6', $response->conditionGroups[0]->conditions[0]->value);
        $this->assertEquals('nr', $response->conditionGroups[0]->conditions[0]->field);
        $this->assertEquals('<', $response->conditionGroups[0]->conditions[0]->operator);
    }

    /**
     * Test PUT to update an existing filter without conditions.
     */
    public function testPutWithoutConditions()
    {
        $newName = 'The new name';
        $conjunction = false;
        $newContext = 'account';

        $this->client->request(
            'PUT',
            '/api/filters/' . $this->filter1->getId(),
            [
                'name' => $newName,
                'locale' => 'de',
                'conjunction' => $conjunction,
                'context' => $newContext,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($newName, $response->name);
        $this->assertEquals($conjunction, $response->conjunction);
        $this->assertEquals($newContext, $response->context);
    }

    /**
     * Test PUT to update a not existing filter.
     */
    public function testPutNotExisting()
    {
        $this->client->request('PUT', '/api/filters/666?locale=de', ['code' => 'Missing filter']);
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $this->assertEquals(
            'Entity with the type "SuluResourceBundle:Filter" and the id "666" not found.',
            $response->message
        );
    }

    /**
     * Test DELETE.
     */
    public function testDeleteById()
    {
        $this->client->request('DELETE', '/api/filters/' . $this->filter1->getId());
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request('GET', '/api/filters/' . $this->filter1->getId() . '?locale=de');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    /**
     * Test CDELETE.
     */
    public function testCDeleteByIds()
    {
        $this->client->request('DELETE',
            '/api/filters?ids=' . $this->filter1->getId() . ',' . $this->filter2->getId() . ',' . $this->filter3->getId(
            )
        );
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request('GET', '/api/filters?locale=de&context=contact');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEmpty($response->_embedded->filters);
    }

    /**
     * Test CDELETE with non existent ids.
     */
    public function testCDeleteByIdsNotExisting()
    {
        $this->client->request('DELETE', '/api/filters?ids=666,999');
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request('GET', '/api/filters?locale=de&context=contact&flat=true');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(2, count($response->_embedded->filters));
    }

    /**
     * Test CDELETE with partially existent ids.
     */
    public function testCDeleteByIdsPartialExistent()
    {
        $this->client->request('DELETE', '/api/filters?ids=' . $this->filter1->getId() . ',666');
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request('GET', '/api/filters?locale=de&context=contact&flat=true');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(1, count($response->_embedded->filters));
    }

    /**
     * Test DELETE on none existing Object.
     */
    public function testDeleteByIdNotExisting()
    {
        $this->client->request('GET', '/api/filters/666?locale=de');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }
}

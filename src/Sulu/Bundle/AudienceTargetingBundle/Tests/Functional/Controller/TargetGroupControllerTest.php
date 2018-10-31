<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Functional\Controller;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TargetGroupControllerTest extends SuluTestCase
{
    const BASE_URL = 'api/target-groups';

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->purgeDatabase();
    }

    public function testGetById()
    {
        $targetGroup = $this->createTargetGroup([
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
            'webspaces' => [
                [
                    'webspaceKey' => 'my-webspace-1',
                ],
                [
                    'webspaceKey' => 'my-webspace-2',
                ],
            ],
        ]);

        $this->getEntityManager()->flush();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', self::BASE_URL . '/' . $targetGroup->getId());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Target Group Title', $response['title']);
        $this->assertEquals('Target group description number 1', $response['description']);
        $this->assertEquals(3, $response['priority']);
        $this->assertEquals(true, $response['active']);
        $this->assertCount(2, $response['webspaces']);
    }

    public function testGetAll()
    {
        $targetGroup1 = $this->createTargetGroup([
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
        ]);
        $targetGroup2 = $this->createTargetGroup([
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
        ]);

        $this->getEntityManager()->flush();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', self::BASE_URL);

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $targetGroups = $response['_embedded']['target-groups'];
        $this->assertCount(2, $targetGroups);
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
            'webspaces' => [
                [
                    'webspaceKey' => 'my-webspace-1',
                ],
                [
                    'webspaceKey' => 'my-webspace-2',
                ],
            ],
            'rules' => [
                [
                    'title' => 'rule-1',
                    'frequency' => 1,
                    'conditions' => [
                        [
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'de',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->request('POST', self::BASE_URL, [], [], [], json_encode($data));

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($data['title'], $response['title']);
        $this->assertEquals($data['description'], $response['description']);
        $this->assertEquals($data['priority'], $response['priority']);
        $this->assertEquals($data['active'], $response['active']);
        $this->assertCount(count($data['webspaces']), $response['webspaces']);

        $this->assertNotNull($this->getTargetGroupRepository()->find($response['id']));
        $targetGroup = $this->getTargetGroupRepository()->find($response['id']);
        $rule1 = $targetGroup->getRules()[0];
        $rule1Conditions = $rule1->getConditions()[0];
        $this->assertEquals($data['rules'][0]['title'], $rule1->getTitle());
        $this->assertEquals($data['rules'][0]['frequency'], $rule1->getFrequency());
        $this->assertEquals($data['rules'][0]['conditions'][0]['type'], $rule1Conditions->getType());
        $this->assertEquals($data['rules'][0]['conditions'][0]['condition'], $rule1Conditions->getCondition());
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $targetGroup = $this->createTargetGroup([
            'title' => 'Target Group Title',
        ]);

        $this->getEntityManager()->flush();

        $data = [
            'title' => 'Target Group Title 2',
            'description' => 'Target group description number 2',
            'priority' => 4,
            'active' => false,
            'webspaces' => [
                [
                    'webspaceKey' => 'my-webspace-1',
                ],
            ],
            'rules' => [
                [
                    'title' => 'rule-1',
                    'frequency' => 1,
                    'conditions' => [
                        [
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'de',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->request('PUT', self::BASE_URL . '/' . $targetGroup->getId(), [], [], [], json_encode($data));

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data['title'], $response['title']);
        $this->assertEquals($data['description'], $response['description']);
        $this->assertEquals($data['priority'], $response['priority']);
        $this->assertEquals($data['active'], $response['active']);
        $this->assertCount(count($data['webspaces']), $response['webspaces']);
        $this->assertCount(count($data['rules']), $response['rules']);
        $this->assertEquals($data['webspaces'][0]['webspaceKey'], $response['webspaces'][0]['webspaceKey']);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($response['id']);
        $this->assertNotNull($targetGroup);
        $this->assertCount(count($data['webspaces']), $targetGroup->getWebspaces());
        $this->assertCount(count($data['rules']), $targetGroup->getRules());
        $webspace1 = $targetGroup->getWebspaces()[0];
        $rule1 = $targetGroup->getRules()[0];
        $rule1Conditions = $rule1->getConditions()[0];
        $this->assertCount(count($data['webspaces']), $response['webspaces']);
        $this->assertEquals($data['webspaces'][0]['webspaceKey'], $webspace1->getWebspaceKey());
        $this->assertCount(count($data['rules']), $response['rules']);
        $this->assertEquals($data['rules'][0]['title'], $rule1->getTitle());
        $this->assertEquals($data['rules'][0]['frequency'], $rule1->getFrequency());
        $this->assertEquals($data['rules'][0]['conditions'][0]['type'], $rule1Conditions->getType());
        $this->assertEquals($data['rules'][0]['conditions'][0]['condition'], $rule1Conditions->getCondition());
    }

    public function testPutWithRemoveRoleAndWebspaces()
    {
        $client = $this->createAuthenticatedClient();

        $targetGroup = $this->createTargetGroup([
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
            'webspaces' => [
                [
                    'webspaceKey' => 'my-webspace-1',
                ],
                [
                    'webspaceKey' => 'my-webspace-2',
                ],
            ],
            'rules' => [
                [
                    'title' => 'rule-1',
                    'frequency' => 1,
                    'conditions' => [
                        [
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'de',
                            ],
                        ],
                        [
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'en',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'rule-2',
                    'frequency' => 2,
                    'conditions' => [],
                ],
            ],
        ]);

        $this->getEntityManager()->flush();

        $data = [
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
            'webspaces' => [
                [
                    'webspaceKey' => 'my-webspace-1',
                ],
            ],
            'rules' => [
                [
                    'id' => $targetGroup->getRules()[0]->getId(),
                    'title' => 'rule-1',
                    'frequency' => 1,
                    'conditions' => [
                        [
                            'id' => $targetGroup->getRules()[0]->getConditions()[0]->getId(),
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'en',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->request('PUT', self::BASE_URL . '/' . $targetGroup->getId(), [], [], [], json_encode($data));

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(count($data['webspaces']), $response['webspaces']);
        $this->assertCount(count($data['rules']), $response['rules']);
        $this->assertCount(count($data['rules'][0]['conditions']), $response['rules'][0]['conditions']);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($response['id']);
        $this->assertNotNull($targetGroup);
        $this->assertCount(count($data['webspaces']), $targetGroup->getWebspaces());
        $this->assertEquals($data['webspaces'][0]['webspaceKey'], $targetGroup->getWebspaces()[0]->getWebspaceKey());
        $this->assertCount(count($data['rules']), $targetGroup->getRules());
        $rule1 = $targetGroup->getRules()[0];
        $this->assertEquals($data['rules'][0]['title'], $rule1->getTitle());
        $this->assertEquals($data['rules'][0]['frequency'], $rule1->getFrequency());
        $this->assertCount(count($data['rules'][0]['conditions']), $rule1->getConditions());
        $this->assertEquals($data['rules'][0]['conditions'][0]['type'], $rule1->getConditions()[0]->getType());
        $this->assertEquals($data['rules'][0]['conditions'][0]['condition'], $rule1->getConditions()[0]->getCondition());
    }

    public function testSingleDelete()
    {
        $targetGroup = $this->createTargetGroup([
            'title' => 'Target Group Title',
        ]);

        $this->getEntityManager()->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', self::BASE_URL . '/' . $targetGroup->getId());

        $response = $client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($targetGroup->getId());

        $this->assertNull($targetGroup);
    }

    public function testMultipleDelete()
    {
        $targetGroup1 = $this->createTargetGroup([
            'title' => 'Target Group Title',
        ]);
        $targetGroup2 = $this->createTargetGroup([
            'title' => 'Target Group Title',
        ]);

        $this->getEntityManager()->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            self::BASE_URL . '?ids=' . implode(',', [$targetGroup1->getId(), $targetGroup2->getId()])
        );

        $response = $client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($targetGroup1->getId());
        $targetGroup2 = $this->getTargetGroupRepository()->find($targetGroup2->getId());

        $this->assertNull($targetGroup);
        $this->assertNull($targetGroup2);
    }

    /**
     * Create a new Target Group.
     *
     * @param array $data
     *
     * @return TargetGroupInterface
     */
    private function createTargetGroup($data)
    {
        /** @var TargetGroupInterface $targetGroup */
        $targetGroup = $this->getTargetGroupRepository()->createNew();
        $this->getEntityManager()->persist($targetGroup);
        $targetGroup->setTitle($this->getProperty($data, 'title', 'Target Group'));
        $targetGroup->setDescription($this->getProperty($data, 'description', 'Target Group Description'));
        $targetGroup->setPriority($this->getProperty($data, 'priority', 1));
        $targetGroup->setAllWebspaces($this->getProperty($data, 'allWebspaces', false));
        $targetGroup->setActive($this->getProperty($data, 'active', true));

        $webspaces = $this->getProperty($data, 'webspaces', []);
        foreach ($webspaces as $index => $webspaceData) {
            $this->createTargetGroupWebspace($webspaceData, $targetGroup);
        }

        $rules = $this->getProperty($data, 'rules', []);
        foreach ($rules as $ruleData) {
            $this->createTargetGroupRule($ruleData, $targetGroup);
        }

        return $targetGroup;
    }

    /**
     * Creates a target group webspace entity.
     *
     * @param array $data
     * @param TargetGroupInterface $targetGroup
     *
     * @return TargetGroupWebspaceInterface
     */
    private function createTargetGroupWebspace($data, TargetGroupInterface $targetGroup)
    {
        /** @var TargetGroupWebspaceInterface $webspace */
        $webspace = $this->getTargetGroupWebspaceRepository()->createNew();
        $this->getEntityManager()->persist($targetGroup);
        $webspace->setTargetGroup($targetGroup);
        $webspace->setWebspaceKey($this->getProperty($data, 'webspaceKey', 'webspacekey-' . uniqid()));
        $targetGroup->addWebspace($webspace);

        return $webspace;
    }

    private function createTargetGroupRule($data, TargetGroupInterface $targetGroup)
    {
        $targetGroupRule = $this->getTargetGroupRuleRepository()->createNew();
        $this->getEntityManager()->persist($targetGroupRule);
        $targetGroupRule->setTargetGroup($targetGroup);
        $targetGroupRule->setTitle($data['title']);
        $targetGroupRule->setFrequency($data['frequency']);
        $targetGroup->addRule($targetGroupRule);

        $conditions = $this->getProperty($data, 'conditions', []);
        foreach ($conditions as $conditionData) {
            $this->createTargetGroupCondition($conditionData, $targetGroupRule);
        }

        return $targetGroupRule;
    }

    private function createTargetGroupCondition($data, TargetGroupRuleInterface $targetGroupRule)
    {
        $targetGroupCondition = $this->getTargetGroupConditionRepository()->createNew();
        $this->getEntityManager()->persist($targetGroupCondition);
        $targetGroupCondition->setRule($targetGroupRule);
        $targetGroupCondition->setType($data['type']);
        $targetGroupCondition->setCondition($data['condition']);
        $targetGroupRule->addCondition($targetGroupCondition);

        return $targetGroupCondition;
    }

    /**
     * Returns value from data array with given key. If none found, given default is returned.
     *
     * @param array $data
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    private function getProperty($data, $key, $default)
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * @return TargetGroupRepositoryInterface
     */
    private function getTargetGroupRepository()
    {
        return $this->getContainer()->get('sulu.repository.target_group');
    }

    /**
     * @return TargetGroupWebspaceRepositoryInterface
     */
    private function getTargetGroupWebspaceRepository()
    {
        return $this->getContainer()->get('sulu.repository.target_group_webspace');
    }

    /**
     * @return TargetGroupWebspaceRepositoryInterface
     */
    private function getTargetGroupRuleRepository()
    {
        return $this->getContainer()->get('sulu.repository.target_group_rule');
    }

    private function getTargetGroupConditionRepository()
    {
        return $this->getContainer()->get('sulu.repository.target_group_condition');
    }
}

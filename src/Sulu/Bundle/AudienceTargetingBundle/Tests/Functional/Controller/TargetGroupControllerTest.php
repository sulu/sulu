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
use Sulu\Bundle\AudienceTargetingBundle\Mapper\TargetGroupMapperInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TargetGroupControllerTest extends SuluTestCase
{
    const BASE_URL = 'api/target-groups';

    /**
     * @var TargetGroupInterface
     */
    private $targetGroup;

    /**
     * @var TargetGroupInterface
     */
    private $targetGroup2;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initOrm();
    }

    /**
     * Initialize test data.
     */
    public function initOrm()
    {
        $this->purgeDatabase();

        // Create first target group.
        $this->targetGroup = $this->getTargetGroupRepository()->createNew();
        $this->getTargetGroupMapper()->mapDataToTargetGroup($this->targetGroup, $this->getSampleData());
        $this->getEntityManager()->persist($this->targetGroup);

        // Create a second target group.
        $this->targetGroup2 = $this->getTargetGroupRepository()->createNew();
        $this->getTargetGroupMapper()->mapDataToTargetGroup($this->targetGroup2, $this->getSampleData());
        $this->getEntityManager()->persist($this->targetGroup2);

        // Flush.
        $this->getEntityManager()->flush();
    }

    /**
     * Test if controller returns correct entity when perform get by id request.
     */
    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', self::BASE_URL . '/' . $this->targetGroup->getId());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $sampleData = $this->getSampleData();

        $this->assertEquals($sampleData['title'], $response['title']);
        $this->assertEquals($sampleData['description'], $response['description']);
        $this->assertEquals($sampleData['priority'], $response['priority']);
        $this->assertEquals($sampleData['active'], $response['active']);
        $this->assertCount(count($sampleData['webspaces']), $response['webspaces']);
    }

    /**
     * Test if cget action returns all target-groups.
     */
    public function testGetAll()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', self::BASE_URL);

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $targetGroups = $response['_embedded']['target-groups'];
        $this->assertCount(2, $targetGroups);
    }

    /**
     * Test if post of target group.
     */
    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $sampleData = $this->getSampleData();

        $client->request('POST', self::BASE_URL, $sampleData);

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($sampleData['title'], $response['title']);
        $this->assertEquals($sampleData['description'], $response['description']);
        $this->assertEquals($sampleData['priority'], $response['priority']);
        $this->assertEquals($sampleData['active'], $response['active']);
        $this->assertCount(count($sampleData['webspaces']), $response['webspaces']);

        $this->assertNotNull($this->getTargetGroupRepository()->find($response['id']));
    }

    /**
     * Test if controller returns correct entity when perform get by id request.
     */
    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $sampleData = $this->getAlternativeSampleData();

        $client->request('PUT', self::BASE_URL . '/' . $this->targetGroup->getId(), $sampleData);

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($sampleData['title'], $response['title']);
        $this->assertEquals($sampleData['description'], $response['description']);
        $this->assertEquals($sampleData['priority'], $response['priority']);
        $this->assertEquals($sampleData['active'], $response['active']);
        $this->assertCount(count($sampleData['webspaces']), $response['webspaces']);
        $this->assertEquals($sampleData['webspaces'][0]['webspaceKey'], $response['webspaces'][0]['webspaceKey']);

        $this->assertNotNull($this->getTargetGroupRepository()->find($response['id']));
    }

    /**
     * Test deleting a target group over api.
     */
    public function testSingleDelete()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', self::BASE_URL . '/' . $this->targetGroup->getId());

        $response = $client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($this->targetGroup->getId());

        $this->assertNull($targetGroup);
    }

    /**
     * Test deleting multiple target groups over api.
     */
    public function testMultipleDelete()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            self::BASE_URL . '?ids=' . implode(',', [$this->targetGroup->getId(), $this->targetGroup2->getId()])
        );

        $response = $client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($this->targetGroup->getId());
        $targetGroup2 = $this->getTargetGroupRepository()->find($this->targetGroup2->getId());

        $this->assertNull($targetGroup);
        $this->assertNull($targetGroup2);
    }

    /**
     * Returns sample data for target group creation as array.
     *
     * @return array
     */
    private function getSampleData()
    {
        return [
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
        ];
    }

    /**
     * Returns alternative sample data for target group changes.
     *
     * @return array
     */
    private function getAlternativeSampleData()
    {
        return [
            'title' => 'Target Group Title 2',
            'description' => 'Target group description number 2',
            'priority' => 4,
            'active' => false,
            'webspaces' => [
                [
                    'webspaceKey' => 'my-webspace-1',
                ],
            ],
        ];
    }

    /**
     * @return TargetGroupMapperInterface
     */
    private function getTargetGroupMapper()
    {
        return $this->getContainer()->get('sulu_audience_targeting.target_group_mapper');
    }

    /**
     * @return TargetGroupRepositoryInterface
     */
    private function getTargetGroupRepository()
    {
        return $this->getContainer()->get('sulu.repository.target_group');
    }
}

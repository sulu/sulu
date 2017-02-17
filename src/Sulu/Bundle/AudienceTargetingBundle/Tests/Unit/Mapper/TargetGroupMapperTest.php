<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Mapper;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Mapper\TargetGroupMapperInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TargetGroupMapperTest extends SuluTestCase
{
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
    }

    /**
     * Test mapping data to target group.
     */
    public function testTargetGroupMapping()
    {
        $sampleData = $this->getSampleData();

        $targetGroup = $this->getTargetGroupRepository()->createNew();
        $this->getTargetGroupMapper()->mapDataToTargetGroup($targetGroup, $sampleData);

        $this->assertTrue($targetGroup instanceof TargetGroupInterface);
        $this->assertEquals($sampleData['title'], $targetGroup->getTitle());
        $this->assertEquals($sampleData['description'], $targetGroup->getDescription());
        $this->assertEquals($sampleData['priority'], $targetGroup->getPriority());
        $this->assertEquals($sampleData['active'], $targetGroup->isActive());
        $this->assertCount(count($sampleData['webspaces']), $targetGroup->getWebspaces());
    }

    /**
     * Get sample data for test.
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

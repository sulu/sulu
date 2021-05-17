<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Unit\Infrastructure\Sulu\Metadata;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Metadata\ActivitiesListMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadata;

class ActivitiesListMetadataVisitorTest extends TestCase
{
    /**
     * @var ActivitiesListMetadataVisitor
     */
    private $activitiesListMetadataVisitor;

    protected function setUp(): void
    {
        $this->activitiesListMetadataVisitor = new ActivitiesListMetadataVisitor();
    }

    public function testVisitOtherResource(): void
    {
        $listMetadata = $this->prophesize(ListMetadata::class);
        $listMetadata->getField(Argument::any())->shouldNotBeCalled();

        $this->activitiesListMetadataVisitor->visitListMetadata(
            $listMetadata->reveal(),
            'other',
            'en',
            ['showResource' => false]
        );
    }

    public function testVisitShowResourceTrue(): void
    {
        $listMetadata = new ListMetadata();
        $resourceFieldMetadata = new FieldMetadata('resource');
        $resourceFieldMetadata->setVisibility('no');
        $listMetadata->addField($resourceFieldMetadata);

        $this->activitiesListMetadataVisitor->visitListMetadata(
            $listMetadata,
            'activities',
            'en',
            ['showResource' => true]
        );

        $this->assertSame('yes', $listMetadata->getField('resource')->getVisibility());
    }

    public function testVisitShowResourceFalse(): void
    {
        $listMetadata = new ListMetadata();
        $resourceFieldMetadata = new FieldMetadata('resource');
        $resourceFieldMetadata->setVisibility('no');
        $listMetadata->addField($resourceFieldMetadata);

        $this->activitiesListMetadataVisitor->visitListMetadata(
            $listMetadata,
            'activities',
            'en',
            ['showResource' => false]
        );

        $this->assertSame('no', $listMetadata->getField('resource')->getVisibility());
    }
}

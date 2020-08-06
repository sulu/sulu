<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Content\Types;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\PageBundle\Content\Types\SinglePageSelection;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;

class SinglePageSelectionTest extends TestCase
{
    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    /**
     * @var ObjectProphecy
     */
    private $securityChecker;

    /**
     * @var SinglePageSelection
     */
    private $type;

    public function setUp(): void
    {
        parent::setUp();

        $this->property = $this->prophesize(PropertyInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);

        $this->type = new SinglePageSelection(
            $this->referenceStore->reveal(),
            $this->securityChecker->reveal()
        );
    }

    public function providePreResolve()
    {
        return [
            [
                '4234-2345-2345-3245',
                ['4234-2345-2345-3245'],
            ],
            [
                null,
                [],
            ],
            [
                '',
                [],
            ],
        ];
    }

    /**
     * @dataProvider providePreResolve
     */
    public function testPreResolve($propertyValue, $expected)
    {
        $this->property->getValue()->willReturn($propertyValue);
        $this->type->preResolve($this->property->reveal());

        if (0 === \count($expected)) {
            $this->referenceStore->add(Argument::any())->shouldNotBeCalled();
        }

        foreach ($expected as $uuid) {
            $this->referenceStore->add($uuid)->shouldBeCalled();
        }
    }

    public function testContentData()
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');
        $structure->getWebspaceKey()->willReturn('sulu_io');

        $this->property->getValue()->willReturn('some-uuid');
        $this->property->getStructure()->willReturn($structure->reveal());

        $this->securityChecker->hasPermission(
            new SecurityCondition(
                'sulu.webspaces.sulu_io',
                'de',
                SecurityBehavior::class,
                'some-uuid'
            ),
            PermissionTypes::VIEW
        )->willReturn(true);

        $this->assertEquals('some-uuid', $this->type->getContentData($this->property->reveal()));
    }

    public function testContentDataForMissingPermissions()
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');
        $structure->getWebspaceKey()->willReturn('sulu_io');

        $this->property->getValue()->willReturn('some-uuid');
        $this->property->getStructure()->willReturn($structure->reveal());

        $this->securityChecker->hasPermission(
            new SecurityCondition(
                'sulu.webspaces.sulu_io',
                'de',
                SecurityBehavior::class,
                'some-uuid'
            ),
            PermissionTypes::VIEW
        )->willReturn(false);

        $this->assertNull($this->type->getContentData($this->property->reveal()));
    }
}

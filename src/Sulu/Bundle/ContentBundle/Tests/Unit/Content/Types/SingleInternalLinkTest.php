<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types;

use Sulu\Bundle\ContentBundle\Content\Types\SingleInternalLink;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

class SingleInternalLinkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    /**
     * @var SingleInternalLink
     */
    private $type;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    public function setUp()
    {
        parent::setUp();

        $this->property = $this->prophesize(PropertyInterface::class);
        $this->structure = $this->prophesize(StructureInterface::class);
        $this->contentMapper = $this->prophesize(ContentMapperInterface::class);
        $this->structureResolver = $this->prophesize(StructureResolverInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->type = new SingleInternalLink(
            $this->contentMapper->reveal(),
            $this->structureResolver->reveal(),
            $this->referenceStore->reveal(),
            'some_template.html.twig'
        );
    }

    public function singleInternalLinkData()
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
     * @dataProvider singleInternalLinkData
     */
    public function testSingleInternalLink($propertyValue, $expected)
    {
        $this->property->getValue()->willReturn($propertyValue);
        $this->structure->getLanguageCode()->willReturn('de');
        $structure = $this->structure->reveal();
        $this->property->getStructure()->willReturn($structure);

        foreach ($expected as $uuid) {
            $this->referenceStore->add($uuid)->shouldBeCalled();
            $this->contentMapper->load($uuid, null, 'de')->willReturn($structure)->shouldBeCalled();
            $this->structureResolver->resolve($structure)->willReturn([])->shouldBeCalled();
        }

        $this->type->preResolve($this->property->reveal());
        $this->type->getContentData($this->property->reveal());
        $this->type->getViewData($this->property->reveal());
    }
}

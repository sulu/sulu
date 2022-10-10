<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Content\Types\SingleCollectionSelection;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;

class SingleCollectionSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SingleCollectionSelection
     */
    private $singleCollectionSelection;

    /**
     * @var ObjectProphecy<CollectionManagerInterface>
     */
    private $collectionManager;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $collectionReferenceStore;

    protected function setUp(): void
    {
        $this->collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $this->collectionReferenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->singleCollectionSelection = new SingleCollectionSelection(
            $this->collectionManager->reveal(),
            $this->collectionReferenceStore->reveal()
        );
    }

    public function testGetContentDataEmpty(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setStructure($structure->reveal());

        $this->assertNull($this->singleCollectionSelection->getContentData($property));
    }

    public function testGetContentDataNotFound(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setValue(1);
        $property->setStructure($structure->reveal());

        $this->collectionManager->getById(1, 'en')->willThrow(new CollectionNotFoundException('1'));

        $this->assertNull($this->singleCollectionSelection->getContentData($property));
    }

    public function testGetContentData(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setValue(1);
        $property->setStructure($structure->reveal());

        $collection = $this->prophesize(Collection::class);
        $this->collectionManager->getById(1, 'en')->willReturn($collection->reveal());

        $this->assertEquals($collection->reveal(), $this->singleCollectionSelection->getContentData($property));
    }

    public function testPreResolveEmpty(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setValue(null);
        $property->setStructure($structure->reveal());

        $this->collectionReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->singleCollectionSelection->preResolve($property);
    }

    public function testPreResolve(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setValue(22);
        $property->setStructure($structure->reveal());

        $this->collectionReferenceStore->add(22)->shouldBeCalled();

        $this->singleCollectionSelection->preResolve($property);
    }
}

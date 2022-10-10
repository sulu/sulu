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
use Sulu\Bundle\MediaBundle\Content\Types\CollectionSelection;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;

class CollectionSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var CollectionSelection
     */
    private $collectionSelection;

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

        $this->collectionSelection = new CollectionSelection(
            $this->collectionManager->reveal(),
            $this->collectionReferenceStore->reveal()
        );
    }

    public function testGetContentDataNull(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setStructure($structure->reveal());
        $property->setValue(null);

        $this->assertSame([], $this->collectionSelection->getContentData($property));
    }

    public function testGetContentDataEmptyArray(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setStructure($structure->reveal());
        $property->setValue([]);

        $this->assertSame([], $this->collectionSelection->getContentData($property));
    }

    public function testGetContentDataNotFound(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setStructure($structure->reveal());
        $property->setValue([22, 33]);

        $collection22 = $this->prophesize(Collection::class);
        $this->collectionManager->getById(22, 'en')->willReturn($collection22->reveal());
        $this->collectionManager->getById(33, 'en')->willThrow(new CollectionNotFoundException('1'));

        $this->assertSame(
            [$collection22->reveal()],
            $this->collectionSelection->getContentData($property)
        );
    }

    public function testGetContentData(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setStructure($structure->reveal());
        $property->setValue([44, 22]);

        $collection22 = $this->prophesize(Collection::class);
        $this->collectionManager->getById(22, 'en')->willReturn($collection22->reveal());
        $collection44 = $this->prophesize(Collection::class);
        $this->collectionManager->getById(44, 'en')->willReturn($collection44->reveal());

        $this->assertSame(
            [$collection44->reveal(), $collection22->reveal()],
            $this->collectionSelection->getContentData($property)
        );
    }

    public function testPreResolveNull(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setValue(null);
        $property->setStructure($structure->reveal());

        $this->collectionReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->collectionSelection->preResolve($property);
    }

    public function testPreResolveEmptyArray(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setValue([]);
        $property->setStructure($structure->reveal());

        $this->collectionReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->collectionSelection->preResolve($property);
    }

    public function testPreResolve(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('en');

        $property = new Property('collection', [], 'single_collection_selection');
        $property->setValue([44, 22]);
        $property->setStructure($structure->reveal());

        $this->collectionReferenceStore->add(44)->shouldBeCalled();
        $this->collectionReferenceStore->add(22)->shouldBeCalled();

        $this->collectionSelection->preResolve($property);
    }
}

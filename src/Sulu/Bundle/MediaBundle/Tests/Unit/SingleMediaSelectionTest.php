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

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\MediaBundle\Content\Types\SingleMediaSelection;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManager;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;

class SingleMediaSelectionTest extends TestCase
{
    /**
     * @var SingleMediaSelection
     */
    private $singleMediaSelection;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var ReferenceStoreInterface
     */
    private $mediaReferenceStore;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var #PropertyInterface
     */
    private $nodeProperty;

    /**
     * @var Media
     */
    private $media;

    protected function setUp(): void
    {
        $this->mediaManager = $this->prophesize(MediaManager::class);
        $this->mediaReferenceStore = $this->prophesize(ReferenceStore::class);
        $this->media = $this->prophesize(Media::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->nodeProperty = $this->prophesize(PropertyInterface::class);
        $this->singleMediaSelection = new SingleMediaSelection(
            $this->mediaManager->reveal(),
            $this->mediaReferenceStore->reveal()
        );
    }

    public function testReadEmpty()
    {
        $property = $this->prophesize(Property::class);
        $property->getName()->willReturn('media');

        $this->node->hasProperty('media')->willReturn(false);

        $property->setValue(['id' => null])->shouldBeCalled();

        $this->singleMediaSelection->read(
            $this->node->reveal(),
            $property->reveal(),
            'sulu',
            'de',
            ''
        );
    }

    public function testRead()
    {
        $property = $this->prophesize(Property::class);
        $property->getName()->willReturn('media');

        $this->node->hasProperty('media')->willReturn(true);
        $this->node->getPropertyValue('media')->willReturn('{"id":11}');

        $property->setValue(['id' => 11])->shouldBeCalled();

        $this->singleMediaSelection->read(
            $this->node->reveal(),
            $property->reveal(),
            'sulu',
            'de',
            ''
        );
    }

    public function testWriteEmpty()
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(null);

        $this->node->getProperty('media')->willReturn($this->nodeProperty->reveal());
        $this->node->hasProperty('media')->willReturn(true);

        $this->nodeProperty->remove()->shouldBeCalled();

        $this->singleMediaSelection->write(
            $this->node->reveal(),
            $property,
            null,
            'sulu',
            'de',
            ''
        );
    }

    public function testWrite()
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);

        $this->node->setProperty('media', '{"id":11}')->shouldBeCalled();

        $this->singleMediaSelection->write(
            $this->node->reveal(),
            $property,
            1,
            'sulu',
            'de',
            ''
        );
    }

    public function testDefaultParams()
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(null);

        $this->assertEquals(
            [],
            $this->singleMediaSelection->getDefaultParams($property)
        );
    }

    public function testDefaultValue()
    {
        $this->assertEquals(
            '{"id": null}',
            $this->singleMediaSelection->getDefaultValue()
        );
    }

    public function testViewDataEmpty()
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(null);

        $this->assertNull(
            $this->singleMediaSelection->getViewData($property)
        );
    }

    public function testViewData()
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);

        $this->assertEquals(
            ['id' => 11],
            $this->singleMediaSelection->getViewData($property)
        );
    }

    public function testContentDataEmpty()
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(null);

        $this->assertNull(
            $this->singleMediaSelection->getContentData($property)
        );
    }

    public function testContentData()
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);
        $property->setStructure($structure->reveal());

        $this->mediaManager->getById(11, 'de')->willReturn($this->media->reveal());

        $this->assertEquals($this->media->reveal(), $this->singleMediaSelection->getContentData($property));
    }

    public function testContentDataDeleted()
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);
        $property->setStructure($structure->reveal());

        $this->mediaManager->getById(11, 'de')->willThrow(MediaNotFoundException::class);

        $this->assertNull($this->singleMediaSelection->getContentData($property));
    }

    public function testPreResolveEmpty()
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(null);

        $this->mediaReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->singleMediaSelection->preResolve($property);
    }

    public function testPreResolve()
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);

        $this->mediaReferenceStore->add(11)->shouldBeCalled();

        $this->singleMediaSelection->preResolve($property);
    }
}

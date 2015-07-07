<?php

namespace Sulu\Component\Content\Compat\Structure;

use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

class StructureBridgeTest extends \PHPUnit_Framework_TestCase
{
    public function testCopyFrom()
    {
        $titleProperty = $this->prophesize(PropertyMetadata::class);
        $titleProperty->getName()->willReturn('title');
        $imagesProperty = $this->prophesize(PropertyMetadata::class);
        $imagesProperty->getName()->willReturn('images');

        $title = $this->prophesize(Property::class);
        $title->getName()->willReturn('title');
        $images = $this->prophesize(Property::class);
        $images->getName()->willReturn('images');

        $title->setValue('test-title')->shouldBeCalled();
        $images->setValue(array('ids' => array(1, 2, 3, 4)))->shouldBeCalled();

        $document = $this->prophesize(StructureBehavior::class);
        $metadata = $this->prophesize(StructureMetadata::class);
        $copyFromStructure = $this->prophesize(StructureBridge::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $reveal = $copyFromStructure->reveal();
        $property = new \ReflectionProperty(get_class($reveal), 'document');
        $property->setAccessible(true);
        $property->setValue($reveal, $document->reveal());

        $metadata->getProperties()->willReturn(array($titleProperty, $imagesProperty));
        $metadata->hasProperty('title')->willReturn(true);
        $metadata->getProperty('title')->willReturn($title->reveal());
        $metadata->hasProperty('images')->willReturn(true);
        $metadata->getProperty('images')->willReturn($images->reveal());

        $copyFromStructure->getDocument()->willReturn($document);
        $copyFromStructure->hasProperty('title')->willReturn(true);
        $copyFromStructure->hasProperty('images')->willReturn(true);
        $copyFromStructure->getPropertyValue('title')->willReturn('test-title');
        $copyFromStructure->getPropertyValue('images')->willReturn(array('ids' => array(1, 2, 3, 4)));

        $propertyFactory->createProperty(
            Argument::type(PropertyMetadata::class),
            Argument::type(StructureBridge::class)
        )->will(
            function ($args) use ($title, $images) {
                if ($args[0]->getName() === 'title') {
                    return $title->reveal();
                } elseif ($args[0]->getName() === 'images') {
                    return $images->reveal();
                }
            }
        );
        $structure = new StructureBridge(
            $metadata->reveal(),
            $inspector->reveal(),
            $propertyFactory->reveal()
        );

        $structure->copyFrom($copyFromStructure->reveal());
    }
}

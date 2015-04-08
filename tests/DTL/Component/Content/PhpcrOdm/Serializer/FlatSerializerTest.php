<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\PhpcrOdm\Serializer;

use Prophecy\PhpUnit\ProphecyTestCase;
use DTL\Component\Content\Structure\Structure;
use DTL\Component\Content\Structure\Property;
use DTL\Component\Content\PhpcrOdm\Serializer\PropertyNameEncoder;
use DTL\Component\Content\PhpcrOdm\DocumentNodeHelper;
use DTL\Component\Content\PhpcrOdm\NamespaceRoleRegistry;
use DTL\Component\Content\Structure\Factory\StructureFactory;
use Doctrine\ODM\PHPCR\DocumentManager;
use PHPCR\NodeInterface;
use DTL\Component\Content\Document\DocumentInterface;

class FlatSerializerTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->structureFactory = $this->prophesize(StructureFactory::class);
        $this->structure = new Structure();
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->document = $this->prophesize(DocumentInterface::class);
        $this->document->getDocumentType()->willReturn('page');
        $this->node = $this->prophesize(NodeInterface::class);
        $this->helper = new DocumentNodeHelper(new NamespaceRoleRegistry(array(
            'localized-content' => 'i18n',
            'content' => 'cont',
        )));

        $this->structureFactory->getStructure('page', 'test', true)->willReturn(
            $this->structure
        );

        $this->serializer = new FlatSerializer(
            $this->structureFactory->reveal(),
            $this->helper,
            $this->documentManager->reveal()
        );
    }

    public function provideSerializer()
    {
        return array(
            array(
                'de',
                array(
                    'some_number' => 1234,
                    'animals' => array(
                        'title' => 'Smart content',
                        'sort_method' => 'asc',
                    ),
                    'options' => array(
                        'numbers' => array(
                            'two', 'three'
                        ),
                        'foo' => array(
                            'bar' => 'baz',
                            'boo' => 'bog',
                        ),
                    ),
                ),
                array(
                    'some_number' => array(
                        'localized' => false,
                    ),
                    'animals' => array(
                        'localized' => true,
                    ),
                    'options' => array(
                        'localized' => false,
                    ),
                ),
                array(
                    'cont:some_number' => 1234,
                    'i18n:de-animals' . FlatSerializer::ARRAY_DELIM . 'title' => 'Smart content',
                    'i18n:de-animals' . FlatSerializer::ARRAY_DELIM . 'sort_method' => 'asc',
                    'cont:options' . FlatSerializer::ARRAY_DELIM . 'numbers' . FlatSerializer::ARRAY_DELIM . '0' => 'two',
                    'cont:options' . FlatSerializer::ARRAY_DELIM . 'numbers' . FlatSerializer::ARRAY_DELIM . '1' => 'three',
                    'cont:options' . FlatSerializer::ARRAY_DELIM . 'foo' . FlatSerializer::ARRAY_DELIM . 'bar' => 'baz',
                    'cont:options' . FlatSerializer::ARRAY_DELIM . 'foo' . FlatSerializer::ARRAY_DELIM . 'boo' => 'bog',
                ),
            ),
        );
    }

    /**
     * @param string $locale Locale to use for the document
     * @param array $data Content data which the document will return
     * @param array $propertyMetadatas Metadata for the structure properties
     * @param array $expectedResult Expected result
     *
     * @dataProvider provideSerializer
     */
    public function testSerialize($locale, $data, $propertyMetadatas, $expectedResult)
    {
        $this->document->getStructureType()->willReturn('test');
        $this->document->getPhpcrNode()->willReturn($this->node);
        $this->document->getLocale()->willReturn($locale);
        $this->document->getContent()->willReturn($data);

        $this->loadMetadata($propertyMetadatas);

        foreach ($expectedResult as $propName => $propValue) {
            $this->node->setProperty($propName, $propValue)->shouldBeCalled();
        }

        $this->serializer->serialize($this->document->reveal());
    }

    /**
     * Note that this test uses the same data as testSerialize but swaps the data
     * and expectedResult.
     *
     * @param mixed $locale
     * @param mixed $expectedResult
     * @param mixed $propertyMetadatas
     * @param mixed $data
     *
     * @dataProvider provideSerializer
     */
    public function testDeserialize($locale, $expectedResult, $propertyMetadatas, $data)
    {
        $this->document->getStructureType()->willReturn('test');
        $this->document->getPhpcrNode()->willReturn($this->node);
        $this->document->getLocale()->willReturn($locale);

        $this->loadMetadata($propertyMetadatas);

        $nodeProperties = array();
        foreach ($data as $propName => $propValue) {
            $nodeProperty = $this->prophesize('PHPCR\PropertyInterface');
            $nodeProperty->getValue()->willReturn($propValue);
            $nodeProperties[$propName] = $nodeProperty->reveal();
        }

        $this->node->getProperties()->willReturn($nodeProperties);

        $res = $this->serializer->deserialize($this->document->reveal());

        $this->assertEquals($expectedResult, $res);
    }

    private function loadMetadata($propertyMetadatas)
    {
        foreach ($propertyMetadatas as $propertyName => $propertyMetadata) {
            $property = new Property($propertyName);
            foreach ($propertyMetadata as $attrName => $attrValue) {
                $property->$attrName = $attrValue;
            }
            $this->structure->addChild($property);
        }
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

class MediaSelectionContentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType
     */
    private $mediaSelection;

    /**
     * @var \Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface
     */
    private $mediaManager;

    protected function setUp()
    {
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);

        $this->mediaSelection = new MediaSelectionContentType(
            $this->mediaManager->reveal(), 'SuluMediaBundle:Template:image-selection.html.twig'
        );
    }

    public function testTemplate()
    {
        $this->assertEquals('SuluMediaBundle:Template:image-selection.html.twig', $this->mediaSelection->getTemplate());
    }

    public function testWrite()
    {
        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setProperty']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                ]
            )
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                ]
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                ]
            )
        );

        $this->mediaSelection->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testWriteWithPassedContainer()
    {
        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setProperty']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                    'data' => ['data1', 'data2'],
                ]
            )
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                ]
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                ]
            )
        );

        $this->mediaSelection->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testRead()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setValue', 'getParams']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    [
                        'property',
                        '{}',
                        $config,
                    ],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with(json_decode($config, true))->will(
            $this->returnValue(null)
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                ]
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadWithType()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setValue', 'getParams']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    [
                        'property',
                        '{}',
                        $config,
                    ],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with(json_decode($config, true))->will(
            $this->returnValue(null)
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                    'types' => 'document',
                ]
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadWithMultipleTypes()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setValue', 'getParams']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    [
                        'property',
                        '{}',
                        $config,
                    ],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with(json_decode($config, true))->will(
            $this->returnValue(null)
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                    'types' => 'document,image',
                ]
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }
}

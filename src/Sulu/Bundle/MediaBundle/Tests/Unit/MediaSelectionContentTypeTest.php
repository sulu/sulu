<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types;

//FIXME remove on update to phpunit 3.8, caused by https://github.com/sebastianbergmann/phpunit/issues/604
use JMS\Serializer\Serializer;
use PHPUnit_Framework_TestCase;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;

interface NodeInterface extends \PHPCR\NodeInterface, \Iterator
{
}

class MediaSelectionContentTypeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType
     */
    private $mediaSelection;

    /**
     * @var \Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var Serializer
     */
    private $serializer ;

    protected function setUp()
    {
        $this->mediaManager = $this->getMock('\Sulu\Bundle\MeediaBundle\Media\Manager\MediaManagerInterface');
        $this->serializer = $this->getMock('JMS\Serializer\Serializer', array(), array(), '', false);

        $this->mediaSelection = new \Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType(
            $this->mediaManager, $this->serializer, 'SuluMediaBundle:Template:image-selection.html.twig'
        );
    }

    public function testTemplate()
    {
        $this->assertEquals('SuluMediaBundle:Template:image-selection.html.twig', $this->mediaSelection->getTemplate());
    }

    public function testWrite()
    {
        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setProperty')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getValue', 'getParams')
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                array(
                    'ids' => array(1, 2, 3, 4),
                    'displayOption' => 'right',
                    'config' => array('conf1' => 1, 'conf2' => 2)
                )
            )
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                )
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                array(
                    'ids' => array(1, 2, 3, 4),
                    'displayOption' => 'right',
                    'config' => array('conf1' => 1, 'conf2' => 2)
                )
            )
        );

        $this->mediaSelection->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testWriteWithPassedContainer()
    {
        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setProperty')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getValue', 'getParams')
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                array(
                    'ids' => array(1, 2, 3, 4),
                    'displayOption' => 'right',
                    'config' => array('conf1' => 1, 'conf2' => 2),
                    'data' => array('data1', 'data2')
                )
            )
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                )
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                array(
                    'ids' => array(1, 2, 3, 4),
                    'displayOption' => 'right',
                    'config' => array('conf1' => 1, 'conf2' => 2)
                )
            )
        );

        $this->mediaSelection->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testRead()
    {
        $container = new MediaSelectionContainer(
            array('conf1' => 1, 'conf2' => 2),
            'right',
            array(1, 2, 3, 4),
            'en',
            null,
            $this->mediaManager,
            $this->serializer
        );

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}'
                    )
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with($container)->will($this->returnValue(null));

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                )
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadPreview()
    {
        $container = new MediaSelectionContainer(
            array('conf1' => 1, 'conf2' => 2),
            'right',
            array(1, 2, 3, 4),
            'en',
            null,
            $this->mediaManager,
            $this->serializer
        );

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}'
                    )
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with($container)->will($this->returnValue(null));

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                )
            )
        );

        $this->mediaSelection->readForPreview(
            array(
                'config' => array('conf1' => 1, 'conf2' => 2),
                'displayOption' => 'right',
                'ids' => array(1, 2, 3, 4)
            ),
            $property,
            'test',
            'en',
            's'
        );
    }

    public function testReadWithType()
    {
        $container = new MediaSelectionContainer(
            array('conf1' => 1, 'conf2' => 2),
            'right',
            array(1, 2, 3, 4),
            'en',
            'document',
            $this->mediaManager,
            $this->serializer
        );

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}'
                    )
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with($container)->will($this->returnValue(null));

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                    'types' => 'document'
                )
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadPreviewWithType()
    {
        $container = new MediaSelectionContainer(
            array('conf1' => 1, 'conf2' => 2),
            'right',
            array(1, 2, 3, 4),
            'en',
            'document',
            $this->mediaManager,
            $this->serializer
        );

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}'
                    )
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with($container)->will($this->returnValue(null));

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                    'types' => 'document'
                )
            )
        );

        $this->mediaSelection->readForPreview(
            array(
                'config' => array('conf1' => 1, 'conf2' => 2),
                'displayOption' => 'right',
                'ids' => array(1, 2, 3, 4)
            ),
            $property,
            'test',
            'en',
            's'
        );
    }

    public function testReadWithMultipleTypes()
    {
        $container = new MediaSelectionContainer(
            array('conf1' => 1, 'conf2' => 2),
            'right',
            array(1, 2, 3, 4),
            'en',
            'document,image',
            $this->mediaManager,
            $this->serializer
        );

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}'
                    )
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with($container)->will($this->returnValue(null));

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                    'types' => 'document,image'
                )
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadPreviewMultipleTypes()
    {
        $container = new MediaSelectionContainer(
            array('conf1' => 1, 'conf2' => 2),
            'right',
            array(1, 2, 3, 4),
            'en',
            'document,image',
            $this->mediaManager,
            $this->serializer
        );

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}'
                    )
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with($container)->will($this->returnValue(null));

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                    'types' => 'document,image'
                )
            )
        );

        $this->mediaSelection->readForPreview(
            array(
                'config' => array('conf1' => 1, 'conf2' => 2),
                'displayOption' => 'right',
                'ids' => array(1, 2, 3, 4)
            ),
            $property,
            'test',
            'en',
            's'
        );
    }
}

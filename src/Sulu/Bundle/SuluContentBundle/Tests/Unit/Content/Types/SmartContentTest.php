<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types;

use Sulu\Bundle\ContentBundle\Content\SmartContentContainer;
use Sulu\Bundle\ContentBundle\Content\Types\SmartContent;
use Sulu\Bundle\ContentBundle\Repository\NodeRepository;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;

//FIXME remove on update to phpunit 3.8, caused by https://github.com/sebastianbergmann/phpunit/issues/604
interface NodeInterface extends \PHPCR\NodeInterface, \Iterator
{
}

class SmartContentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SmartContent
     */
    private $smartContent;

    /**
     * @var NodeRepository
     */
    private $nodeRepository;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    public function setUp()
    {
        $this->nodeRepository = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Repository\NodeRepository',
            array(),
            '',
            false
        );

        $this->tagManager = $this->getMockForAbstractClass(
            'Sulu\Bundle\TagBundle\Tag\TagManagerInterface',
            array(),
            '',
            false,
            true,
            true,
            array('resolveTagIds', 'resolveTagNames')
        );

        $serializer = $this->getMock('JMS\Serializer\Serializer', array(), array(), '', false);

        $this->smartContent = new SmartContent(
            $this->nodeRepository,
            $this->tagManager,
            $serializer,
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

        $this->tagManager->expects($this->any())->method('resolveTagIds')->will(
            $this->returnValueMap(
                array(
                    array(array(1, 2), array('Tag1', 'Tag2'))
                )
            )
        );

        $this->tagManager->expects($this->any())->method('resolveTagName')->will(
            $this->returnValueMap(
                array(
                    array(array('Tag1', 'Tag2'), array(1, 2))
                )
            )
        );
    }

    public function testTemplate()
    {
        $this->assertEquals(
            'SuluContentBundle:Template:content-types/smart_content.html.twig',
            $this->smartContent->getTemplate()
        );
    }

    public function testWrite()
    {
        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
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
            array('getValue')
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                array(
                    'dataSource' => array(
                        'home/products'
                    ),
                    'sortBy' => array(
                        'published'
                    )
                )
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                array(
                    'dataSource' => array(
                        'home/products'
                    ),
                    'sortBy' => array(
                        'published'
                    )
                )
            )
        );

        $this->smartContent->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testWriteWithPassedContainer()
    {
        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
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
            array('getValue')
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                array(
                    'config' => array(
                        'dataSource' => array(
                            'home/products'
                        ),
                        'sortBy' => array(
                            'published'
                        )
                    )
                )
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                array(
                    'dataSource' => array(
                        'home/products'
                    ),
                    'sortBy' => array(
                        'published'
                    )
                )
            )
        );

        $this->smartContent->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testRead()
    {
        $smartContentContainer = new SmartContentContainer($this->nodeRepository, $this->tagManager, 'test', 'en', 's');
        $smartContentContainer->setConfig(
            array(
                'tags' => array('Tag1', 'Tag2'),
                'limitResult' => '2'
            )
        );

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
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
            array('setValue')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array('property', '{}', '{"tags":[1,2],"limitResult":"2"}')
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->exactly(1))->method('setValue')->with($smartContentContainer);

        $this->smartContent->read($node, $property, 'test', 'en', 's');
    }

    public function testReadPreview()
    {

        $smartContentContainerPreview = new SmartContentContainer($this->nodeRepository, $this->tagManager, 'test', 'en', 's', true);
        $smartContentContainerPreview->setConfig(
            array(
                'tags' => array('Tag1', 'Tag2'),
                'limitResult' => '2'
            )
        );

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
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
            array('setValue')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array('property', '{}', '{"tags":[1,2],"limitResult":"2"}')
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->exactly(1))->method('setValue')->with($smartContentContainerPreview);

        $this->smartContent->readForPreview(
            array('tags' => array('Tag1', 'Tag2'), 'limitResult' => 2),
            $property,
            'test',
            'en',
            's'
        );
    }
}

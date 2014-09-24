<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Block;

use PHPCR\NodeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;

class BlockContentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BlockContentType
     */
    private $blockContentType;

    /**
     * @var PropertyInterface
     */
    private $blockProperty;

    /**
     * @var PropertyInterface
     */
    private $subBlockProperty;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeValueMap;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    protected function setUp()
    {
        parent::setUp();

        $this->contentTypeManager = $this->getMock(
            'Sulu\Component\Content\ContentTypeManager',
            array('get'),
            array(),
            '',
            false
        );

        $this->blockContentType = new BlockContentType($this->contentTypeManager, 'not in use', 'sulu_locale:');

        $this->contentTypeValueMap = array(
            array('text_line', new TextLine('not in use')),
            array('text_area', new TextArea('not in use')),
            array('block', $this->blockContentType)
        );

        $this->contentTypeManager
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($this->contentTypeValueMap));
    }

    protected function prepareSingleBlockProperty()
    {
        $this->blockProperty = new BlockProperty('block1', '', 'default', false, true);
        $type1 = new BlockPropertyType('type1', '');
        $type1->addChild(new Property('title', '', 'text_line', false, true));
        $type1->addChild(new Property('article', '', 'text_area', false, true));
        $this->blockProperty->addType($type1);

        $this->subBlockProperty = new BlockProperty('sub-block', '', 'default', false, true);
        $subType1 = new BlockPropertyType('subType1', '');
        $subType1->addChild(new Property('title', '', 'text_line', false, true));
        $subType1->addChild(new Property('article', '', 'text_area', false, true));
        $this->subBlockProperty->addType($subType1);
        $type1->addChild($this->subBlockProperty);

        $type2 = new BlockPropertyType('type2', '');
        $type2->addChild(new Property('name', '', 'text_line', false, true));

        $this->blockProperty->addType($type2);
    }

    protected function prepareMultipleBlockProperty()
    {
        $this->blockProperty = new BlockProperty('block1', '', 'default', false, true, 1, 10);
        $type1 = new BlockPropertyType('type1', '');
        $type1->addChild(new Property('title', '', 'text_line', false, true));
        $type1->addChild(new Property('article', '', 'text_area'));
        $this->blockProperty->addType($type1);

        $this->subBlockProperty = new BlockProperty('sub-block', '', 'default', false, true);
        $subType1 = new BlockPropertyType('subType1', '');
        $subType1->addChild(new Property('title', '', 'text_line', false, true));
        $subType1->addChild(new Property('article', '', 'text_area', false, true));
        $this->subBlockProperty->addType($subType1);
        $type1->addChild($this->subBlockProperty);

        $type2 = new BlockPropertyType('type2', '');
        $type2->addChild(new Property('name', '', 'text_line', false, true));

        $this->blockProperty->addType($type2);
    }

    public function testRead()
    {
        $this->prepareSingleBlockProperty();

        $this->node = $this->getMock('\Jackalope\Node', array('getPropertyValue', 'hasProperty'), array(), '', false);
        $data = array(
            'type' => 'type1',
            'title' => 'Test-Title',
            'article' => 'Test-Article',
            'sub-block' => array(
                'type' => 'subType1',
                'title' => 'Test-Sub-Title',
                'article' => 'Test-Sub-Article'
            )
        );

        $valueMap = array(
            array('sulu_locale:de-block1-length', null, 1),
            array('sulu_locale:de-block1-type#0', null, 'type1'),
            array('sulu_locale:de-block1-title#0', null, $data['title']),
            array('sulu_locale:de-block1-article#0', null, $data['article']),
            array('sulu_locale:de-block1-sub-block#0-length', null, 1),
            array('sulu_locale:de-block1-sub-block#0-type#0', null, 'subType1'),
            array('sulu_locale:de-block1-sub-block#0-title#0', null, $data['sub-block']['title']),
            array('sulu_locale:de-block1-sub-block#0-article#0', null, $data['sub-block']['article'])
        );
        $this->node
            ->expects($this->any())
            ->method('getPropertyValue')
            ->will($this->returnValueMap($valueMap));
        $this->node
            ->expects($this->any())
            ->method('hasProperty')
            ->will($this->returnValue(true));

        $this->blockContentType->read(
            $this->node,
            new TranslatedProperty($this->blockProperty, 'de', 'sulu_locale'),
            'default',
            'de',
            ''
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testWrite()
    {
        $this->prepareSingleBlockProperty();

        $this->node = $this->getMock('\Jackalope\Node', array('setProperty'), array(), '', false);
        $result = array();
        $this->node
            ->expects($this->any())
            ->method('setProperty')
            ->will(
                $this->returnCallback(
                    function () use (&$result) {
                        $args = func_get_args();
                        $result[$args[0]] = $args[1];
                    }
                )
            );

        $data = array(
            'type' => 'type1',
            'title' => 'Test-Title',
            'article' => 'Test-Article',
            'sub-block' => array(
                'type' => 'subType1',
                'title' => 'Test-Title',
                'article' => 'Test-Article'
            )
        );
        $this->blockProperty->setValue($data);

        $this->blockContentType->write(
            $this->node,
            new TranslatedProperty($this->blockProperty, 'de', 'sulu_locale'),
            1,
            'default',
            'de',
            ''
        );

        // check repository node
        $this->assertEquals(
            array(
                'sulu_locale:de-block1-length' => 1,
                'sulu_locale:de-block1-type#0' => $data['type'],
                'sulu_locale:de-block1-title#0' => $data['title'],
                'sulu_locale:de-block1-article#0' => $data['article'],
                'sulu_locale:de-block1-sub-block#0-length' => 1,
                'sulu_locale:de-block1-sub-block#0-type#0' => $data['sub-block']['type'],
                'sulu_locale:de-block1-sub-block#0-title#0' => $data['sub-block']['title'],
                'sulu_locale:de-block1-sub-block#0-article#0' => $data['sub-block']['article']
            ),
            $result
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testReadMultiple()
    {
        $this->prepareMultipleBlockProperty();

        $this->node = $this->getMock('\Jackalope\Node', array('getPropertyValue', 'hasProperty'), array(), '', false);
        $data = array(
            array(
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => array(
                    'Test-Article-1-1',
                    'Test-Article-1-2'
                ),
                'sub-block' => array(
                    'type' => 'subType1',
                    'title' => 'Test-Title-Sub-1',
                    'article' => 'Test-Article-Sub-1'
                )
            ),
            array(
                'type' => 'type1',
                'title' => 'Test-Title-2',
                'article' => 'Test-Article-2',
                'sub-block' => array(
                    'type' => 'subType1',
                    'title' => 'Test-Title-Sub-2',
                    'article' => 'Test-Article-Sub-2'
                )
            )
        );

        $valueMap = array(
            array('sulu_locale:de-block1-length', null, 2),

            array('sulu_locale:de-block1-type#0', null, 'type1'),
            array('sulu_locale:de-block1-title#0', null, $data[0]['title']),
            array('sulu_locale:de-block1-article#0', null, $data[0]['article']),
            array('sulu_locale:de-block1-sub-block#0-length', null, 1),
            array('sulu_locale:de-block1-sub-block#0-type#0', null, 'subType1'),
            array('sulu_locale:de-block1-sub-block#0-title#0', null, $data[0]['sub-block']['title']),
            array('sulu_locale:de-block1-sub-block#0-article#0', null, $data[0]['sub-block']['article']),

            array('sulu_locale:de-block1-type#1', null, 'type1'),
            array('sulu_locale:de-block1-title#1', null, $data[1]['title']),
            array('sulu_locale:de-block1-article#1', null, $data[1]['article']),
            array('sulu_locale:de-block1-sub-block#1-length', null, 1),
            array('sulu_locale:de-block1-sub-block#1-type#0', null, 'subType1'),
            array('sulu_locale:de-block1-sub-block#1-title#0', null, $data[1]['sub-block']['title']),
            array('sulu_locale:de-block1-sub-block#1-article#0', null, $data[1]['sub-block']['article'])
        );

        $this->node
            ->expects($this->any())
            ->method('getPropertyValue')
            ->will($this->returnValueMap($valueMap));
        $this->node
            ->expects($this->any())
            ->method('hasProperty')
            ->will($this->returnValue(true));

        $this->blockContentType->read(
            $this->node,
            new TranslatedProperty($this->blockProperty, 'de', 'sulu_locale'),
            'default',
            'de',
            ''
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testWriteMultiple()
    {
        $this->prepareMultipleBlockProperty();

        $this->node = $this->getMock('\Jackalope\Node', array('setProperty'), array(), '', false);
        $result = array();
        $this->node
            ->expects($this->any())
            ->method('setProperty')
            ->will(
                $this->returnCallback(
                    function () use (&$result) {
                        $args = func_get_args();
                        $result[$args[0]] = $args[1];
                    }
                )
            );

        $data = array(
            array(
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => array(
                    'Test-Article-1-1',
                    'Test-Article-1-2'
                ),
                'sub-block' => array(
                    'type' => 'subType1',
                    'title' => 'Test-Title-Sub-1',
                    'article' => 'Test-Article-Sub-1'
                )
            ),
            array(
                'type' => 'type1',
                'title' => 'Test-Title-2',
                'article' => 'Test-Article-2',
                'sub-block' => array(
                    'type' => 'subType1',
                    'title' => 'Test-Title-Sub-2',
                    'article' => 'Test-Article-Sub-2'
                )
            )
        );
        $this->blockProperty->setValue($data);

        $this->blockContentType->write(
            $this->node,
            new TranslatedProperty($this->blockProperty, 'de', 'sulu_locale'),
            1,
            'default',
            'de',
            ''
        );

        // check repository node
        $this->assertEquals(
            array(
                'sulu_locale:de-block1-length' => 2,
                'sulu_locale:de-block1-type#0' => 'type1',
                'sulu_locale:de-block1-title#0' => $data[0]['title'],
                'sulu_locale:de-block1-article#0' => $data[0]['article'],
                'sulu_locale:de-block1-sub-block#0-length' => 1,
                'sulu_locale:de-block1-sub-block#0-type#0' => 'subType1',
                'sulu_locale:de-block1-sub-block#0-title#0' => $data[0]['sub-block']['title'],
                'sulu_locale:de-block1-sub-block#0-article#0' => $data[0]['sub-block']['article'],
                'sulu_locale:de-block1-type#1' => 'type1',
                'sulu_locale:de-block1-title#1' => $data[1]['title'],
                'sulu_locale:de-block1-article#1' => $data[1]['article'],
                'sulu_locale:de-block1-sub-block#1-length' => 1,
                'sulu_locale:de-block1-sub-block#1-type#0' => 'subType1',
                'sulu_locale:de-block1-sub-block#1-title#0' => $data[1]['sub-block']['title'],
                'sulu_locale:de-block1-sub-block#1-article#0' => $data[1]['sub-block']['article']
            ),
            $result
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testReadMultipleDifferentTypes()
    {
        $this->prepareMultipleBlockProperty();

        $this->node = $this->getMock('\Jackalope\Node', array('getPropertyValue', 'hasProperty'), array(), '', false);
        $data = array(
            array(
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => array(
                    'Test-Article-1-1',
                    'Test-Article-1-2'
                ),
                'sub-block' => array(
                    'type' => 'subType1',
                    'title' => 'Test-Title-Sub-1',
                    'article' => 'Test-Article-Sub-1'
                )
            ),
            array(
                'type' => 'type2',
                'name' => 'Test-Name-2',
            )
        );

        $valueMap = array(
            array('sulu_locale:de-block1-length', null, 2),

            array('sulu_locale:de-block1-type#0', null, 'type1'),
            array('sulu_locale:de-block1-title#0', null, $data[0]['title']),
            array('sulu_locale:de-block1-article#0', null, $data[0]['article']),
            array('sulu_locale:de-block1-sub-block#0-length', null, 1),
            array('sulu_locale:de-block1-sub-block#0-type#0', null, 'subType1'),
            array('sulu_locale:de-block1-sub-block#0-title#0', null, $data[0]['sub-block']['title']),
            array('sulu_locale:de-block1-sub-block#0-article#0', null, $data[0]['sub-block']['article']),

            array('sulu_locale:de-block1-type#1', null, 'type2'),
            array('sulu_locale:de-block1-name#1', null, $data[1]['name']),
        );

        $this->node
            ->expects($this->any())
            ->method('getPropertyValue')
            ->will($this->returnValueMap($valueMap));
        $this->node
            ->expects($this->any())
            ->method('hasProperty')
            ->will($this->returnValue(true));

        $this->blockContentType->read(
            $this->node,
            new TranslatedProperty($this->blockProperty, 'de', 'sulu_locale'),
            'default',
            'de',
            ''
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testWriteMultipleDifferentTypes()
    {
        $this->prepareMultipleBlockProperty();

        $this->node = $this->getMock('\Jackalope\Node', array('setProperty'), array(), '', false);
        $result = array();
        $this->node
            ->expects($this->any())
            ->method('setProperty')
            ->will(
                $this->returnCallback(
                    function () use (&$result) {
                        $args = func_get_args();
                        $result[$args[0]] = $args[1];
                    }
                )
            );

        $data = array(
            array(
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => array(
                    'Test-Article-1-1',
                    'Test-Article-1-2'
                ),
                'sub-block' => array(
                    'type' => 'subType1',
                    'title' => 'Test-Title-Sub-1',
                    'article' => 'Test-Article-Sub-1'
                )
            ),
            array(
                'type' => 'type2',
                'name' => 'Test-Name-2',
            )
        );
        $this->blockProperty->setValue($data);

        $this->blockContentType->write(
            $this->node,
            new TranslatedProperty($this->blockProperty, 'de', 'sulu_locale'),
            1,
            'default',
            'de',
            ''
        );

        // check repository node
        $this->assertEquals(
            array(
                'sulu_locale:de-block1-length' => 2,
                'sulu_locale:de-block1-type#0' => 'type1',
                'sulu_locale:de-block1-title#0' => $data[0]['title'],
                'sulu_locale:de-block1-article#0' => $data[0]['article'],
                'sulu_locale:de-block1-sub-block#0-length' => 1,
                'sulu_locale:de-block1-sub-block#0-type#0' => 'subType1',
                'sulu_locale:de-block1-sub-block#0-title#0' => $data[0]['sub-block']['title'],
                'sulu_locale:de-block1-sub-block#0-article#0' => $data[0]['sub-block']['article'],
                'sulu_locale:de-block1-type#1' => 'type2',
                'sulu_locale:de-block1-name#1' => $data[1]['name'],
            ),
            $result
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testGetContentData()
    {
        $this->prepareSingleBlockProperty();

        $data = array(
            array(
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => array(
                    'Test-Article-1-1',
                    'Test-Article-1-2'
                ),
                'sub-block' => array(
                    'type' => 'subType1',
                    'title' => 'Test-Title-Sub-1',
                    'article' => 'Test-Article-Sub-1'
                )
            ),
            array(
                'type' => 'type2',
                'name' => 'Test-Name-2',
            )
        );
        $this->blockProperty->setValue($data);

        $result = $this->blockContentType->getContentData($this->blockProperty);

        $this->assertEquals($data, $result);
    }
}

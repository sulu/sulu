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

        $this->blockContentType = new BlockContentType($this->contentTypeManager);

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
        $this->blockProperty = new BlockProperty('block1');
        $this->blockProperty->addChild(new Property('title', 'text_line'));
        $this->blockProperty->addChild(new Property('article', 'text_area'));

        $this->subBlockProperty = new BlockProperty('sub-block');
        $this->subBlockProperty->addChild(new Property('title', 'text_line'));
        $this->subBlockProperty->addChild(new Property('article', 'text_area'));

        $this->blockProperty->addChild($this->subBlockProperty);
    }

    protected function prepareMultipleBlockProperty()
    {
        $this->blockProperty = new BlockProperty('block1', false, false, 1, 10);
        $this->blockProperty->addChild(new Property('title', 'text_line'));
        $this->blockProperty->addChild(new Property('article', 'text_area'));

        $this->subBlockProperty = new BlockProperty('sub-block');
        $this->subBlockProperty->addChild(new Property('title', 'text_line'));
        $this->subBlockProperty->addChild(new Property('article', 'text_area'));

        $this->blockProperty->addChild($this->subBlockProperty);
    }

    public function testRead()
    {
        $this->prepareSingleBlockProperty();

        $this->node = $this->getMock('\Jackalope\Node', array('getPropertyValue', 'hasProperty'), array(), '', false);
        $data = array(
            'title' => 'Test-Title',
            'article' => 'Test-Article',
            'sub-block' => array(
                'title' => 'Test-Sub-Title',
                'article' => 'Test-Sub-Article'
            )
        );

        $valueMap = array(
            array('sulu_locale:de-block1-title', null, $data['title']),
            array('sulu_locale:de-block1-article', null, $data['article']),
            array('sulu_locale:de-block1-sub-block-title', null, $data['sub-block']['title']),
            array('sulu_locale:de-block1-sub-block-article', null, $data['sub-block']['article'])
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
            'title' => 'Test-Title',
            'article' => 'Test-Article',
            'sub-block' => array(
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
                'sulu_locale:de-block1-title' => $data['title'],
                'sulu_locale:de-block1-article' => $data['article'],
                'sulu_locale:de-block1-sub-block-title' => $data['sub-block']['title'],
                'sulu_locale:de-block1-sub-block-article' => $data['sub-block']['article']
            ),
            $result
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
                'title' => 'Test-Title-1',
                'article' => array(
                    'Test-Article-1-1',
                    'Test-Article-1-2'
                ),
                'sub-block' => array(
                    'title' => 'Test-Title-Sub-1',
                    'article' => 'Test-Article-Sub-1'
                )
            ),
            array(
                'title' => 'Test-Title-2',
                'article' => 'Test-Article-2',
                'sub-block' => array(
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
                'sulu_locale:de-block1-title' => array(
                    $data[0]['title'],
                    $data[1]['title']
                ),
                'sulu_locale:de-block1-article' => array(
                    $data[0]['article'],
                    $data[1]['article']
                ),
                'sulu_locale:de-block1-sub-block-title' => array(
                    $data[0]['sub-block']['title'],
                    $data[1]['sub-block']['title']
                ),
                'sulu_locale:de-block1-sub-block-article' => array(
                    $data[0]['sub-block']['article'],
                    $data[1]['sub-block']['article']
                )
            ),
            $result
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }
}

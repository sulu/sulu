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

        $this->blockProperty = new BlockProperty('block1');
        $this->blockProperty->addChild(new Property('title', 'text_line'));
        $this->blockProperty->addChild(new Property('article', 'text_area'));

        $this->subBlockProperty = new BlockProperty('sub-block');
        $this->subBlockProperty->addChild(new Property('title', 'text_line'));
        $this->subBlockProperty->addChild(new Property('article', 'text_area'));

        $this->blockProperty->addChild($this->subBlockProperty);

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

    public function testRead()
    {
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
}

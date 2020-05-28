<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Block;

use Jackalope\Node;
use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\PageBundle\Content\Types\SinglePageSelection;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Content\Compat\Block\BlockProperty;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Types\BlockContentType;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;

class BlockContentTypeTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->contentTypeManager = $this->prophesize(ContentTypeManager::class);
        $this->blockContentType = new BlockContentType($this->contentTypeManager->reveal(), 'not in use', 'i18n:');

        $this->contentTypeValueMap = [
            ['text_line', new TextLine('not in use')],
            ['text_area', new TextArea('not in use')],
            ['internal_link', new SinglePageSelection(new ReferenceStore(), 'not in use')],
            ['block', $this->blockContentType],
        ];

        $this->contentTypeManager->get('text_line')->willReturn(new TextLine('not in use'));
        $this->contentTypeManager->get('text_area')->willReturn(new TextArea('not in use'));
        $this->contentTypeManager->get('internal_link')->willReturn(new SinglePageSelection(new ReferenceStore(), 'not in use'));
        $this->contentTypeManager->get('block')->willReturn($this->blockContentType);
    }

    protected function prepareSingleBlockProperty()
    {
        $this->blockProperty = new BlockProperty('block1', '', 'type1', false, true, 999, 1);
        $type1 = new BlockPropertyType('type1', '');
        $type1->addChild(new Property('title', '', 'text_line', false, true));
        $type1->addChild(new Property('article', '', 'text_area', false, true));
        $this->blockProperty->addType($type1);

        $this->subBlockProperty = new BlockProperty('sub-block', '', 'subType1', false, true, 999, 1);
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
        $this->blockProperty = new BlockProperty('block1', '', 'type1', false, true, 10, 1);
        $type1 = new BlockPropertyType('type1', '');
        $type1->addChild(new Property('title', '', 'text_line', false, true));
        $type1->addChild(new Property('article', '', 'text_area'));
        $this->blockProperty->addType($type1);

        $this->subBlockProperty = new BlockProperty('sub-block', '', 'subType1', false, true);
        $subType1 = new BlockPropertyType('subType1', '');
        $subType1->addChild(new Property('title', '', 'text_line', false, true));
        $subType1->addChild(new Property('article', '', 'text_area', false, true));

        $this->subBlockProperty->addType($subType1);
        $type1->addChild($this->subBlockProperty);

        $type2 = new BlockPropertyType('type2', '');
        $type2->addChild(new Property('name', '', 'text_line', false, true));

        $this->blockProperty->addType($type2);
    }

    protected function prepareMultipleBlockWithLinksProperty()
    {
        $this->blockProperty = new BlockProperty('block1', '', 'type1', false, true, 10, 1);
        $type1 = new BlockPropertyType('type1', '');
        $type1->addChild(new Property('title', '', 'text_line', false, true));
        $type1->addChild(new Property('article', '', 'text_area'));
        $this->blockProperty->addType($type1);

        $this->subBlockProperty = new BlockProperty('sub-block', '', 'subType1', false, true);
        $subType1 = new BlockPropertyType('subType1', '');
        $subType1->addChild(new Property('title', '', 'text_line', false, true));
        $subType1->addChild(new Property('article', '', 'text_area', false, true));
        $subType1->addChild(new Property('link', '', 'internal_link', false, true));
        $this->subBlockProperty->addType($subType1);
        $type1->addChild($this->subBlockProperty);

        $type2 = new BlockPropertyType('type2', '');
        $type2->addChild(new Property('name', '', 'text_line', false, true));
        $type2->addChild(new Property('link', '', 'internal_link', false, true));

        $this->blockProperty->addType($type2);
    }

    public function testRead()
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title',
                'article' => 'Test-Article',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Sub-Title',
                        'article' => 'Test-Sub-Article',
                        'settings' => new \stdClass(),
                    ],
                ],
                'settings' => new \stdClass(),
            ],
        ];

        $valueMap = [
            'i18n:de-block1-length' => 1,
            'i18n:de-block1-type#0' => $data[0]['type'],
            'i18n:de-block1-title#0' => $data[0]['title'],
            'i18n:de-block1-article#0' => $data[0]['article'],
            'i18n:de-block1-sub-block#0-length' => 1,
            'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
            'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
            'i18n:de-block1-settings#0' => '[]',
            'i18n:de-block1-sub-block#0-settings#0' => '{}',
        ];

        $this->node = $this->prophesize(Node::class);
        foreach ($valueMap as $name => $value) {
            $this->node->getPropertyValue($name)->willReturn($value);
            $this->node->hasProperty($name)->willReturn(true);
        }

        $this->blockContentType->read(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            'default',
            'de',
            ''
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testReadWithEmptySettings()
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title',
                'article' => 'Test-Article',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Sub-Title',
                        'article' => 'Test-Sub-Article',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
        ];

        $valueMap = [
            'i18n:de-block1-length' => 1,
            'i18n:de-block1-type#0' => $data[0]['type'],
            'i18n:de-block1-title#0' => $data[0]['title'],
            'i18n:de-block1-article#0' => $data[0]['article'],
            'i18n:de-block1-sub-block#0-length' => 1,
            'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
            'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
            'i18n:de-block1-settings#0' => '[]',
            'i18n:de-block1-sub-block#0-settings#0' => '{}',
        ];

        $this->node = $this->prophesize(Node::class);
        foreach ($valueMap as $name => $value) {
            $this->node->getPropertyValue($name)->willReturn($value);
            $this->node->hasProperty($name)->willReturn(true);
        }

        $this->blockContentType->read(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            'default',
            'de',
            ''
        );

        // check resulted structure
        $this->assertEquals(
            [
                [
                    'type' => 'type1',
                    'title' => 'Test-Title',
                    'article' => 'Test-Article',
                    'sub-block' => [
                        [
                            'type' => 'subType1',
                            'title' => 'Test-Sub-Title',
                            'article' => 'Test-Sub-Article',
                            'settings' => new \stdClass(),
                        ],
                    ],
                    'settings' => new \stdClass(),
                ],
            ],
            $this->blockProperty->getValue()
        );
    }

    public function testWrite()
    {
        $this->prepareSingleBlockProperty();

        $result = [];
        $this->node = $this->prophesize(Node::class);
        $this->node->getPropertyValueWithDefault(Argument::any(), null)->willReturn(null);
        $this->node->setProperty(Argument::cetera())->will(
            function($arguments) use (&$result) {
                $result[$arguments[0]] = $arguments[1];
            }
        );

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title',
                'article' => 'Test-Article',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title',
                        'article' => 'Test-Article',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
        ];
        $this->blockProperty->setValue($data);

        $this->blockContentType->write(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            1,
            'default',
            'de',
            ''
        );

        // check repository node
        $this->assertEquals(
            [
                'i18n:de-block1-length' => 1,
                'i18n:de-block1-type#0' => $data[0]['type'],
                'i18n:de-block1-title#0' => $data[0]['title'],
                'i18n:de-block1-article#0' => $data[0]['article'],
                'i18n:de-block1-sub-block#0-length' => 1,
                'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
                'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
                'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
                'i18n:de-block1-settings#0' => '[]',
                'i18n:de-block1-sub-block#0-settings#0' => '[]',
            ],
            $result
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testReadMultiple()
    {
        $this->prepareMultipleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => new \stdClass(),
                    ],
                ],
                'settings' => ['segment' => 'w'],
            ],
            [
                'type' => 'type1',
                'title' => 'Test-Title-2',
                'article' => 'Test-Article-2',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-2',
                        'article' => 'Test-Article-Sub-2',
                        'settings' => new \stdClass(),
                    ],
                ],
                'settings' => new \stdClass(),
            ],
        ];

        $valueMap = [
            'i18n:de-block1-length' => 2,
            'i18n:de-block1-type#0' => 'type1',
            'i18n:de-block1-title#0' => $data[0]['title'],
            'i18n:de-block1-article#0' => $data[0]['article'],
            'i18n:de-block1-sub-block#0-length' => 1,
            'i18n:de-block1-sub-block#0-type#0' => 'subType1',
            'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
            'i18n:de-block1-type#1' => 'type1',
            'i18n:de-block1-title#1' => $data[1]['title'],
            'i18n:de-block1-article#1' => $data[1]['article'],
            'i18n:de-block1-sub-block#1-length' => 1,
            'i18n:de-block1-sub-block#1-type#0' => 'subType1',
            'i18n:de-block1-sub-block#1-title#0' => $data[1]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#1-article#0' => $data[1]['sub-block'][0]['article'],
            'i18n:de-block1-settings#0' => '{"segment": "w"}',
            'i18n:de-block1-sub-block#0-settings#0' => '{}',
            'i18n:de-block1-settings#1' => '{}',
            'i18n:de-block1-sub-block#1-settings#0' => '{}',
        ];

        $this->node = $this->prophesize(Node::class);
        foreach ($valueMap as $name => $value) {
            $this->node->getPropertyValue($name)->willReturn($value);
            $this->node->hasProperty($name)->willReturn(true);
        }

        $this->blockContentType->read(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
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

        $result = [];
        $this->node = $this->prophesize(Node::class);
        $this->node->getPropertyValueWithDefault(Argument::any(), null)->willReturn(null);
        $this->node->setProperty(Argument::cetera())->will(
            function($arguments) use (&$result) {
                $result[$arguments[0]] = $arguments[1];
            }
        );

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type1',
                'title' => 'Test-Title-2',
                'article' => 'Test-Article-2',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-2',
                        'article' => 'Test-Article-Sub-2',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
        ];
        $this->blockProperty->setValue($data);

        $this->blockContentType->write(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            1,
            'default',
            'de',
            ''
        );

        // check repository node
        $this->assertEquals(
            [
                'i18n:de-block1-length' => 2,
                'i18n:de-block1-type#0' => $data[0]['type'],
                'i18n:de-block1-title#0' => $data[0]['title'],
                'i18n:de-block1-article#0' => $data[0]['article'],
                'i18n:de-block1-sub-block#0-length' => 1,
                'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
                'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
                'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
                'i18n:de-block1-type#1' => $data[1]['type'],
                'i18n:de-block1-title#1' => $data[1]['title'],
                'i18n:de-block1-article#1' => $data[1]['article'],
                'i18n:de-block1-sub-block#1-length' => 1,
                'i18n:de-block1-sub-block#1-type#0' => $data[1]['sub-block'][0]['type'],
                'i18n:de-block1-sub-block#1-title#0' => $data[1]['sub-block'][0]['title'],
                'i18n:de-block1-sub-block#1-article#0' => $data[1]['sub-block'][0]['article'],
                'i18n:de-block1-settings#0' => '[]',
                'i18n:de-block1-sub-block#0-settings#0' => '[]',
                'i18n:de-block1-settings#1' => '[]',
                'i18n:de-block1-sub-block#1-settings#0' => '[]',
            ],
            $result
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testReadMultipleDifferentTypes()
    {
        $this->prepareMultipleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => new \stdClass(),
                    ],
                ],
                'settings' => new \stdClass(),
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-2',
                'settings' => new \stdClass(),
            ],
        ];

        $valueMap = [
            'i18n:de-block1-length' => 2,
            'i18n:de-block1-type#0' => $data[0]['type'],
            'i18n:de-block1-title#0' => $data[0]['title'],
            'i18n:de-block1-article#0' => $data[0]['article'],
            'i18n:de-block1-sub-block#0-length' => 1,
            'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
            'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
            'i18n:de-block1-type#1' => $data[1]['type'],
            'i18n:de-block1-name#1' => $data[1]['name'],
            'i18n:de-block1-settings#0' => '[]',
            'i18n:de-block1-sub-block#0-settings#0' => '[]',
            'i18n:de-block1-settings#1' => '[]',
        ];

        $this->node = $this->prophesize(Node::class);
        foreach ($valueMap as $name => $value) {
            $this->node->getPropertyValue($name)->willReturn($value);
            $this->node->hasProperty($name)->willReturn(true);
        }

        $this->blockContentType->read(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
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

        $result = [];
        $this->node = $this->prophesize(Node::class);
        $this->node->getPropertyValueWithDefault(Argument::any(), null)->willReturn(null);
        $this->node->setProperty(Argument::cetera())->will(
            function($arguments) use (&$result) {
                $result[$arguments[0]] = $arguments[1];
            }
        );

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-2',
                'settings' => [],
            ],
        ];
        $this->blockProperty->setValue($data);

        $this->blockContentType->write(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            1,
            'default',
            'de',
            ''
        );

        // check repository node
        $this->assertEquals(
            [
                'i18n:de-block1-length' => 2,
                'i18n:de-block1-type#0' => $data[0]['type'],
                'i18n:de-block1-title#0' => $data[0]['title'],
                'i18n:de-block1-article#0' => $data[0]['article'],
                'i18n:de-block1-sub-block#0-length' => 1,
                'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
                'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
                'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
                'i18n:de-block1-type#1' => $data[1]['type'],
                'i18n:de-block1-name#1' => $data[1]['name'],
                'i18n:de-block1-settings#0' => '[]',
                'i18n:de-block1-sub-block#0-settings#0' => '[]',
                'i18n:de-block1-settings#1' => '[]',
            ],
            $result
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testGetContentData()
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-2',
                'settings' => [],
            ],
        ];
        $this->blockProperty->setValue($data);

        $result = $this->blockContentType->getContentData($this->blockProperty);

        $this->assertEquals($data, $result);
    }

    public function testGetContentDataWithHiddenBlocks()
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => [],
                    ],
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => ['hidden' => true],
                    ],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-2',
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-3',
                'settings' => ['hidden' => true],
            ],
        ];
        $this->blockProperty->setValue($data);

        $result = $this->blockContentType->getContentData($this->blockProperty);

        $this->assertEquals(
            [
                [
                    'type' => 'type1',
                    'title' => 'Test-Title-1',
                    'article' => [
                        'Test-Article-1-1',
                        'Test-Article-1-2',
                    ],
                    'sub-block' => [
                        [
                            'type' => 'subType1',
                            'title' => 'Test-Title-Sub-1',
                            'article' => 'Test-Article-Sub-1',
                            'settings' => [],
                        ],
                    ],
                    'settings' => [],
                ],
                [
                    'type' => 'type2',
                    'name' => 'Test-Name-2',
                    'settings' => [],
                ],
            ],
            $result
        );
    }
}

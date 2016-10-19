<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Metadata\Loader;

use InvalidArgumentException;
use Prophecy\Argument;
use Sulu\Component\Content\Metadata\Loader\XmlLegacyLoader;
use Sulu\Component\HttpCache\CacheLifetimeResolverInterface;

class XmlLegacyLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function templateDataProvider()
    {
        return [['page'], ['home'], ['article']];
    }

    /**
     * @dataProvider templateDataProvider
     */
    public function testReadTemplate($type)
    {
        $template = [
            'key' => 'template',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 2400],
            'tags' => [
                [
                    'name' => 'some.random.structure.tag',
                    'attributes' => [
                        'foo' => 'bar',
                        'bar' => 'foo',
                    ],
                ],
            ],
            'meta' => [
                'title' => [
                    'de' => 'Das ist das Template 1',
                    'en' => 'That´s the template 1',
                ],
            ],
            'properties' => [
                'title' => [
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.node.title',
                            'priority' => 10,
                            'attributes' => [],
                        ],
                        [
                            'name' => 'some.random.tag',
                            'priority' => null,
                            'attributes' => [
                                'one' => '1',
                                'two' => '2',
                                'three' => 'three',
                            ],
                        ],
                    ],
                    'params' => [],
                    'meta' => [
                        'title' => [
                            'de' => 'Titel',
                            'en' => 'Title',
                        ],
                        'info_text' => [
                            'de' => 'Titel-Info-DE',
                            'en' => 'Title-Info-EN',
                        ],
                        'placeholder' => [
                            'de' => 'Platzhalter-Info-DE',
                            'en' => 'Placeholder-Info-EN',
                        ],
                    ],
                ],
                'url' => [
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.rlp',
                            'priority' => 1,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
                'article' => [
                    'name' => 'article',
                    'type' => 'text_area',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.node.title',
                            'priority' => 5,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
                'pages' => [
                    'name' => 'pages',
                    'type' => 'smart_content_selection',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.node.title',
                            'priority' => null,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
                'article_number' => [
                    'name' => 'article_number',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => false,
                    'tags' => [],
                    'params' => [],
                    'meta' => [],
                ],
                'images' => [
                    'name' => 'images',
                    'type' => 'image_selection',
                    'minOccurs' => 0,
                    'maxOccurs' => 2,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => true,
                    'tags' => [],
                    'params' => [
                        [
                            'name' => 'minLinks',
                            'value' => 1,
                            'type' => 'string',
                            'meta' => [],
                        ],
                        [
                            'name' => 'maxLinks',
                            'value' => 10,
                            'type' => 'string',
                            'meta' => [],
                        ],
                        [
                            'name' => 'displayOptions',
                            'value' => [
                                [
                                    'name' => 'leftTop',
                                    'value' => true,
                                    'type' => 'string',
                                    'meta' => [],
                                ],
                                [
                                    'name' => 'top',
                                    'value' => false,
                                    'type' => 'string',
                                    'meta' => [],
                                ],
                                [
                                    'name' => 'rightTop',
                                    'value' => true,
                                    'type' => 'string',
                                    'meta' => [],
                                ],
                                [
                                    'name' => 'left',
                                    'value' => false,
                                    'type' => 'string',
                                    'meta' => [],
                                ],
                                [
                                    'name' => 'middle',
                                    'value' => false,
                                    'type' => 'string',
                                    'meta' => [],
                                ],
                                [
                                    'name' => 'right',
                                    'value' => false,
                                    'type' => 'string',
                                    'meta' => [],
                                ],
                                [
                                    'name' => 'leftBottom',
                                    'value' => true,
                                    'type' => 'string',
                                    'meta' => [],
                                ],
                                [
                                    'name' => 'bottom',
                                    'value' => false,
                                    'type' => 'string',
                                    'meta' => [],
                                ],
                                [
                                    'name' => 'rightBottom',
                                    'value' => true,
                                    'type' => 'string',
                                    'meta' => [],
                                ],
                            ],
                            'type' => 'collection',
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                ],
            ],
        ];

        $result = $this->loadFixture('template.xml', $type);
        $this->assertEquals($template, $result);
        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, count($x));
    }

    public function testReadTitleInSection()
    {
        $template = [
            'key' => 'template',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 2400],
            'properties' => [
                'title_section' => [
                    'name' => 'title_section',
                    'colspan' => null,
                    'cssClass' => null,
                    'type' => 'section',
                    'params' => [],
                    'meta' => [],
                    'properties' => [
                        'title' => [
                            'name' => 'title',
                            'type' => 'text_line',
                            'minOccurs' => null,
                            'maxOccurs' => null,
                            'colspan' => null,
                            'cssClass' => null,
                            'mandatory' => true,
                            'multilingual' => true,
                            'tags' => [],
                            'params' => [],
                            'meta' => [],
                        ],
                    ],
                ],
                'url' => [
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.rlp',
                            'priority' => 1,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
            ],
            'tags' => [],
            'meta' => [],
        ];

        $result = $this->loadFixture('template_title_in_section.xml');

        $this->assertEquals($template, $result);
        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, count($x));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testReadTypesInvalidPath()
    {
        $this->loadFixture('template_not_exists.xml');
    }

    public function testReadTypesEmptyProperties()
    {
        $template = [
            'key' => 'template',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 2400],
            'properties' => [],
            'tags' => [],
            'meta' => [],
        ];

        $this->setExpectedException(
            '\Sulu\Component\Content\Metadata\Loader\Exception\RequiredPropertyNameNotFoundException',
            'The property with the name "title" is required, but was not found in the template "template"'
        );
        $result = $this->loadFixture('template_missing_properties.xml');
        $this->assertEquals($template, $result);
    }

    /**
     * @expectedException \Sulu\Component\Content\Metadata\Loader\Exception\InvalidXmlException
     */
    public function testReadTypesMissingMandatory()
    {
        $this->loadFixture('template_missing_mandatory.xml');
    }

    public function testReadBlockTemplate()
    {
        $template = [
            'key' => 'template_block',
            'view' => 'ClientWebsiteBundle:Website:complex.html.twig',
            'controller' => 'SuluWebsiteBundle:Default:index',
            'cacheLifetime' => ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 4800],
            'properties' => [
                'title' => [
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.node.title',
                            'priority' => 10,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
                'url' => [
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.rlp',
                            'priority' => 1,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
                'article' => [
                    'name' => 'article',
                    'type' => 'text_editor',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [],
                    'params' => [],
                    'meta' => [],
                ],
                'block1' => [
                    'name' => 'block1',
                    'default-type' => 'default',
                    'minOccurs' => '2',
                    'maxOccurs' => '10',
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'type' => 'block',
                    'tags' => [
                        [
                            'name' => 'sulu.node.block',
                            'priority' => 20,
                            'attributes' => [],
                        ],
                        [
                            'name' => 'sulu.test.block',
                            'priority' => 1,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                    'types' => [
                        'default' => [
                            'name' => 'default',
                            'meta' => [],
                            'properties' => [
                                'title1.1' => [
                                    'name' => 'title1.1',
                                    'type' => 'text_line',
                                    'minOccurs' => null,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => [],
                                    'params' => [],
                                    'meta' => [],
                                ],
                                'article1.1' => [
                                    'name' => 'article1.1',
                                    'type' => 'text_area',
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'minOccurs' => 2,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'tags' => [],
                                    'params' => [],
                                    'meta' => [],
                                ],
                                'block1.1' => [
                                    'name' => 'block1.1',
                                    'default-type' => 'default',
                                    'minOccurs' => null,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => false,
                                    'type' => 'block',
                                    'tags' => [],
                                    'params' => [],
                                    'meta' => [],
                                    'types' => [
                                        'default' => [
                                            'name' => 'default',
                                            'meta' => [],
                                            'properties' => [
                                                'block1.1.1' => [
                                                    'name' => 'block1.1.1',
                                                    'default-type' => 'default',
                                                    'minOccurs' => null,
                                                    'maxOccurs' => null,
                                                    'colspan' => null,
                                                    'cssClass' => null,
                                                    'mandatory' => false,
                                                    'type' => 'block',
                                                    'tags' => [],
                                                    'params' => [],
                                                    'meta' => [],
                                                    'types' => [
                                                        'default' => [
                                                            'name' => 'default',
                                                            'meta' => [],
                                                            'properties' => [
                                                                'article1.1.1' => [
                                                                    'name' => 'article1.1.1',
                                                                    'type' => 'text_area',
                                                                    'minOccurs' => 2,
                                                                    'maxOccurs' => null,
                                                                    'colspan' => null,
                                                                    'cssClass' => null,
                                                                    'mandatory' => true,
                                                                    'multilingual' => true,
                                                                    'tags' => [
                                                                        [
                                                                            'name' => 'sulu.node.title',
                                                                            'priority' => 5,
                                                                            'attributes' => [],
                                                                        ],
                                                                    ],
                                                                    'params' => [],
                                                                    'meta' => [],
                                                                ],
                                                                'article2.1.2' => [
                                                                    'name' => 'article2.1.2',
                                                                    'type' => 'text_area',
                                                                    'minOccurs' => 2,
                                                                    'maxOccurs' => null,
                                                                    'colspan' => null,
                                                                    'cssClass' => null,
                                                                    'mandatory' => true,
                                                                    'multilingual' => true,
                                                                    'tags' => [],
                                                                    'params' => [],
                                                                    'meta' => [],
                                                                ],
                                                                'block1.1.3' => [
                                                                    'name' => 'block1.1.3',
                                                                    'default-type' => 'default',
                                                                    'minOccurs' => null,
                                                                    'maxOccurs' => null,
                                                                    'colspan' => null,
                                                                    'cssClass' => null,
                                                                    'mandatory' => false,
                                                                    'type' => 'block',
                                                                    'tags' => [],
                                                                    'params' => [],
                                                                    'meta' => [],
                                                                    'types' => [
                                                                        'default' => [
                                                                            'name' => 'default',
                                                                            'meta' => [],
                                                                            'properties' => [
                                                                                'article1.1.3.1' => [
                                                                                    'name' => 'article1.1.3.1',
                                                                                    'type' => 'text_area',
                                                                                    'minOccurs' => 2,
                                                                                    'maxOccurs' => null,
                                                                                    'colspan' => null,
                                                                                    'cssClass' => null,
                                                                                    'mandatory' => true,
                                                                                    'multilingual' => true,
                                                                                    'tags' => [],
                                                                                    'params' => [],
                                                                                    'meta' => [],
                                                                                ],
                                                                            ],
                                                                        ],
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                                'block1.1.2' => [
                                                    'name' => 'block1.1.2',
                                                    'default-type' => 'default',
                                                    'type' => 'block',
                                                    'minOccurs' => null,
                                                    'maxOccurs' => null,
                                                    'colspan' => null,
                                                    'cssClass' => null,
                                                    'mandatory' => false,
                                                    'tags' => [],
                                                    'params' => [],
                                                    'meta' => [],
                                                    'types' => [
                                                        'default' => [
                                                            'name' => 'default',
                                                            'meta' => [],
                                                            'properties' => [
                                                                'article1.1.2.1' => [
                                                                    'name' => 'article1.1.2.1',
                                                                    'type' => 'text_area',
                                                                    'minOccurs' => 2,
                                                                    'maxOccurs' => null,
                                                                    'colspan' => null,
                                                                    'cssClass' => null,
                                                                    'mandatory' => true,
                                                                    'multilingual' => true,
                                                                    'tags' => [],
                                                                    'params' => [],
                                                                    'meta' => [],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'blog' => [
                    'name' => 'blog',
                    'type' => 'text_editor',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [],
                    'params' => [],
                    'meta' => [],
                ],
            ],
            'tags' => [],
            'meta' => [],
        ];

        $result = $this->loadFixture('template_block.xml');
        $this->assertEquals($template, $result);
        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, count($x));
    }

    public function testBlockMultipleTypes()
    {
        $template = [
            'key' => 'template_block_types',
            'view' => 'ClientWebsiteBundle:Website:complex.html.twig',
            'controller' => 'SuluWebsiteBundle:Default:index',
            'cacheLifetime' => ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 4800],
            'properties' => [
                'title' => [
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.node.title',
                            'priority' => 10,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
                'url' => [
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.rlp',
                            'priority' => 1,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
                'block1' => [
                    'name' => 'block1',
                    'default-type' => 'default',
                    'minOccurs' => '2',
                    'maxOccurs' => '10',
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'type' => 'block',
                    'tags' => [
                        [
                            'name' => 'sulu.node.block',
                            'priority' => 20,
                            'attributes' => [],
                        ],
                        [
                            'name' => 'sulu.test.block',
                            'priority' => 1,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [
                        'title' => [
                            'de' => 'Block1 DE',
                            'en' => 'Block1 EN',
                        ],
                        'info_text' => [
                            'de' => 'Info Block1 DE',
                            'en' => 'Info Block1 EN',
                        ],
                        'placeholder' => [
                            'de' => 'Placeholder Block1 DE',
                            'en' => 'Placeholder Block1 EN',
                        ],
                    ],
                    'types' => [
                        'default' => [
                            'name' => 'default',
                            'meta' => [
                                'title' => [
                                    'de' => 'Default DE',
                                    'en' => 'Default EN',
                                ],
                                'info_text' => [
                                    'de' => 'Info Default DE',
                                    'en' => 'Info Default EN',
                                ],
                                'placeholder' => [
                                    'de' => 'Placeholder Default DE',
                                    'en' => 'Placeholder Default EN',
                                ],
                            ],
                            'properties' => [
                                'title' => [
                                    'name' => 'title',
                                    'type' => 'text_line',
                                    'minOccurs' => null,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => [],
                                    'params' => [],
                                    'meta' => [],
                                ],
                                'article' => [
                                    'name' => 'article',
                                    'type' => 'text_area',
                                    'minOccurs' => 2,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => [],
                                    'params' => [],
                                    'meta' => [],
                                ],
                            ],
                        ],
                        'test' => [
                            'name' => 'test',
                            'meta' => [
                                'title' => [
                                    'de' => 'Test DE',
                                    'en' => 'Test EN',
                                ],
                                'info_text' => [
                                    'de' => 'Info Test DE',
                                    'en' => 'Info Test EN',
                                ],
                                'placeholder' => [
                                    'de' => 'Placeholder Test DE',
                                    'en' => 'Placeholder Test EN',
                                ],
                            ],
                            'properties' => [
                                'title' => [
                                    'name' => 'title',
                                    'type' => 'text_line',
                                    'minOccurs' => null,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => [],
                                    'params' => [],
                                    'meta' => [],
                                ],
                                'name' => [
                                    'name' => 'name',
                                    'type' => 'text_line',
                                    'minOccurs' => 2,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => [],
                                    'params' => [],
                                    'meta' => [],
                                ],
                                'article' => [
                                    'name' => 'article',
                                    'type' => 'text_editor',
                                    'minOccurs' => 2,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => [],
                                    'params' => [],
                                    'meta' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                'blog' => [
                    'name' => 'blog',
                    'type' => 'text_editor',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [],
                    'params' => [],
                    'meta' => [],
                ],
            ],
            'tags' => [],
            'meta' => [],
        ];

        $result = $this->loadFixture('template_block_types.xml');
        $this->assertEquals($template, $result);
        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, count($x));
    }

    public function testSections()
    {
        $template = [
            'key' => 'template_sections',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 2400],
            'properties' => [
                'title' => [
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => 6,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        '0' => [
                            'name' => 'sulu.node.title',
                            'priority' => 10,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [
                        'title' => [
                            'de' => 'Titel',
                            'en' => 'Title',
                        ],
                        'info_text' => [
                            'de' => 'Titel-Info-DE',
                            'en' => 'Title-Info-EN',
                        ],
                        'placeholder' => [
                            'de' => 'Platzhalter-Info-DE',
                            'en' => 'Placeholder-Info-EN',
                        ],
                    ],
                ],
                'test' => [
                    'name' => 'test',
                    'colspan' => null,
                    'cssClass' => 'test',
                    'type' => 'section',
                    'params' => [],
                    'meta' => [
                        'title' => [
                            'de' => 'Test-DE',
                            'en' => 'Test-EN',
                        ],
                        'info_text' => [
                            'de' => 'Info-DE',
                            'en' => 'Info-EN',
                        ],
                    ],
                    'properties' => [
                        'url' => [
                            'name' => 'url',
                            'type' => 'resource_locator',
                            'minOccurs' => null,
                            'maxOccurs' => null,
                            'colspan' => 6,
                            'cssClass' => 'test',
                            'mandatory' => true,
                            'multilingual' => true,
                            'tags' => [
                                '0' => [
                                    'name' => 'sulu.rlp',
                                    'priority' => '1',
                                    'attributes' => [],
                                ],
                            ],
                            'params' => [],
                            'meta' => [],
                        ],
                        'article' => [
                            'name' => 'article',
                            'type' => 'text_area',
                            'minOccurs' => null,
                            'maxOccurs' => null,
                            'colspan' => 6,
                            'cssClass' => null,
                            'mandatory' => null,
                            'multilingual' => true,
                            'tags' => [
                                '0' => [
                                    'name' => 'sulu.node.title',
                                    'priority' => 5,
                                    'attributes' => [],
                                ],

                            ],
                            'params' => [],
                            'meta' => [],
                        ],
                        'block' => [
                            'name' => 'block',
                            'default-type' => 'test',
                            'minOccurs' => null,
                            'maxOccurs' => null,
                            'colspan' => null,
                            'cssClass' => null,
                            'mandatory' => null,
                            'type' => 'block',
                            'tags' => [],
                            'params' => [],
                            'meta' => [
                                'title' => [
                                    'de' => 'Block-DE',
                                    'en' => 'Block-EN',
                                ],
                                'info_text' => [
                                    'de' => 'Block-Info-DE',
                                    'en' => 'Block-Info-EN',
                                ],
                            ],
                            'types' => [
                                'test' => [
                                    'name' => 'test',
                                    'meta' => [],
                                    'properties' => [
                                        'name' => [
                                            'name' => 'name',
                                            'type' => 'text_line',
                                            'minOccurs' => null,
                                            'maxOccurs' => null,
                                            'colspan' => null,
                                            'cssClass' => null,
                                            'mandatory' => null,
                                            'multilingual' => true,
                                            'tags' => [],
                                            'params' => [],
                                            'meta' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'pages' => [
                    'name' => 'pages',
                    'type' => 'smart_content_selection',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => true,
                    'tags' => [
                        '0' => [
                            'name' => 'sulu.node.title',
                            'priority' => null,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
                'images' => [
                    'name' => 'images',
                    'type' => 'image_selection',
                    'minOccurs' => 0,
                    'maxOccurs' => 2,
                    'colspan' => 6,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => true,
                    'tags' => [],
                    'params' => [
                        [
                            'name' => 'minLinks',
                            'value' => 1,
                            'type' => 'string',
                            'meta' => [],
                        ],
                        [
                            'name' => 'maxLinks',
                            'value' => 10,
                            'type' => 'string',
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                ],
            ],
            'tags' => [],
            'meta' => [],
        ];

        $result = $this->loadFixture('template_sections.xml');
        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, count($x));
    }

    public function testReservedName()
    {
        $this->setExpectedException(
            '\Sulu\Component\Content\Metadata\Loader\Exception\ReservedPropertyNameException'
        );

        $result = $this->loadFixture('template_reserved.xml');
    }

    public function testNestingParams()
    {
        $template = [
            'key' => 'template_nesting_params',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 2400],
            'properties' => [
                'title' => [
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [],
                    'params' => [
                        ['name' => 'minLinks', 'value' => '1', 'type' => 'string', 'meta' => []],
                        ['name' => 'maxLinks', 'value' => '10', 'type' => 'string', 'meta' => []],
                        [
                            'name' => 'test',
                            'value' => [
                                ['name' => 't1', 'value' => 'v1', 'type' => 'string', 'meta' => []],
                                ['name' => 't2', 'value' => 'v2', 'type' => 'string', 'meta' => []],
                                ['name' => 't3', 'value' => 'v3', 'type' => 'string', 'meta' => []],
                                ['name' => 't4', 'value' => 'v4', 'type' => 'string', 'meta' => []],
                            ],
                            'type' => 'collection',
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                ],
                'url' => [
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.rlp',
                            'priority' => 1,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
            ],
            'tags' => [],
            'meta' => [],
        ];

        $result = $this->loadFixture('template_nesting_params.xml');

        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, count($x));
        $this->assertEquals($template, $result);
    }

    public function testMetaParams()
    {
        $template = [
            'key' => 'template_meta_params',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 2400],
            'properties' => [
                'title' => [
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [],
                    'params' => [
                        [
                            'name' => 'min',
                            'value' => '1',
                            'type' => 'string',
                            'meta' => [
                                'title' => ['de' => 'Mindestens', 'en' => 'Minimum'],
                            ],
                        ],
                    ],
                    'meta' => [],
                ],
                'url' => [
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.rlp',
                            'priority' => 1,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
            ],
            'tags' => [],
            'meta' => [],
        ];

        $result = $this->loadFixture('template_meta_params.xml');

        $this->assertEquals($template, $result);
    }

    public function testWithoutTitle()
    {
        $this->setExpectedException(
            '\Sulu\Component\Content\Metadata\Loader\Exception\RequiredPropertyNameNotFoundException',
            'The property with the name "title" is required, but was not found in the template "template"'
        );

        $this->loadFixture('template_missing_title.xml');
    }

    public function testWithoutRlpTagTypePage()
    {
        $this->setExpectedException(
            '\Sulu\Component\Content\Metadata\Loader\Exception\RequiredTagNotFoundException',
            'The tag with the name "sulu.rlp" is required, but was not found in the template "template"'
        );

        $resolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $resolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())->willReturn(true);
        $templateReader = new XmlLegacyLoader($resolver->reveal());
        $result = $templateReader->load(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->getResourceDirectory(), 'DataFixtures', 'Page', 'template_missing_rlp_tag.xml']
            ),
            'page'
        );
    }

    public function testWithoutRlpTagTypePageInternal()
    {
        $resolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $resolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())->willReturn(true);
        $templateReader = new XmlLegacyLoader($resolver->reveal());
        $result = $templateReader->load(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->getResourceDirectory(), 'DataFixtures', 'Page', 'template_missing_rlp_tag_internal.xml']
            ),
            'page'
        );

        // no exception should be thrown
        $this->assertNotNull($result);
    }

    public function testWithoutRlpTagTypeHome()
    {
        $this->setExpectedException(
            '\Sulu\Component\Content\Metadata\Loader\Exception\RequiredTagNotFoundException',
            'The tag with the name "sulu.rlp" is required, but was not found in the template "template"'
        );

        $resolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $resolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())->willReturn(true);
        $templateReader = new XmlLegacyLoader($resolver->reveal());
        $result = $templateReader->load(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->getResourceDirectory(), 'DataFixtures', 'Page', 'template_missing_rlp_tag.xml']
            ),
            'home'
        );
    }

    public function testWithoutRlpTagTypeHomeInternal()
    {
        $resolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $resolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())->willReturn(true);
        $templateReader = new XmlLegacyLoader($resolver->reveal());
        $result = $templateReader->load(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->getResourceDirectory(), 'DataFixtures', 'Page', 'template_missing_rlp_tag_internal.xml']
            ),
            'home'
        );

        // no exception should be thrown
        $this->assertNotNull($result);
    }

    public function testWithoutRlpTagTypeSnippet()
    {
        $resolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $resolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())->willReturn(true);
        $templateReader = new XmlLegacyLoader($resolver->reveal());
        $result = $templateReader->load(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->getResourceDirectory(), 'DataFixtures', 'Page', 'template_missing_rlp_tag.xml']
            ),
            'snippet'
        );

        // no exception should be thrown
        $this->assertNotNull($result);
    }

    public function testCacheLifeTimeZero()
    {
        $result = $this->loadFixture('template_lifetime_0.xml');
        $this->assertEquals(
            ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 0],
            $result['cacheLifetime']
        );
    }

    public function testWithoutRlpTagTypeSnippetInternal()
    {
        $resolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $resolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())->willReturn(true);
        $templateReader = new XmlLegacyLoader($resolver->reveal());
        $result = $templateReader->load(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->getResourceDirectory(), 'DataFixtures', 'Page', 'template_missing_rlp_tag_internal.xml']
            ),
            'snippet'
        );

        // no exception should be thrown
        $this->assertNotNull($result);
    }

    public function testReadTemplateWithXInclude()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Xinclude are not supported with php(unit) and windows.');
        }

        $template = [
            'key' => 'template',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 2400],
            'tags' => [],
            'meta' => [
                'title' => [
                    'de' => 'Das ist das Template 1',
                    'en' => 'That´s the template 1',
                ],
            ],
            'properties' => [
                'title' => [
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [],
                    'params' => [],
                    'meta' => [],
                ],
                'url' => [
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => [
                        [
                            'name' => 'sulu.rlp',
                            'priority' => 1,
                            'attributes' => [],
                        ],
                    ],
                    'params' => [],
                    'meta' => [],
                ],
            ],
        ];

        $result = $this->loadFixture('template_with_xinclude.xml');
        $this->assertEquals($template, $result);
        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, count($x));
    }

    public function testLoadCacheLifetimeExpression()
    {
        $resolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $resolver->supports(CacheLifetimeResolverInterface::TYPE_EXPRESSION, '@daily')->willReturn(true);
        $xmlLegacyLoader = new XmlLegacyLoader($resolver->reveal());
        $result = $xmlLegacyLoader->load(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->getResourceDirectory(), 'DataFixtures', 'Page', 'template_expression.xml']
            ),
            'page'
        );

        $this->assertEquals(
            ['type' => CacheLifetimeResolverInterface::TYPE_EXPRESSION, 'value' => '@daily'],
            $result['cacheLifetime']
        );
    }

    public function testLoadCacheLifetimeInvalidExpression()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $resolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $resolver->supports(CacheLifetimeResolverInterface::TYPE_EXPRESSION, 'test')->willReturn(false);
        $xmlLegacyLoader = new XmlLegacyLoader($resolver->reveal());
        $result = $xmlLegacyLoader->load(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->getResourceDirectory(), 'DataFixtures', 'Page', 'template_invalid_expression.xml']
            ),
            'page'
        );
    }

    private function arrayRecursiveDiff($aArray1, $aArray2)
    {
        $aReturn = [];

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }

        return $aReturn;
    }

    private function loadFixture($name, $type = 'page')
    {
        $resolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $resolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())->willReturn(true);
        $xmlLegacyLoader = new XmlLegacyLoader($resolver->reveal());
        $result = $xmlLegacyLoader->load(
            implode(DIRECTORY_SEPARATOR, [$this->getResourceDirectory(), 'DataFixtures', 'Page', $name]),
            $type
        );

        return $result;
    }

    private function getResourceDirectory()
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [__DIR__, '..', '..', '..', '..', '..', '..', '..', '..', 'tests', 'Resources']
        );
    }
}

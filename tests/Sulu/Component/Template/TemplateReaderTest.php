<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Xml;

use InvalidArgumentException;
use Sulu\Component\Content\Template\TemplateReader;

class TemplateReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testReadTemplate()
    {
        $template = array(
            'key' => 'template',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => '2400',
            'meta' => array(
                'title' => array(
                    'de' => 'Das ist das Template 1',
                    'en' => 'That´s the template 1'
                )
            ),
            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.name',
                            'priority' => null
                        ),
                        array(
                            'name' => 'sulu.node.title',
                            'priority' => 10
                        )
                    ),
                    'params' => array(),
                    'meta' => array(
                        'title' => array(
                            'de' => 'Titel',
                            'en' => 'Title'
                        ),
                        'info_text' => array(
                            'de' => 'Titel-Info-DE',
                            'en' => 'Title-Info-EN'
                        ),
                        'placeholder' => array(
                            'de' => 'Platzhalter-Info-DE',
                            'en' => 'Placeholder-Info-EN'
                        )
                    )
                ),
                'url' => array(
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.rlp.part',
                            'priority' => 1
                        )
                    ),
                    'params' => array(),
                    'meta' => array()
                ),
                'article' => array(
                    'name' => 'article',
                    'type' => 'text_area',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.title',
                            'priority' => 5
                        )
                    ),
                    'params' => array(),
                    'meta' => array()
                ),
                'pages' => array(
                    'name' => 'pages',
                    'type' => 'smart_content_selection',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.title',
                            'priority' => null
                        )
                    ),
                    'params' => array(),
                    'meta' => array()
                ),
                'article_number' => array(
                    'name' => 'article_number',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => false,
                    'tags' => array(),
                    'params' => array(),
                    'meta' => array()
                ),
                'images' => array(
                    'name' => 'images',
                    'type' => 'image_selection',
                    'minOccurs' => 0,
                    'maxOccurs' => 2,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => true,
                    'tags' => array(),
                    'params' => array(
                        array(
                            'name' => 'minLinks',
                            'value' => 1
                        ),
                        array(
                            'name' => 'maxLinks',
                            'value' => 10
                        )
                    ),
                    'meta' => array()
                )
            )
        );

        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__ . '/../../../Resources/DataFixtures/Template/template.xml');

        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, sizeof($x));

        $this->assertEquals($template, $result);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testReadTypesInvalidPath()
    {
        $templateReader = new TemplateReader();
        $templateReader->load(
            __DIR__ . '/../../../Resources/DataFixtures/Template/template_not_exists.xml'
        );
    }

    public function testReadTypesEmptyProperties()
    {
        $template = array(
            'key' => 'template',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => 2400,
            'properties' => array()
        );

        $this->setExpectedException(
            '\Sulu\Component\Content\Template\Exception\InvalidXmlException',
            'The given XML is invalid! Tag(s) sulu.node.name required but not found'
        );
        $templateReader = new TemplateReader();
        $result = $templateReader->load(
            __DIR__ . '/../../../Resources/DataFixtures/Template/template_missing_properties.xml'
        );
        $this->assertEquals($template, $result);
    }

    /**
     * @expectedException \Sulu\Component\Content\Template\Exception\InvalidXmlException
     */
    public function testReadTypesMissingMandatory()
    {
        $templateReader = new TemplateReader();
        $templateReader->load(__DIR__ . '/../../../Resources/DataFixtures/Template/template_missing_mandatory.xml');
    }

    public function testReadBlockTemplate()
    {
        $template = array(
            'key' => 'complex',
            'view' => 'ClientWebsiteBundle:Website:complex.html.twig',
            'controller' => 'SuluWebsiteBundle:Default:index',
            'cacheLifetime' => '4800',
            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.name',
                            'priority' => null
                        ),
                        array(
                            'name' => 'sulu.node.title',
                            'priority' => 10
                        )
                    ),
                    'params' => array(),
                    'meta' => array()
                ),
                'url' => array(
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.rlp.part',
                            'priority' => 1
                        )
                    ),
                    'params' => array(),
                    'meta' => array()
                ),
                'article' => array(
                    'name' => 'article',
                    'type' => 'text_editor',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => array(),
                    'params' => array(),
                    'meta' => array()
                ),
                'block1' => array(
                    'name' => 'block1',
                    'default-type' => 'default',
                    'minOccurs' => '2',
                    'maxOccurs' => '10',
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'type' => 'block',
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.block',
                            'priority' => 20
                        ),
                        array(
                            'name' => 'sulu.test.block',
                            'priority' => 1
                        )
                    ),
                    'params' => array(),
                    'meta' => array(),
                    'types' => array(
                        'default' => array(
                            'name' => 'default',
                            'meta' => array(),
                            'properties' => array(
                                'title1.1' => array(
                                    'name' => 'title1.1',
                                    'type' => 'text_line',
                                    'minOccurs' => null,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => array(),
                                    'params' => array(),
                                    'meta' => array()
                                ),
                                'article1.1' => array(
                                    'name' => 'article1.1',
                                    'type' => 'text_area',
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'minOccurs' => 2,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'tags' => array(),
                                    'params' => array(),
                                    'meta' => array()
                                ),
                                'block1.1' => array(
                                    'name' => 'block1.1',
                                    'default-type' => 'default',
                                    'minOccurs' => null,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => false,
                                    'type' => 'block',
                                    'tags' => array(),
                                    'params' => array(),
                                    'meta' => array(),
                                    'types' => array(
                                        'default' => array(
                                            'name' => 'default',
                                            'meta' => array(),
                                            'properties' => array(
                                                'block1.1.1' => array(
                                                    'name' => 'block1.1.1',
                                                    'default-type' => 'default',
                                                    'minOccurs' => null,
                                                    'maxOccurs' => null,
                                                    'colspan' => null,
                                                    'cssClass' => null,
                                                    'mandatory' => false,
                                                    'type' => 'block',
                                                    'tags' => array(),
                                                    'params' => array(),
                                                    'meta' => array(),
                                                    'types' => array(
                                                        'default' => array(
                                                            'name' => 'default',
                                                            'meta' => array(),
                                                            'properties' => array(
                                                                'article1.1.1' => array(
                                                                    'name' => 'article1.1.1',
                                                                    'type' => 'text_area',
                                                                    'minOccurs' => 2,
                                                                    'maxOccurs' => null,
                                                                    'colspan' => null,
                                                                    'cssClass' => null,
                                                                    'mandatory' => true,
                                                                    'multilingual' => true,
                                                                    'tags' => array(
                                                                        array(
                                                                            'name' => 'sulu.node.title',
                                                                            'priority' => 5
                                                                        )
                                                                    ),
                                                                    'params' => array(),
                                                                    'meta' => array()
                                                                ),
                                                                'article2.1.2' => array(
                                                                    'name' => 'article2.1.2',
                                                                    'type' => 'text_area',
                                                                    'minOccurs' => 2,
                                                                    'maxOccurs' => null,
                                                                    'colspan' => null,
                                                                    'cssClass' => null,
                                                                    'mandatory' => true,
                                                                    'multilingual' => true,
                                                                    'tags' => array(),
                                                                    'params' => array(),
                                                                    'meta' => array()
                                                                ),
                                                                'block1.1.3' => array(
                                                                    'name' => 'block1.1.3',
                                                                    'default-type' => 'default',
                                                                    'minOccurs' => null,
                                                                    'maxOccurs' => null,
                                                                    'colspan' => null,
                                                                    'cssClass' => null,
                                                                    'mandatory' => false,
                                                                    'type' => 'block',
                                                                    'tags' => array(),
                                                                    'params' => array(),
                                                                    'meta' => array(),
                                                                    'types' => array(
                                                                        'default' => array(
                                                                            'name' => 'default',
                                                                            'meta' => array(),
                                                                            'properties' => array(
                                                                                'article1.1.3.1' => array(
                                                                                    'name' => 'article1.1.3.1',
                                                                                    'type' => 'text_area',
                                                                                    'minOccurs' => 2,
                                                                                    'maxOccurs' => null,
                                                                                    'colspan' => null,
                                                                                    'cssClass' => null,
                                                                                    'mandatory' => true,
                                                                                    'multilingual' => true,
                                                                                    'tags' => array(),
                                                                                    'params' => array(),
                                                                                    'meta' => array()
                                                                                )
                                                                            )
                                                                        )
                                                                    )
                                                                )
                                                            )
                                                        )
                                                    )
                                                ),
                                                'block1.1.2' => array(
                                                    'name' => 'block1.1.2',
                                                    'default-type' => 'default',
                                                    'type' => 'block',
                                                    'minOccurs' => null,
                                                    'maxOccurs' => null,
                                                    'colspan' => null,
                                                    'cssClass' => null,
                                                    'mandatory' => false,
                                                    'tags' => array(),
                                                    'params' => array(),
                                                    'meta' => array(),
                                                    'types' => array(
                                                        'default' => array(
                                                            'name' => 'default',
                                                            'meta' => array(),
                                                            'properties' => array(
                                                                'article1.1.2.1' => array(
                                                                    'name' => 'article1.1.2.1',
                                                                    'type' => 'text_area',
                                                                    'minOccurs' => 2,
                                                                    'maxOccurs' => null,
                                                                    'colspan' => null,
                                                                    'cssClass' => null,
                                                                    'mandatory' => true,
                                                                    'multilingual' => true,
                                                                    'tags' => array(),
                                                                    'params' => array(),
                                                                    'meta' => array()
                                                                )
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                'blog' => array(
                    'name' => 'blog',
                    'type' => 'text_editor',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => array(),
                    'params' => array(),
                    'meta' => array()
                ),
            )
        );

        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__ . '/../../../Resources/DataFixtures/Template/template_block.xml');

        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, sizeof($x));

        $this->assertEquals($template, $result);
    }

    public function testDuplicatedPriority()
    {
        $this->setExpectedException(
            '\Sulu\Component\Content\Template\Exception\InvalidXmlException',
            'The given XML is invalid! Priority 10 of tag sulu.node.title exists duplicated'
        );
        $templateReader = new TemplateReader();
        $result = $templateReader->load(
            __DIR__ . '/../../../Resources/DataFixtures/Template/template_duplicated_priority.xml'
        );
    }

    public function testBlockMultipleTypes()
    {
        $template = array(
            'key' => 'complex',
            'view' => 'ClientWebsiteBundle:Website:complex.html.twig',
            'controller' => 'SuluWebsiteBundle:Default:index',
            'cacheLifetime' => '4800',
            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.name',
                            'priority' => null
                        ),
                        array(
                            'name' => 'sulu.node.title',
                            'priority' => 10
                        )
                    ),
                    'params' => array(),
                    'meta' => array()
                ),
                'url' => array(
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.rlp.part',
                            'priority' => 1
                        )
                    ),
                    'params' => array(),
                    'meta' => array()
                ),
                'block1' => array(
                    'name' => 'block1',
                    'default-type' => 'default',
                    'minOccurs' => '2',
                    'maxOccurs' => '10',
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'type' => 'block',
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.block',
                            'priority' => 20
                        ),
                        array(
                            'name' => 'sulu.test.block',
                            'priority' => 1
                        )
                    ),
                    'params' => array(),
                    'meta' => array(),
                    'types' => array(
                        'default' => array(
                            'name' => 'default',
                            'meta' => array(),
                            'properties' => array(
                                'title' => array(
                                    'name' => 'title',
                                    'type' => 'text_line',
                                    'minOccurs' => null,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => array(),
                                    'params' => array(),
                                    'meta' => array()
                                ),
                                'article' => array(
                                    'name' => 'article',
                                    'type' => 'text_area',
                                    'minOccurs' => 2,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => array(),
                                    'params' => array(),
                                    'meta' => array()
                                )
                            )
                        ),
                        'test' => array(
                            'name' => 'test',
                            'meta' => array(),
                            'properties' => array(
                                'title' => array(
                                    'name' => 'title',
                                    'type' => 'text_line',
                                    'minOccurs' => null,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => array(),
                                    'params' => array(),
                                    'meta' => array()
                                ),
                                'name' => array(
                                    'name' => 'name',
                                    'type' => 'text_line',
                                    'minOccurs' => 2,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => array(),
                                    'params' => array(),
                                    'meta' => array()
                                ),
                                'article' => array(
                                    'name' => 'article',
                                    'type' => 'text_editor',
                                    'minOccurs' => 2,
                                    'maxOccurs' => null,
                                    'colspan' => null,
                                    'cssClass' => null,
                                    'mandatory' => true,
                                    'multilingual' => true,
                                    'tags' => array(),
                                    'params' => array(),
                                    'meta' => array()
                                )
                            )
                        )
                    )
                ),
                'blog' => array(
                    'name' => 'blog',
                    'type' => 'text_editor',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => array(),
                    'params' => array(),
                    'meta' => array()
                )
            )
        );

        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__ . '/../../../Resources/DataFixtures/Template/template_block_types.xml');

        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, sizeof($x));

        $this->assertEquals($template, $result);
    }

    public function testSections()
    {
        $template = array(
            'key' => 'template',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => '2400',
            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'text_line',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => 6,
                    'cssClass' => null,
                    'mandatory' => true,
                    'multilingual' => true,
                    'tags' => array(
                        '0' => array(
                            'name' => 'sulu.node.name',
                            'priority' => null
                        ),
                        '1' => array(
                            'name' => 'sulu.node.title',
                            'priority' => 10
                        )
                    ),
                    'params' => array(),
                    'meta' => array(
                        'title' => array(
                            'de' => 'Titel',
                            'en' => 'Title'
                        ),
                        'info_text' => array(
                            'de' => 'Titel-Info-DE',
                            'en' => 'Title-Info-EN'
                        ),
                        'placeholder' => array(
                            'de' => 'Platzhalter-Info-DE',
                            'en' => 'Placeholder-Info-EN'
                        )
                    )
                ),
                'test' => array(
                    'name' => 'test',
                    'colspan' => null,
                    'cssClass' => 'test',
                    'type' => 'section',
                    'params' => array(),
                    'meta' => array(
                        'title' => array(
                            'de' => 'Test-DE',
                            'en' => 'Test-EN'
                        ),
                        'info_text' => array(
                            'de' => 'Info-DE',
                            'en' => 'Info-EN'
                        )
                    ),
                    'properties' => array(
                        'url' => array(
                            'name' => 'url',
                            'type' => 'resource_locator',
                            'minOccurs' => null,
                            'maxOccurs' => null,
                            'colspan' => 6,
                            'cssClass' => 'test',
                            'mandatory' => true,
                            'multilingual' => true,
                            'tags' => array(
                                '0' => Array
                                (
                                    'name' => 'sulu.rlp.part',
                                    'priority' => 1
                                )

                            ),
                            'params' => array(),
                            'meta' => array(),
                        ),
                        'article' => array(
                            'name' => 'article',
                            'type' => 'text_area',
                            'minOccurs' => null,
                            'maxOccurs' => null,
                            'colspan' => 6,
                            'cssClass' => null,
                            'mandatory' => null,
                            'multilingual' => true,
                            'tags' => array(
                                '0' => Array
                                (
                                    'name' => 'sulu.node.title',
                                    'priority' => 5
                                )

                            ),
                            'params' => array(),
                            'meta' => array()
                        ),
                        'block' => array(
                            'name' => 'block',
                            'default-type' => 'test',
                            'minOccurs' => null,
                            'maxOccurs' => null,
                            'colspan' => null,
                            'cssClass' => null,
                            'mandatory' => null,
                            'type' => 'block',
                            'tags' => array(),
                            'params' => array(),
                            'meta' => array(
                                'title' => array(
                                    'de' => 'Block-DE',
                                    'en' => 'Block-EN'
                                ),
                                'info_text' => array(
                                    'de' => 'Block-Info-DE',
                                    'en' => 'Block-Info-EN'
                                )
                            ),
                            'types' => array(
                                'test' => Array
                                (
                                    'name' => 'test',
                                    'meta' => array(),
                                    'properties' => array(
                                        'name' => array(
                                            'name' => 'name',
                                            'type' => 'text_line',
                                            'minOccurs' => null,
                                            'maxOccurs' => null,
                                            'colspan' => null,
                                            'cssClass' => null,
                                            'mandatory' => null,
                                            'multilingual' => true,
                                            'tags' => array(),
                                            'params' => array(),
                                            'meta' => array()
                                        )
                                    )
                                )
                            )
                        ),
                    )
                ),
                'pages' => array(
                    'name' => 'pages',
                    'type' => 'smart_content_selection',
                    'minOccurs' => null,
                    'maxOccurs' => null,
                    'colspan' => null,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => true,
                    'tags' => array(
                        '0' => array(
                            'name' => 'sulu.node.title',
                            'priority' => null
                        )
                    ),
                    'params' => array(),
                    'meta' => array(),
                ),
                'images' => array(
                    'name' => 'images',
                    'type' => 'image_selection',
                    'minOccurs' => 0,
                    'maxOccurs' => 2,
                    'colspan' => 6,
                    'cssClass' => null,
                    'mandatory' => false,
                    'multilingual' => true,
                    'tags' => array(),
                    'params' => array(
                        '0' => array(
                            'name' => 'minLinks',
                            'value' => 1
                        ),
                        '1' => array(
                            'name' => 'maxLinks',
                            'value' => 10
                        )
                    ),
                    'meta' => array(),
                )
            )
        );

        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__ . '/../../../Resources/DataFixtures/Template/template_sections.xml');

        $x = $this->arrayRecursiveDiff($result, $template);
        $this->assertEquals(0, sizeof($x));

        $this->assertEquals($template, $result);
    }

    function testReservedName()
    {
        $this->setExpectedException(
            '\Sulu\Component\Content\Template\Exception\InvalidXmlException',
            'The given XML is invalid! Property name changed is a reserved'
        );

        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__ . '/../../../Resources/DataFixtures/Template/template_reserved.xml');
    }

    function arrayRecursiveDiff($aArray1, $aArray2)
    {
        $aReturn = array();

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
}

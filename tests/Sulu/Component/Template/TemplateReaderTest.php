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

use Sulu\Component\Content\Template\TemplateReader;

class TemplateReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testReadTemplate()
    {
        $template = array(
            'key' => 'template',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => 2400,
            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'title' => 'properties.title',
                    'type' => 'text_line',
                    'mandatory' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.name'
                        ),
                        array(
                            'name' => 'sulu.node.title',
                            'priority' => 10
                        )
                    )
                ),
                'url' => array(
                    'name' => 'url',
                    'title' => 'properties.url',
                    'type' => 'resource_locator',
                    'mandatory' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.rlp.part',
                            'priority' => 1
                        )
                    )
                ),
                'article' => array(
                    'name' => 'article',
                    'type' => 'text_area',
                    'mandatory' => false,
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.title',
                            'priority' => 5
                        )
                    )
                ),
                'pages' => array(
                    'name' => 'pages',
                    'type' => 'smart_content_selection',
                    'mandatory' => false,
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.title'
                        )
                    )
                ),
                'images' => array(
                    'name' => 'images',
                    'type' => 'image_selection',
                    'minOccurs' => 0,
                    'maxOccurs' => 2,
                    'params' => array(
                        array(
                            'name' => 'minLinks',
                            'value' => 1
                        ),
                        array(
                            'name' => 'maxLinks',
                            'value' => 10
                        )
                    )

                )
            )
        );

        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__ . '/../../../Resources/DataFixtures/Template/template.xml');
        $this->assertEquals($template, $result);
    }

    /**
     * @expectedException \Sulu\Component\Content\Template\Exception\InvalidArgumentException
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
                    'title' => 'properties.title',
                    'type' => 'text_line',
                    'mandatory' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.node.name'
                        ),
                        array(
                            'name' => 'sulu.node.title',
                            'priority' => 10
                        )
                    )
                ),
                'url' => array(
                    'name' => 'url',
                    'title' => 'properties.url',
                    'type' => 'resource_locator',
                    'mandatory' => true,
                    'tags' => array(
                        array(
                            'name' => 'sulu.rlp.part',
                            'priority' => 1
                        )
                    )
                ),
                'article' => array(
                    'name' => 'article',
                    'type' => 'text_editor',
                    'mandatory' => true
                ),
                'block1' => array(
                    'name' => 'block1',
                    'title' => 'properties.block1',
                    'minOccurs' => '2',
                    'maxOccurs' => '10',
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
                    'properties' => array(
                        'title1.1' => array(
                            'name' => 'title1.1',
                            'type' => 'text_line',
                            'mandatory' => true
                        ),
                        'article1.1' => array(
                            'name' => 'article1.1',
                            'type' => 'text_area',
                            'mandatory' => true,
                            'minOccurs' => 2
                        ),
                        'block1.1' => array(
                            'name' => 'block1.1',
                            'type' => 'block',
                            'properties' => array(
                                'block1.1.1' => array(
                                    'name' => 'block1.1.1',
                                    'type' => 'block',
                                    'properties' => array(
                                        'article1.1.1' => array(
                                            'name' => 'article1.1.1',
                                            'title' => 'properties.title1',
                                            'type' => 'text_area',
                                            'mandatory' => true,
                                            'minOccurs' => 2,
                                            'tags' => array(
                                                array(
                                                    'name' => 'sulu.node.title',
                                                    'priority' => 5
                                                )
                                            ),
                                        ),
                                        'article2.1.2' => array(
                                            'name' => 'article2.1.2',
                                            'type' => 'text_area',
                                            'mandatory' => true,
                                            'minOccurs' => 2
                                        ),
                                        'block1.1.3' => array(
                                            'name' => 'block1.1.3',
                                            'type' => 'block',
                                            'properties' => array(
                                                'article1.1.3.1' => array(
                                                    'name' => 'article1.1.3.1',
                                                    'type' => 'text_area',
                                                    'mandatory' => true,
                                                    'minOccurs' => 2
                                                )
                                            )
                                        )
                                    )
                                ),
                                'block1.1.2' => array(
                                    'name' => 'block1.1.2',
                                    'type' => 'block',
                                    'properties' => array(
                                        'article1.1.2.1' => array(
                                            'name' => 'article1.1.2.1',
                                            'type' => 'text_area',
                                            'mandatory' => true,
                                            'minOccurs' => '2'
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
                    'mandatory' => true
                ),
            )
        );

        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__ . '/../../../Resources/DataFixtures/Template/template_block.xml');
        $this->assertEquals($template, $result);
    }

    public function testDuplicatedPriority()
    {
        $this->setExpectedException('\Sulu\Component\Content\Template\Exception\InvalidXmlException','The given XML is invalid! Priority 10 of tag sulu.node.title exists duplicated');
        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__ . '/../../../Resources/DataFixtures/Template/template_duplicated_priority.xml');
    }

}

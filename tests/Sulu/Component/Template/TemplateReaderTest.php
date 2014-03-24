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
use Sulu\Component\Content\Template\Exception\InvalidArgumentException;
use Sulu\Component\Content\Template\Exception\InvalidXmlException;

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
                    'type' => 'text_line',
                    'mandatory' => true
                ),
                'url' => array(
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'mandatory' => true
                ),
                'article' => array(
                    'name' => 'article',
                    'type' => 'text_area',
                    'mandatory' => false
                ),
                'pages' => array(
                    'name' => 'pages',
                    'type' => 'smart_content_selection',
                    'mandatory' => false
                ),
                'images' => array(
                    'name' => 'images',
                    'type' => 'image_selection',
                    'minOccurs' => 0,
                    'maxOccurs' => 2,
                    'params' => array(
                        'minLinks' => 1,
                        'maxLinks' => 10
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
        $templateReader->load('');
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
            'cacheLifetime' => 4800,
            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'text_line',
                    'mandatory' => true
                ),
                'url' => array(
                    'name' => 'url',
                    'type' => 'resource_locator',
                    'mandatory' => true
                ),
                'article' => array(
                    'name' => 'article',
                    'type' => 'text_editor',
                    'mandatory' => true
                ),
                'block1' => array(
                    'name' => 'block1',
                    'minOccurs' => '2',
                    'maxOccurs' => '10',
                    'mandatory' => true,
                    'type' => 'block',
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
                                            'type' => 'text_area',
                                            'mandatory' => true,
                                            'minOccurs' => 2
                                        ),
                                        'article1.1.2' => array(
                                            'name' => 'article1.1.2',
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
                )
            )
        );

        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__ . '/../../../Resources/DataFixtures/Template/template_block.xml');
        $this->assertEquals($template, $result);
    }

}

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
use Sulu\Component\Content\Template\Exceptions\InvalidArgumentException;
use Sulu\Component\Content\Template\Exceptions\InvalidXmlException;

class TemplateReaderTest extends \PHPUnit_Framework_TestCase
{


    public function setUp()
    {

    }

    public function tearDown()
    {

    }

    public function testReadTemplate()
    {
        $template = array(
            'key' => 'overview',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => 2400,

            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'textLine',
                    'mandatory' => true
                ),
                'url' => array(
                    'name' => 'url',
                    'type' => 'resourceLocator',
                    'mandatory' => true
                ),
                'article' => array(
                    'name' => 'article',
                    'type' => 'textArea',
                    'mandatory' => false
                ),
                'pages' => array(
                    'name' => 'pages',
                    'type' => 'smartContentSelection',
                    'mandatory' => false
                ),
                'images' => array(
                    'name' => 'images',
                    'type' => 'imageSelection',
                    'minOccurs'=> 0,
                    'maxOccurs'=> 2,
                    'params' => array(
                        'minLinks' => 1,
                        'maxLinks' => 10
                    )

                )
            )
        );

        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__."/../../../Resources/Datafixtures/Template/template.xml");
        $this->assertEquals($template, $result);
    }

    /**
     * @expectedException \Sulu\Component\Content\Template\Exceptions\InvalidArgumentException
     */
    public function testReadTypesInvalidPath()
    {
        $templateReader = new TemplateReader();
        $templateReader->load("");
    }

    public function testReadTypesEmptyProperties()
    {
        $template = array(
            'key' => 'overview',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifetime' => 2400,

            'properties' => array()
        );

        $templateReader = new TemplateReader();
        $result = $templateReader->load(__DIR__."/../../../Resources/Datafixtures/Template/template_missing_properties.xml");
        $this->assertEquals($template, $result);
    }

    /**
     * @expectedException \Sulu\Component\Content\Template\Exceptions\InvalidXmlException
     */
    public function testReadTypesMissingMandatory()
    {
        $templateReader = new TemplateReader();
        $templateReader->load(__DIR__."/../../../Resources/Datafixtures/Template/template_missing_mandatory.xml");
    }


}

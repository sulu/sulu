<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use Jackalope\Session;
use PHPCR\Util\NodeHelper;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManager;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Template\TemplateReader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyInterface;

class StructureMangerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoaderInterface
     */
    public $loader;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    public function setUp()
    {
        $cacheDir = __DIR__ . '/../../../../Resources/cache';
        $cacheFile = $cacheDir . '/Template_Structure_template.php';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir);
        } else {
            if (is_file($cacheFile)) {
                unlink($cacheFile);
            }
        }

        $this->loader = new TemplateReader();
        $this->structureManager = new StructureManager($this->loader,
            array(
                'template_dir' => __DIR__ . '/../../../../Resources/DataFixtures/Template',
                'cache_dir' => $cacheDir
            ));
    }

    public function tearDown()
    {
        if (file_exists(__DIR__ . '/../../../../Resources/cache/TemplateStructureCache.php')) {
            unlink(__DIR__ . '/../../../../Resources/cache/TemplateStructureCache.php');
        }

        if (file_exists(__DIR__ . '/../../../../Resources/cache/Template_blockStructureCache.php')) {
            unlink(__DIR__ . '/../../../../Resources/cache/Template_blockStructureCache.php');
        }
    }

    public function testGetStructure()
    {
        /** @var StructureInterface $structure */
        $structure = $this->structureManager->getStructure('template');

        // should implement interface
        $this->assertInstanceOf('\Sulu\Component\Content\StructureInterface', $structure);

        // check properties
        $this->assertEquals('template', $structure->getKey());
        $this->assertEquals('page.html.twig', $structure->getView());
        $this->assertEquals('SuluContentBundle:Default:index', $structure->getController());
        $this->assertEquals(2400, $structure->getCacheLifeTime());

        // check count of properties
        $properties = $structure->getProperties();
        $this->assertEquals(5, sizeof($properties));

        // check title
        $this->assertArrayHasKey('title', $properties);
        $property = $structure->getProperty('title');
        $this->assertEquals('title', $property->getName());
        $this->assertEquals('text_line', $property->getContentTypeName());
        $this->assertEquals(true, $property->isMandatory());
        $this->assertEquals(false, $property->isMultilingual());
        $this->assertEquals(1, $property->getMaxOccurs());
        $this->assertEquals(1, $property->getMinOccurs());
        $this->assertEquals(null, $property->getValue());
        $this->assertEquals(
            array(),
            $property->getParams()
        );

        // check url
        $this->assertArrayHasKey('url', $properties);
        $property = $structure->getProperty('url');
        $this->assertEquals('url', $property->getName());
        $this->assertEquals('resource_locator', $property->getContentTypeName());
        $this->assertEquals(true, $property->isMandatory());
        $this->assertEquals(false, $property->isMultilingual());
        $this->assertEquals(1, $property->getMaxOccurs());
        $this->assertEquals(1, $property->getMinOccurs());
        $this->assertEquals(null, $property->getValue());
        $this->assertEquals(
            array(),
            $property->getParams()
        );

        // check article
        $this->assertArrayHasKey('article', $properties);
        $property = $structure->getProperty('article');
        $this->assertEquals('article', $property->getName());
        $this->assertEquals('text_area', $property->getContentTypeName());
        $this->assertEquals(false, $property->isMandatory());
        $this->assertEquals(false, $property->isMultilingual());
        $this->assertEquals(1, $property->getMaxOccurs());
        $this->assertEquals(1, $property->getMinOccurs());
        $this->assertEquals(null, $property->getValue());
        $this->assertEquals(
            array(),
            $property->getParams()
        );

        // check pages
        $this->assertArrayHasKey('pages', $properties);
        $property = $structure->getProperty('pages');
        $this->assertEquals('pages', $property->getName());
        $this->assertEquals('smart_content_selection', $property->getContentTypeName());
        $this->assertEquals(false, $property->isMandatory());
        $this->assertEquals(false, $property->isMultilingual());
        $this->assertEquals(1, $property->getMaxOccurs());
        $this->assertEquals(1, $property->getMinOccurs());
        $this->assertEquals(null, $property->getValue());
        $this->assertEquals(
            array(),
            $property->getParams()
        );

        // check images
        $this->assertArrayHasKey('images', $properties);
        $property = $structure->getProperty('images');
        $this->assertEquals('images', $property->getName());
        $this->assertEquals('image_selection', $property->getContentTypeName());
        $this->assertEquals(false, $property->isMandatory());
        $this->assertEquals(false, $property->isMultilingual());
        $this->assertEquals(2, $property->getMaxOccurs());
        $this->assertEquals(0, $property->getMinOccurs());
        $this->assertEquals(null, $property->getValue());
        $this->assertEquals(
            array(
                'minLinks' => '1',
                'maxLinks' => '10'
            ),
            $property->getParams()
        );
    }


    public function testGetBlockStructure()
    {
        /** @var StructureInterface $structure */
        $structure = $this->structureManager->getStructure('template_block');

        // should implement interface
        $this->assertInstanceOf('\Sulu\Component\Content\StructureInterface', $structure);

        // check properties
        $this->assertEquals('complex', $structure->getKey());
        $this->assertEquals('ClientWebsiteBundle:Website:complex.html.twig', $structure->getView());
        $this->assertEquals('SuluWebsiteBundle:Default:index', $structure->getController());
        $this->assertEquals(4800, $structure->getCacheLifeTime());

        // check count of properties
        $properties = $structure->getProperties();
        $this->assertEquals(5, sizeof($properties));

        // check title
        $this->assertArrayHasKey('title', $properties);
        $property = $structure->getProperty('title');
        $this->assertEquals('title', $property->getName());
        $this->assertEquals('text_line', $property->getContentTypeName());
        $this->assertEquals(true, $property->isMandatory());
        $this->assertEquals(false, $property->isMultilingual());
        $this->assertEquals(1, $property->getMaxOccurs());
        $this->assertEquals(1, $property->getMinOccurs());
        $this->assertEquals(null, $property->getValue());
        $this->assertEquals(
            array(),
            $property->getParams()
        );

        // check url
        $this->assertArrayHasKey('url', $properties);
        $property = $structure->getProperty('url');
        $this->assertEquals('url', $property->getName());
        $this->assertEquals('resource_locator', $property->getContentTypeName());
        $this->assertEquals(true, $property->isMandatory());
        $this->assertEquals(false, $property->isMultilingual());
        $this->assertEquals(1, $property->getMaxOccurs());
        $this->assertEquals(1, $property->getMinOccurs());
        $this->assertEquals(null, $property->getValue());
        $this->assertEquals(
            array(),
            $property->getParams()
        );

        // check article
        $this->assertArrayHasKey('article', $properties);
        $property = $structure->getProperty('article');
        $this->assertEquals('article', $property->getName());
        $this->assertEquals('text_editor', $property->getContentTypeName());
        $this->assertEquals(true, $property->isMandatory());
        $this->assertEquals(false, $property->isMultilingual());
        $this->assertEquals(1, $property->getMaxOccurs());
        $this->assertEquals(1, $property->getMinOccurs());
        $this->assertEquals(null, $property->getValue());
        $this->assertEquals(
            array(),
            $property->getParams()
        );

        // check blog
        $this->assertArrayHasKey('blog', $properties);
        $property = $structure->getProperty('blog');
        $this->assertEquals('blog', $property->getName());
        $this->assertEquals('text_editor', $property->getContentTypeName());
        $this->assertEquals(true, $property->isMandatory());
        $this->assertEquals(false, $property->isMultilingual());
        $this->assertEquals(1, $property->getMaxOccurs());
        $this->assertEquals(1, $property->getMinOccurs());
        $this->assertEquals(null, $property->getValue());
        $this->assertEquals(
            array(),
            $property->getParams()
        );

        // check block
        $this->assertArrayHasKey('block1', $properties);

        $this->assertTrue($structure->getProperty('block1') instanceof BlockPropertyInterface);

        /** @var BlockPropertyInterface $block1 */
        $block1 = $structure->getProperty('block1');

        $this->assertEquals('block1', $block1->getName());
        $this->assertEquals(2, $block1->getMinOccurs());
        $this->assertEquals(10, $block1->getMaxOccurs());
        $this->assertEquals(true, $block1->isMandatory());
        $this->assertEquals(false, $block1->isMultilingual());
        $this->assertEquals('block', $block1->getContentTypeName());

        $subproperties = $block1->getSubProperties();
        $this->assertEquals(3, sizeof($subproperties));

        $title11 = $subproperties[0];
        $this->assertEquals('title1.1', $title11->getName());
        $this->assertEquals('text_line', $title11->getContentTypeName());
        $this->assertEquals(true, $title11->isMandatory());
        $this->assertEquals(false, $title11->isMultilingual());
        $this->assertEquals(1, $title11->getMaxOccurs());
        $this->assertEquals(1, $title11->getMinOccurs());
        $this->assertEquals(null, $title11->getValue());
        $this->assertEquals(
            array(),
            $title11->getParams()
        );


        $article11 = $subproperties[1];
        $this->assertEquals('article1.1', $article11->getName());
        $this->assertEquals('text_area', $article11->getContentTypeName());
        $this->assertEquals(true, $article11->isMandatory());
        $this->assertEquals(false, $article11->isMultilingual());
        $this->assertEquals(1, $article11->getMaxOccurs());
        $this->assertEquals(2, $article11->getMinOccurs());
        $this->assertEquals(null, $article11->getValue());
        $this->assertEquals(
            array(),
            $article11->getParams()
        );

        $block11 = $subproperties[2];
        $this->assertEquals('block1.1', $block11->getName());
        $this->assertEquals(1, $block11->getMinOccurs());
        $this->assertEquals(1, $block11->getMaxOccurs());
        $this->assertEquals(false, $block11->isMandatory());
        $this->assertEquals(false, $block11->isMultilingual());
        $this->assertEquals('block', $block11->getContentTypeName());

        $subproperties11 = $block11->getSubProperties();
        $this->assertEquals(2, sizeof($subproperties11));

        $block111 = $subproperties11[0];
        $this->assertEquals('block1.1.1', $block111->getName());
        $this->assertEquals(1, $block111->getMinOccurs());
        $this->assertEquals(1, $block111->getMaxOccurs());
        $this->assertEquals(false, $block111->isMandatory());
        $this->assertEquals(false, $block111->isMultilingual());
        $this->assertEquals('block', $block111->getContentTypeName());

        $subproperties111 = $block111->getSubProperties();
        $this->assertEquals(3, sizeof($subproperties111));

        $article111 = $subproperties111[0];
        $this->assertEquals('article1.1.1', $article111->getName());
        $this->assertEquals('text_area', $article111->getContentTypeName());
        $this->assertEquals(true, $article111->isMandatory());
        $this->assertEquals(false, $article111->isMultilingual());
        $this->assertEquals(1, $article111->getMaxOccurs());
        $this->assertEquals(2, $article111->getMinOccurs());
        $this->assertEquals(null, $article111->getValue());
        $this->assertEquals(
            array(),
            $article111->getParams()
        );

        $article212 = $subproperties111[1];
        $this->assertEquals('article2.1.2', $article212->getName());
        $this->assertEquals('text_area', $article212->getContentTypeName());
        $this->assertEquals(true, $article212->isMandatory());
        $this->assertEquals(false, $article212->isMultilingual());
        $this->assertEquals(1, $article212->getMaxOccurs());
        $this->assertEquals(2, $article212->getMinOccurs());
        $this->assertEquals(null, $article212->getValue());
        $this->assertEquals(
            array(),
            $article212->getParams()
        );

        $block113 = $subproperties111[2];
        $this->assertEquals('block1.1.3', $block113->getName());
        $this->assertEquals(1, $block113->getMinOccurs());
        $this->assertEquals(1, $block113->getMaxOccurs());
        $this->assertEquals(false, $block113->isMandatory());
        $this->assertEquals(false, $block113->isMultilingual());
        $this->assertEquals('block', $block113->getContentTypeName());

        $subproperties113 = $block113->getSubProperties();
        $this->assertEquals(1, sizeof($subproperties113));

        $article1131 = $subproperties113[0];
        $this->assertEquals('article1.1.3.1', $article1131->getName());
        $this->assertEquals('text_area', $article1131->getContentTypeName());
        $this->assertEquals(true, $article1131->isMandatory());
        $this->assertEquals(false, $article1131->isMultilingual());
        $this->assertEquals(1, $article1131->getMaxOccurs());
        $this->assertEquals(2, $article1131->getMinOccurs());
        $this->assertEquals(null, $article1131->getValue());
        $this->assertEquals(
            array(),
            $article212->getParams()
        );

        $block112 = $subproperties11[1];
        $this->assertEquals('block1.1.2', $block112->getName());
        $this->assertEquals(1, $block112->getMinOccurs());
        $this->assertEquals(1, $block112->getMaxOccurs());
        $this->assertEquals(false, $block112->isMandatory());
        $this->assertEquals(false, $block112->isMultilingual());
        $this->assertEquals('block', $block112->getContentTypeName());

        $subproperties112 = $block112->getSubProperties();
        $this->assertEquals(1, sizeof($subproperties112));

        $article1121 = $subproperties112[0];
        $this->assertEquals('article1.1.2.1', $article1121->getName());
        $this->assertEquals('text_area', $article1121->getContentTypeName());
        $this->assertEquals(true, $article1121->isMandatory());
        $this->assertEquals(false, $article1121->isMultilingual());
        $this->assertEquals(1, $article1121->getMaxOccurs());
        $this->assertEquals(2, $article1121->getMinOccurs());
        $this->assertEquals(null, $article1121->getValue());
        $this->assertEquals(
            array(),
            $article1121->getParams()
        );


    }
}

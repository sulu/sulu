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
//        if (file_exists(__DIR__ . '/../../../../Resources/cache/TemplateStructureCache.php')) {
//            unlink(__DIR__ . '/../../../../Resources/cache/TemplateStructureCache.php');
//        }
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

        // check iamges
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

    }
}

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

use Prophecy\Argument;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Metadata\Loader\XmlLoader;
use Sulu\Component\HttpCache\CacheLifetimeResolverInterface;

class XmlLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmlLoader
     */
    private $loader;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    public function setUp()
    {
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $this->loader = new XmlLoader($this->contentTypeManager->reveal(), $this->cacheLifetimeResolver->reveal());
    }

    public function testLoadTemplate()
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);
        $this->contentTypeManager->has('smart_content_selection')->willReturn(true);
        $this->contentTypeManager->has('image_selection')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template.xml');

        $this->assertFalse($result->internal);
    }

    public function testLoadInternalTemplate()
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);
        $this->contentTypeManager->has('smart_content_selection')->willReturn(true);
        $this->contentTypeManager->has('image_selection')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template_load_internal.xml');

        $this->assertTrue($result->internal);
    }

    public function testLoadBlockMetaTitles()
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('text_editor')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template_block_types.xml');

        $blockTypes = $result->getProperty('block1')->getComponents();

        $this->assertEquals('Default DE', $blockTypes[0]->getTitle('de'));
        $this->assertEquals('Default EN', $blockTypes[0]->getTitle('en'));
        $this->assertEquals('Test DE', $blockTypes[1]->getTitle('de'));
        $this->assertEquals('Test EN', $blockTypes[1]->getTitle('en'));
        $this->assertEquals('Info Block1 DE', $blockTypes[1]->getDescription('de'));
        $this->assertEquals('Info Block1 EN', $blockTypes[1]->getDescription('en'));
    }

    public function testLoadBlockTypeWithoutMeta()
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template_block_type_without_meta.xml');

        $this->assertCount(1, $result->getProperty('block1')->getComponents());
    }

    public function testLoadInvalidIgnore()
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('test')->willReturn(false);
        $result = $this->load('template_with_invalid_ignore.xml');

        $properties = $result->getProperties();

        $this->assertCount(2, $properties);
        $this->assertEquals('title', $properties['title']->getName());
        $this->assertEquals('url', $properties['url']->getName());
    }

    public function testLoadInvalidWithoutIgnore()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('test')->willReturn(false);
        $this->contentTypeManager->getAll()->willReturn(['text_line' => [], 'resource_locator' => []]);
        $result = $this->load('template_without_invalid_ignore.xml');
    }

    private function load($name)
    {
        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Page/' . $name
        );

        return $result;
    }

    private function getResourceDirectory()
    {
        return __DIR__ . '/../../../../../../../../tests/Resources';
    }
}

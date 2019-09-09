<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Metadata\Loader;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Metadata\Loader\Exception\RequiredPropertyNameNotFoundException;
use Sulu\Component\Content\Metadata\Loader\Exception\RequiredTagNotFoundException;
use Sulu\Component\Content\Metadata\Loader\StructureXmlLoader;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;
use Symfony\Component\Translation\TranslatorInterface;

class StructureXmlLoaderTest extends TestCase
{
    private $requiredTagNames = [
        'page' => ['sulu.rlp'],
        'home' => ['sulu.rlp'],
        'snippet' => [],
    ];

    private $requiredPropertyNames = [
        'page' => ['title'],
        'home' => ['title'],
        'snippet' => ['title'],
    ];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var StructureXmlLoader
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

    public function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $propertiesXmlParser = new PropertiesXmlParser(
            $this->translator->reveal(),
            ['en' => 'en', 'de' => 'de', 'fr' => 'fr', 'nl' => 'nl']
        );
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $schemaXmlParser = new SchemaXmlParser();

        $this->loader = new StructureXmlLoader(
            $this->cacheLifetimeResolver->reveal(),
            $propertiesXmlParser,
            $schemaXmlParser,
            $this->contentTypeManager->reveal(),
            $this->requiredPropertyNames,
            $this->requiredTagNames
        );
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

        $this->assertFalse($result->isInternal());
        $this->assertNull($result->getSchema());
    }

    public function testLoadTemplateWithSchema()
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);
        $this->contentTypeManager->has('smart_content_selection')->willReturn(true);
        $this->contentTypeManager->has('image_selection')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template_with_schema.xml');

        $this->assertEquals(
            [
                'required' => [],
                'anyOf' => [
                    [
                        'required' => [
                            'article1',
                        ],
                    ],
                    [
                        'required' => [
                            'article2',
                        ],
                    ],
                ],
            ],
            $result->getSchema()->toJsonSchema()
        );
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

        $this->assertTrue($result->isInternal());
    }

    public function testLoadBlockMetaTitles()
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('text_editor')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);
        $this->contentTypeManager->has('block')->willReturn(true);

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
        $this->contentTypeManager->has('block')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template_block_type_without_meta.xml');

        $this->assertCount(1, $result->getProperty('block1')->getComponents());
    }

    public function testLoadNestedSections()
    {
        $result = $this->load('template_with_nested_sections.xml');

        $this->assertEquals(['title', 'test21', 'test221'], array_keys($result->getProperties()));
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
        $this->expectException(\InvalidArgumentException::class);

        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('test')->willReturn(false);
        $this->contentTypeManager->getAll()->willReturn(['text_line', 'resource_locator']);
        $this->load('template_without_invalid_ignore.xml');
    }

    public function testLoadRequiredProperty()
    {
        $this->expectException(RequiredPropertyNameNotFoundException::class);

        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->getAll()->willReturn(['text_line', 'resource_locator']);
        $this->load('template_without_title.xml');
    }

    public function testLoadRequiredTag()
    {
        $this->expectException(RequiredTagNotFoundException::class);

        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->getAll()->willReturn(['text_line', 'resource_locator']);
        $this->load('template_without_sulu_rlp.xml');
    }

    public function testLoadRequiredPropertyOtherType()
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->getAll()->willReturn(['text_line', 'resource_locator']);
        $result = $this->load('template_without_title.xml', 'test');

        $properties = $result->getProperties();

        $this->assertCount(2, $properties);
    }

    public function testLoadRequiredTagOtherType()
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->getAll()->willReturn(['text_line', 'resource_locator']);
        $result = $this->load('template_without_sulu_rlp.xml', 'test');

        $properties = $result->getProperties();

        $this->assertCount(2, $properties);
    }

    private function load($name, $type = null)
    {
        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Page/' . $name,
            $type
        );

        return $result;
    }

    private function getResourceDirectory()
    {
        return __DIR__ . '/../../../../../../../../tests/Resources';
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Metadata\Factory;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Metadata\Factory\Exception\DocumentTypeNotFoundException;
use Sulu\Component\Content\Metadata\Factory\Exception\StructureTypeNotFoundException;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\Loader\StructureXmlLoader;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

class StructureMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var string
     */
    private $somethingMappingFile;

    /**
     * @var string
     */
    private $defaultMappingFile;

    /**
     * @var string
     */
    private $apostropheMappingFile;

    /**
     * @var string
     */
    private $overriddenDefaultMappingFile;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    /**
     * @var ObjectProphecy<LoaderInterface>
     */
    private $loader;

    /**
     * @var StructureMetadataFactory
     */
    private $factory;

    public function setUp(): void
    {
        parent::setUp();
        $this->cacheDir = __DIR__ . \DIRECTORY_SEPARATOR . 'data' . \DIRECTORY_SEPARATOR . 'cache';
        $this->apostropheMappingFile = \implode(\DIRECTORY_SEPARATOR, [__DIR__, 'data', 'apostrophe', 'apostrophe.xml']);
        $this->somethingMappingFile = \implode(\DIRECTORY_SEPARATOR, [__DIR__, 'data', 'page', 'something.xml']);
        $this->defaultMappingFile = \implode(\DIRECTORY_SEPARATOR, [__DIR__, 'data', 'other', 'default.xml']);
        $this->overriddenDefaultMappingFile = \implode(\DIRECTORY_SEPARATOR, [__DIR__, 'data', 'page', 'default.xml']);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->loader = $this->prophesize(LoaderInterface::class);

        $this->factory = new StructureMetadataFactory(
            $this->loader->reveal(),
            [
                'page' => [
                    [
                        'type' => 'page',
                        'path' => __DIR__ . \DIRECTORY_SEPARATOR . 'data' . \DIRECTORY_SEPARATOR . 'page',
                    ],
                    [
                        'type' => 'page',
                        'path' => __DIR__ . \DIRECTORY_SEPARATOR . 'data' . \DIRECTORY_SEPARATOR . 'other',
                    ],
                ],
                'snoopet' => [
                    [
                        'type' => 'page',
                        'path' => __DIR__ . \DIRECTORY_SEPARATOR . 'data' . \DIRECTORY_SEPARATOR . 'snoops',
                    ],
                ],
            ],
            [
                'page' => 'something',
            ],
            $this->cacheDir
        );
    }

    public function tearDown(): void
    {
        $this->cleanUp();
    }

    /**
     * It should throw an exception if a non existing document alias is given.
     */
    public function testGetStructureBadType(): void
    {
        $this->expectExceptionMessage('Structure path for document type "non_existing" is not mapped. Mapped structure types: "page');
        $this->expectException(DocumentTypeNotFoundException::class);
        $this->factory->getStructureMetadata('non_existing', 'foo');
    }

    /**
     * It should throw an exception if a non existing structure type is given.
     */
    public function testGetStructureNonExisting(): void
    {
        $this->expectExceptionMessage('Could not load structure type "overview_not_existing" for document type "page", looked in "');
        $this->expectException(StructureTypeNotFoundException::class);
        $this->factory->getStructureMetadata('page', 'overview_not_existing');
    }

    /**
     * It should use a default structure type if null is given.
     */
    public function testGetStructureDefault(): void
    {
        $somethingStructure = new StructureMetadata();
        $this->loader->load($this->somethingMappingFile, 'page')->willReturn($somethingStructure);
        $this->loader->load($this->somethingMappingFile, 'page')->shouldBeCalledTimes(1);

        $this->factory->getStructureMetadata('page');
    }

    public function testGetStructureTypes(): void
    {
        $this->assertEquals(['page', 'snoopet'], $this->factory->getStructureTypes());
    }

    /**
     * It should cache the result.
     */
    public function testCacheResult(): void
    {
        $somethingStructure = new StructureMetadata();
        $this->loader->load($this->somethingMappingFile, 'page')->willReturn($somethingStructure);
        $this->loader->load($this->somethingMappingFile, 'page')->shouldBeCalledTimes(1);

        $this->factory->getStructureMetadata('page');
        $this->factory->getStructureMetadata('page');
        $this->factory->getStructureMetadata('page');
    }

    public function testGetStructureDefaultNoSet(): void
    {
        $this->assertNull($this->factory->getStructureMetadata('snoopet'));
    }

    public function testGetStructureWithApostrophe(): void
    {
        $contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $contentTypeManager->has(Argument::any())->willReturn(true);

        $cacheLifeTimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $cacheLifeTimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $propertiesXmlLoader = new PropertiesXmlParser(
            $this->translator->reveal(),
            []
        );
        $schemaXmlLoader = new SchemaXmlParser();

        $xmlLoader = new StructureXmlLoader(
            $cacheLifeTimeResolver->reveal(),
            $propertiesXmlLoader,
            $schemaXmlLoader,
            $contentTypeManager->reveal(),
            [],
            [],
            [],
            $this->translator->reveal(),
        );

        $loadResult = $xmlLoader->load($this->apostropheMappingFile, 'page');

        $this->loader->load(Argument::any(), 'page')->willReturn($loadResult);
        $this->assertNotNull($this->factory->getStructureMetadata('page'));
    }

    /**
     * Test that the structure is loaded and that the loader
     * is only called once (that the subsequent fetches do not reload
     * the metadata from the source).
     */
    public function testGetStructure(): void
    {
        $somethingStructure = new StructureMetadata();
        $this->loader->load($this->somethingMappingFile, 'page')->willReturn($somethingStructure);
        $this->loader->load($this->somethingMappingFile, 'page')->shouldBeCalledTimes(1);

        $structure = $this->factory->getStructureMetadata('page', 'something');

        $this->assertEquals($somethingStructure, $structure);

        $this->factory->getStructureMetadata('page', 'something');
        $this->factory->getStructureMetadata('page', 'something');
    }

    /**
     * Test that the structure is searched in the right direction of the configured folder.
     */
    public function testDirection(): void
    {
        $somethingStructure = new StructureMetadata();
        $this->loader->load($this->defaultMappingFile, 'page')
            ->willReturn($somethingStructure)
            ->shouldBeCalled();

        $this->factory->getStructureMetadata('page', 'default');
    }

    /**
     * It returns all structures that are available.
     */
    public function testGetStructures(): void
    {
        $somethingStructure = new StructureMetadata();
        $defaultStructure = new StructureMetadata();
        $this->loader->load($this->somethingMappingFile, 'page')->willReturn($somethingStructure);
        $this->loader->load($this->defaultMappingFile, 'page')->willReturn($defaultStructure);
        $this->loader->load($this->somethingMappingFile, 'page')->shouldBeCalledTimes(1);
        $this->loader->load($this->defaultMappingFile, 'page')->shouldBeCalledTimes(1);

        $structures = $this->factory->getStructures('page');
        $this->assertCount(3, $structures);
        $this->assertEquals($defaultStructure, $structures[0]);
        $this->assertEquals($somethingStructure, $structures[1]);
        $this->assertEquals($defaultStructure, $structures[2]);
    }

    private function cleanUp()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->cacheDir);
    }
}

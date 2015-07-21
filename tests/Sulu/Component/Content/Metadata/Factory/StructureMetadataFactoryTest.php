<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Factory;

use Sulu\Component\Content\Metadata\StructureMetadata;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\Loader\LoaderInterface;

class StructureMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var string
     */
    private $mappingFile;

    /**
     * @var StructureMetadata
     */
    private $structure;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var StructureMetadataFactory
     */
    private $factory;

    public function setUp()
    {
        parent::setUp();
        $this->cacheDir = __DIR__ . '/data/cache';
        $this->mappingFile = __DIR__ . '/data/page/something.xml';

        $this->structure = $this->prophesize('Sulu\Component\Content\Metadata\StructureMetadata');
        $this->loader = $this->prophesize('Symfony\Component\Config\Loader\LoaderInterface');
        $this->factory = new StructureMetadataFactory(
            $this->loader->reveal(),
            [
                'page' => [
                    [
                        'type' => 'page',
                        'path' => __DIR__ . '/data/page',
                    ],
                ],
                'snoopet' => [
                    [
                        'type' => 'page',
                        'path' => __DIR__ . '/data/snoops',
                    ],
                ],
            ],
            [
                'page' => 'something',
            ],
            $this->cacheDir
        );
    }

    public function tearDown()
    {
        $this->cleanUp();
    }

    /**
     * It should throw an exception if a non existing document alias is given.
     *
     * @expectedException Sulu\Component\Content\Metadata\Factory\Exception\DocumentTypeNotFoundException
     * @expectedExceptionMessage Structure path for document type "non_existing" is not mapped. Mapped structure types: "page
     */
    public function testGetStructureBadType()
    {
        $this->factory->getStructureMetadata('non_existing', 'foo');
    }

    /**
     * It should throw an exception if a non existing structure type is given.
     *
     * @expectedException Sulu\Component\Content\Metadata\Factory\Exception\StructureTypeNotFoundException
     * @expectedExceptionMessage Could not load structure type "overview_not_existing" for document type "page", looked in "
     */
    public function testGetStructureNonExisting()
    {
        $this->factory->getStructureMetadata('page', 'overview_not_existing');
    }

    /**
     * It should use a default structure type if null is given.
     */
    public function testGetStructureDefault()
    {
        $this->loader->load($this->mappingFile, 'page')->willReturn($this->structure->reveal());
        $this->loader->load($this->mappingFile, 'page')->shouldBeCalledTimes(1);

        $this->factory->getStructureMetadata('page');
    }

    /**
     * It should cache the result.
     */
    public function testCacheResult()
    {
        $this->loader->load($this->mappingFile, 'page')->willReturn($this->structure->reveal());
        $this->loader->load($this->mappingFile, 'page')->shouldBeCalledTimes(1);

        $this->factory->getStructureMetadata('page');
        $this->factory->getStructureMetadata('page');
        $this->factory->getStructureMetadata('page');
    }

    /**
     * It should throw an exception if no structure type is given and no default is available.
     *
     * @expectedException RuntimeException
     */
    public function testGetStructureDefaultNoSet()
    {
        $this->factory->getStructureMetadata('snoopet');
    }

    /**
     * Test that the structure is loaded and that the loader
     * is only called once (that the subsequent fetches do not reload
     * the metadata from the source).
     */
    public function testGetStructure()
    {
        $this->loader->load($this->mappingFile, 'page')->willReturn($this->structure->reveal());
        $this->loader->load($this->mappingFile, 'page')->shouldBeCalledTimes(1);

        $structure = $this->factory->getStructureMetadata('page', 'something');

        $this->assertEquals($this->structure->reveal(), $structure);

        $this->factory->getStructureMetadata('page', 'something');
        $this->factory->getStructureMetadata('page', 'something');
    }

    /**
     * It returns all structures that are available.
     */
    public function testGetStructures()
    {
        $this->loader->load($this->mappingFile, 'page')->willReturn($this->structure->reveal());
        $this->loader->load($this->mappingFile, 'page')->shouldBeCalledTimes(1);

        $structures = $this->factory->getStructures('page');
        $this->assertEquals($this->structure->reveal(), $structures[0]);
    }

    private function cleanUp()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->cacheDir);
    }
}

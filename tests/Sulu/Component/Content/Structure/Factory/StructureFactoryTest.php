<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure\Factory;

use Symfony\Component\Filesystem\Filesystem;
use Sulu\Component\Content\Structure\Factory\StructureFactory;

class StructureFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $cacheDir;

    public function setUp()
    {
        parent::setUp();
        $this->cacheDir = __DIR__ . '/data/cache';
        $this->mappingFile = __DIR__ . '/data/page/something.xml';

        $this->structure = $this->prophesize('Sulu\Component\Content\Compat\Structure');
        $this->loader = $this->prophesize('Symfony\Component\Config\Loader\LoaderInterface');
        $this->factory = new StructureFactory(
            $this->loader->reveal(),
            array(
                'page' => array(
                    array(
                        'type' => 'page',
                        'internal' => false,
                        'path' => __DIR__ . '/data/page',
                    ),
                ),
                'snoopet' => array(
                    array(
                        'type' => 'page',
                        'internal' => false,
                        'path' => __DIR__ . '/data/snoops',
                    ),
                ),
            ),
            array(
                'page' => 'something',
            ),
            $this->cacheDir
        );
    }

    public function tearDown()
    {
        $this->cleanUp();
    }

    /**
     * It should throw an exception if a non existing document alias is given
     *
     * @expectedException Sulu\Component\Content\Structure\Factory\Exception\DocumentTypeNotFoundException
     * @expectedExceptionMessage Structure path for document type "non_existing" is not mapped. Mapped structure types: "page
     */
    public function testGetStructureBadType()
    {
        $this->factory->getStructure('non_existing', 'foo');
    }

    /**
     * It should throw an exception if a non existing structure type is given
     *
     * @expectedException Sulu\Component\Content\Structure\Factory\Exception\StructureTypeNotFoundException
     * @expectedExceptionMessage Could not load structure type "overview_not_existing" for document type "page", looked in "
     */
    public function testGetStructureNonExisting()
    {
        $this->factory->getStructure('page', 'overview_not_existing');
    }

    /**
     * It should use a default structure type if null is given
     */
    public function testGetStructureDefault()
    {
        $this->loader->load($this->mappingFile, 'page')->willReturn($this->structure->reveal());
        $this->loader->load($this->mappingFile, 'page')->shouldBeCalledTimes(1);

        $this->factory->getStructure('page');
    }

    /**
     * It should cache the result
     */
    public function testCacheResult()
    {
        $this->loader->load($this->mappingFile, 'page')->willReturn($this->structure->reveal());
        $this->loader->load($this->mappingFile, 'page')->shouldBeCalledTimes(1);

        $this->factory->getStructure('page');
        $this->factory->getStructure('page');
        $this->factory->getStructure('page');
    }

    /**
     * It should throw an exception if no structure type is given and no default is available
     *
     * @expectedException RuntimeException
     */
    public function testGetStructureDefaultNoSet()
    {
        $this->factory->getStructure('snoopet');
    }

    /**
     * Test that the structure is loaded and that the loader
     * is only called once (that the subsequent fetches do not reload
     * the metadata from the source)
     */
    public function testGetStructure()
    {
        $this->loader->load($this->mappingFile, 'page')->willReturn($this->structure->reveal());
        $this->loader->load($this->mappingFile, 'page')->shouldBeCalledTimes(1);

        $structure = $this->factory->getStructure('page', 'something');

        $this->assertEquals($this->structure->reveal(), $structure);

        $this->factory->getStructure('page', 'something');
        $this->factory->getStructure('page', 'something');
    }

    /**
     * It returns all structures that are available
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

<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Structure\Factory;

use Prophecy\PhpUnit\ProphecyTestCase;
use Symfony\Component\Filesystem\Filesystem;
use DTL\Component\Content\Structure\Factory\StructureFactory;

class StructureFactoryTest extends ProphecyTestCase
{
    private $cacheDir;

    public function setUp()
    {
        parent::setUp();
        $this->cacheDir = __DIR__ . '/data/cache';
        $this->mappingFile = __DIR__ . '/data/page/something.xml';

        $this->structure = $this->prophesize('DTL\Component\Content\Structure\Structure');
        $this->loader = $this->prophesize('Symfony\Component\Config\Loader\LoaderInterface');
        $this->factory = new StructureFactory(
            $this->loader->reveal(),
            array(
                'page' => array(__DIR__ . '/data/page'),
            ),
            $this->cacheDir
        );
    }

    public function tearDown()
    {
        $this->cleanUp();
    }

    /**
     * @expectedException DTL\Component\Content\Structure\Factory\Exception\DocumentTypeNotFoundException
     * @expectedExceptionMessage Structure path for document type "non_existing" is not mapped. Mapped structure types: "page
     */
    public function testGetStructureBadType()
    {
        $this->factory->getStructure('non_existing', 'foo');
    }

    /**
     * @expectedException DTL\Component\Content\Structure\Factory\Exception\StructureTypeNotFoundException
     * @expectedExceptionMessage Could not load structure type "overview_not_existing" for document type "page", looked in "
     */
    public function testGetStructureNonExisting()
    {
        $this->factory->getStructure('page', 'overview_not_existing');
    }

    /**
     * Test that the structure is loaded and that the loader
     * is only called once (that the subsequent fetches do not reload
     * the metadata from the source)
     */
    public function testGetStructure()
    {
        $this->loader->load($this->mappingFile)->willReturn($this->structure->reveal());
        $this->loader->load($this->mappingFile)->shouldBeCalledTimes(1);

        $structure = $this->factory->getStructure('page', 'something');

        $this->assertEquals($this->structure->reveal(), $structure);

        $this->factory->getStructure('page', 'something');
        $this->factory->getStructure('page', 'something');
    }

    /**
     * It can return a model representation
     */
    public function testGetStructureAsModel()
    {
        $modelRepresentation = new \stdClass;
        $this->loader->load($this->mappingFile)->willReturn($this->structure->reveal());
        $this->structure->transformToModel()->willReturn($modelRepresentation);

        $structure = $this->factory->getStructure('page', 'something', true);
        $this->assertEquals($modelRepresentation, $structure);
    }

    /**
     * It returns all structures that are available
     */
    public function testGetStructures()
    {
        $this->loader->load($this->mappingFile)->willReturn($this->structure->reveal());
        $this->loader->load($this->mappingFile)->shouldBeCalledTimes(1);

        $structures = $this->factory->getStructures('page');
        $this->assertEquals($this->structure->reveal(), $structures[0]);
    }

    private function cleanUp()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->cacheDir);
    }
}

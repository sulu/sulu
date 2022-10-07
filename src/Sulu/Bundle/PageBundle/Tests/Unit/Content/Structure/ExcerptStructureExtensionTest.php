<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Content\Structure;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\PageBundle\Content\Structure\ExcerptStructureExtension;
use Sulu\Bundle\SearchBundle\Search\Factory;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Export\Manager\ExportManagerInterface;
use Sulu\Component\Import\Manager\ImportManagerInterface;

class ExcerptStructureExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testSaveSetLocaleAndWebspace(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getProperties()->willReturn([]);

        $structure->setLanguageCode(null)->willReturn(null);
        $structure->setLanguageCode('de')->shouldBeCalled();

        $structureManager = $this->prophesize(StructureManagerInterface::class);
        $structureManager->getStructure(ExcerptStructureExtension::EXCERPT_EXTENSION_NAME)->willReturn(
            $structure->reveal()
        );

        $contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $factory = $this->prophesize(Factory::class);
        $node = $this->prophesize(NodeInterface::class);
        $exportManager = $this->prophesize(ExportManagerInterface::class);
        $importManager = $this->prophesize(ImportManagerInterface::class);

        $excerptExtension = new ExcerptStructureExtension(
            $structureManager->reveal(),
            $contentTypeManager->reveal(),
            $exportManager->reveal(),
            $importManager->reveal(),
            $factory->reveal()
        );

        $excerptExtension->save($node->reveal(), [], 'sulu_io', 'de');
    }

    public function testLoadSetLocaleAndWebspace(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getProperties()->willReturn([]);

        $structure->setLanguageCode(null)->willReturn(null);
        $structure->setLanguageCode('de')->shouldBeCalled();

        $structureManager = $this->prophesize(StructureManagerInterface::class);
        $structureManager->getStructure(ExcerptStructureExtension::EXCERPT_EXTENSION_NAME)->willReturn(
            $structure->reveal()
        );

        $contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $factory = $this->prophesize(Factory::class);
        $node = $this->prophesize(NodeInterface::class);
        $exportManager = $this->prophesize(ExportManagerInterface::class);
        $importManager = $this->prophesize(ImportManagerInterface::class);

        $excerptExtension = new ExcerptStructureExtension(
            $structureManager->reveal(),
            $contentTypeManager->reveal(),
            $exportManager->reveal(),
            $importManager->reveal(),
            $factory->reveal()
        );

        $excerptExtension->load($node->reveal(), 'sulu_io', 'de');
    }
}

<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Structure;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Content\Structure\ExcerptStructureExtension;
use Sulu\Bundle\SearchBundle\Search\Factory;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;

class ExcerptStructureExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testSaveSetLocaleAndWebspace()
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

        $excerptExtension = new ExcerptStructureExtension(
            $structureManager->reveal(),
            $contentTypeManager->reveal(),
            $factory->reveal()
        );

        $excerptExtension->save($node->reveal(), [], 'sulu_io', 'de');
    }

    public function testLoadSetLocaleAndWebspace()
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

        $excerptExtension = new ExcerptStructureExtension(
            $structureManager->reveal(),
            $contentTypeManager->reveal(),
            $factory->reveal()
        );

        $excerptExtension->load($node->reveal(), 'sulu_io', 'de');
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit\Metadata;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Document\UnknownDocument;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\Metadata\MetadataFactory;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;

class MetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MetadataFactoryInterface>
     */
    private $baseMetadataFactory;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    public function setUp(): void
    {
        $this->baseMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->metadataFactory = new MetadataFactory($this->baseMetadataFactory->reveal());
    }

    public function testGetForPhpcrNodeWithoutMixins(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('jcr:mixinTypes')->willReturn(false);

        $metadata = $this->metadataFactory->getMetadataForPhpcrNode($node->reveal());
        $this->assertEquals(UnknownDocument::class, $metadata->getClass());
    }

    public function testGetForPhpcrNode(): void
    {
        $metadata = $this->prophesize(Metadata::class);
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('jcr:mixinTypes')->willReturn(true);
        $node->getPropertyValue('jcr:mixinTypes')->willReturn(['mix:referenceable', 'sulu:page']);

        $this->baseMetadataFactory->hasMetadataForPhpcrType('mix:referenceable')->willReturn(false);
        $this->baseMetadataFactory->hasMetadataForPhpcrType('sulu:page')->willReturn(true);
        $this->baseMetadataFactory->getMetadataForPhpcrType('sulu:page')->willReturn($metadata->reveal());

        $this->assertSame($metadata->reveal(), $this->metadataFactory->getMetadataForPhpcrNode($node->reveal()));
    }

    public function testGetForPhpcrNodeNoManaged(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('jcr:mixinTypes')->willReturn(true);
        $node->getPropertyValue('jcr:mixinTypes')->willReturn([
        ]);

        $metadata = $this->metadataFactory->getMetadataForPhpcrNode($node->reveal());
        $this->assertNull($metadata->getAlias());
        $this->assertEquals(UnknownDocument::class, $metadata->getClass());
        $this->assertNull($metadata->getPhpcrType());
    }
}

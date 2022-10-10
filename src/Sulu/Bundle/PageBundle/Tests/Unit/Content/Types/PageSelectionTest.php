<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\PageBundle\Content\Types\PageSelection;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;

class PageSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ContentQueryExecutorInterface>
     */
    private $contentQueryExecutor;

    /**
     * @var ObjectProphecy<ContentQueryBuilderInterface>
     */
    private $contentQueryBuilder;

    /**
     * @var ObjectProphecy<LoggerInterface>
     */
    private $logger;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $property;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $referenceStore;

    public function setUp(): void
    {
        $this->contentQueryExecutor = $this->prophesize(ContentQueryExecutorInterface::class);
        $this->contentQueryBuilder = $this->prophesize(ContentQueryBuilderInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);
    }

    public function testWriteWithNoneExistingUUID(): void
    {
        $pageSelection = new PageSelection(
            $this->contentQueryExecutor->reveal(),
            $this->contentQueryBuilder->reveal(), $this->referenceStore->reveal(),
            false
        );

        $node = $this->prophesize(NodeInterface::class);
        $subNode1 = $this->prophesize(NodeInterface::class);
        $subNode2 = $this->prophesize(NodeInterface::class);
        $session = $this->prophesize(SessionInterface::class);

        $node->getIdentifier()->willReturn('1');
        $node->getSession()->willReturn($session->reveal());
        $subNode1->getIdentifier()->willReturn('123-123-123');
        $subNode2->getIdentifier()->willReturn('123-456-789');
        $session->getNodesByIdentifier(['123-123-123', '123-456-789', 'not existing'])
            ->willReturn([$subNode1->reveal(), $subNode2->reveal()]);

        $node->setProperty('property', ['123-123-123', '123-456-789'], PropertyType::REFERENCE)->shouldBeCalled();

        $this->property->getName()->willReturn('property');
        $this->property->getValue()->willReturn(['123-123-123', '123-456-789', 'not existing']);

        $pageSelection->write($node->reveal(), $this->property->reveal(), 1, 'test', 'de', null);
    }

    public function testGetContentData(): void
    {
        $pageSelection = new PageSelection(
            $this->contentQueryExecutor->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->referenceStore->reveal(),
            false,
            ['view' => 64]
        );

        $this->property->getValue()->willReturn(['123-123-123']);
        $this->property->getParams()->willReturn([]);
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getWebspaceKey()->willReturn('default');
        $structure->getLanguageCode()->willReturn('en');
        $this->property->getStructure()->willReturn($structure->reveal());

        $this->contentQueryBuilder->init(['ids' => ['123-123-123'], 'properties' => [], 'published' => true])
            ->shouldBeCalled();
        $this->contentQueryExecutor
             ->execute('default', ['en'], $this->contentQueryBuilder->reveal(), true, -1, null, null, false, 64)
             ->willReturn([['id' => '123-123-123', 'path' => 'phpcr/path/123']]);

        $this->assertSame(
            [['id' => '123-123-123', 'path' => 'phpcr/path/123']],
            $pageSelection->getContentData($this->property->reveal())
        );
    }

    public function testGetContentDataWithUser(): void
    {
        $pageSelection = new PageSelection(
            $this->contentQueryExecutor->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->referenceStore->reveal(),
            false,
            ['view' => 64]
        );

        $this->property->getValue()->willReturn(['123-123-123']);
        $this->property->getParams()->willReturn([]);
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getWebspaceKey()->willReturn('default');
        $structure->getLanguageCode()->willReturn('en');
        $this->property->getStructure()->willReturn($structure->reveal());

        $this->contentQueryBuilder->init(['ids' => ['123-123-123'], 'properties' => [], 'published' => true])
            ->shouldBeCalled();
        $this->contentQueryExecutor
             ->execute(
                 'default',
                 ['en'],
                 $this->contentQueryBuilder->reveal(),
                 true,
                 -1,
                 null,
                 null,
                 false,
                 64
             )
             ->willReturn([]);

        $pageSelection->getContentData($this->property->reveal());
    }

    public function testGetContentDataWithoutPathParameter(): void
    {
        $pageSelection = new PageSelection(
            $this->contentQueryExecutor->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->referenceStore->reveal(),
            false,
            ['view' => 64],
            ['path' => false]
        );

        $this->property->getValue()->willReturn(['123-123-123']);
        $this->property->getParams()->willReturn([]);
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getWebspaceKey()->willReturn('default');
        $structure->getLanguageCode()->willReturn('en');
        $this->property->getStructure()->willReturn($structure->reveal());

        $this->contentQueryBuilder->init(['ids' => ['123-123-123'], 'properties' => [], 'published' => true])
            ->shouldBeCalled();
        $this->contentQueryExecutor
            ->execute('default', ['en'], $this->contentQueryBuilder->reveal(), true, -1, null, null, false, 64)
            ->willReturn([['id' => '123-123-123', 'path' => 'phpcr/path/123']]);

        $this->assertSame(
            [['id' => '123-123-123']],
            $pageSelection->getContentData($this->property->reveal())
        );
    }

    public function testPreResolve(): void
    {
        $pageSelection = new PageSelection(
            $this->contentQueryExecutor->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->referenceStore->reveal(),
            false
        );

        $this->property->getValue()->willReturn(['123-123-123']);
        $this->property->getParams()->willReturn([]);
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getWebspaceKey()->willReturn('default');
        $structure->getLanguageCode()->willReturn('en');
        $this->property->getStructure()->willReturn($structure->reveal());

        $pageSelection->preResolve($this->property->reveal());

        $this->referenceStore->add('123-123-123')->shouldBeCalled();
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\SessionInterface;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ContentBundle\Content\Types\InternalLinks;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;

class InternalLinksTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQueryExecutor;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PropertyInterface
     */
    private $property;

    public function setUp()
    {
        $this->contentQueryExecutor = $this->prophesize(ContentQueryExecutorInterface::class);
        $this->contentQueryBuilder = $this->prophesize(ContentQueryBuilderInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
    }

    public function provideGetReferencedUuids()
    {
        return [
            [
                ['4234-2345-2345-3245', '4321-4321-4321-4321'],
                ['4234-2345-2345-3245', '4321-4321-4321-4321'],
            ],
            [
                [],
                [],
            ],
        ];
    }

    /**
     * @dataProvider provideGetReferencedUuids
     */
    public function testGetReferencedUuids($propertyValue, $expected)
    {
        $internalLinks = new InternalLinks(
            $this->contentQueryExecutor->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->logger->reveal(),
            'some_template.html.twig',
            false
        );

        $this->property->getValue()->willReturn($propertyValue);
        $uuids = $internalLinks->getReferencedUuids($this->property->reveal());
        $this->assertEquals($expected, $uuids);
    }

    public function testWriteWithNoneExistingUUID()
    {
        $internalLinks = new InternalLinks(
            $this->contentQueryExecutor->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->logger->reveal(),
            'some_template.html.twig',
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

        $internalLinks->write($node->reveal(), $this->property->reveal(), 1, 'test', 'de', null);
    }

    public function testGetContentData()
    {
        $internalLinks = new InternalLinks(
            $this->contentQueryExecutor->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->logger->reveal(),
            'some_template.html.twig',
            false
        );

        $this->property->getValue()->willReturn(['123-123-123']);
        $this->property->getParams()->willReturn([]);
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getWebspaceKey()->willReturn('default');
        $structure->getLanguageCode()->willReturn('en');
        $this->property->getStructure()->willReturn($structure->reveal());

        $this->contentQueryBuilder->init(['ids' => ['123-123-123'], 'properties' => [], 'published' => true])
            ->shouldBeCalled();
        $this->contentQueryExecutor->execute('default', ['en'], $this->contentQueryBuilder->reveal())->willReturn([]);

        $internalLinks->getContentData($this->property->reveal());
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\InternalLink;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\ContentBundle\Content\Types\InternalLinks;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;

/**
 * @group unit
 */
class InternalLinksTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQuery;

    /**
     * @var InternalLinks
     */
    private $internalLinks;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    protected function setUp()
    {
        $this->contentQuery = $this->getMockForAbstractClass(
            ContentQueryExecutorInterface::class,
            [],
            '',
            false
        );
        $this->contentQueryBuilder = $this->getMockForAbstractClass(
            ContentQueryBuilderInterface::class,
            [],
            '',
            false
        );
        $this->internalLinks = new InternalLinks($this->contentQuery, $this->contentQueryBuilder, new NullLogger(), 'asdf');
    }

    public function testWriteWithNoneExistingUUID()
    {
        $subNode1 = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true
        );
        $subNode1->expects($this->any())->method('getIdentifier')->will($this->returnValue('123-123-123'));
        $subNode2 = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true
        );
        $subNode2->expects($this->any())->method('getIdentifier')->will($this->returnValue('123-456-789'));

        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true
        );

        $session = $this->getMockForAbstractClass(
            SessionInterface::class,
            [],
            '',
            true,
            true,
            true
        );

        $node->expects($this->any())->method('getSession')->will($this->returnValue($session));
        $session->expects($this->any())->method('getNodesByIdentifier')->will(
            $this->returnValue([$subNode1, $subNode2])
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                [
                    '123-123-123',
                    '123-456-789',
                    'not existing',
                ]
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            [
                '123-123-123',
                '123-456-789',
            ]
        );

        $this->internalLinks->write($node, $property, 1, 'test', 'de', null);
    }
}

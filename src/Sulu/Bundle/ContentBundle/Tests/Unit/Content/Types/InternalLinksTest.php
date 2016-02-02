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

use Psr\Log\LoggerInterface;
use Sulu\Bundle\ContentBundle\Content\Types\InternalLinks;
use Sulu\Component\Content\Compat\PropertyInterface;
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

    /**
     * @var InternalLinks
     */
    private $type;

    public function setUp()
    {
        parent::setUp();
        $this->contentQueryExecutor = $this->prophesize('Sulu\Component\Content\Query\ContentQueryExecutorInterface');
        $this->contentQueryBuilder = $this->prophesize('Sulu\Component\Content\Query\ContentQueryBuilderInterface');
        $this->logger = $this->prophesize('Psr\Log\LoggerInterface');
        $this->property = $this->prophesize('Sulu\Component\Content\Compat\PropertyInterface');

        $this->type = new InternalLinks(
            $this->contentQueryExecutor->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->logger->reveal(),
            'some_template.html.twig'
        );
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
        $this->property->getValue()->willReturn($propertyValue);
        $uuids = $this->type->getReferencedUuids($this->property->reveal());
        $this->assertEquals($expected, $uuids);
    }
}

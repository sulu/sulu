<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Tests\Unit\Content\Document\Query;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\SessionInterface;
use PHPCR\WorkspaceInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Query\StructureQueryBuilderConverter;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\DocumentManager\DocumentStrategyInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StructureQueryBuilderConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var StructureQueryBuilderConverter
     */
    private $converter;

    public function setUp()
    {
        $this->metadataFactory = $this->prophesize(StructureMetadataFactory::class);
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $session = $this->prophesize(SessionInterface::class);
        $workspace = $this->prophesize(WorkspaceInterface::class);
        $queryManager = $this->prophesize(QueryManagerInterface::class);
        $qomf = $this->prophesize(QueryObjectModelFactoryInterface::class);
        $session->getWorkspace()->willReturn($workspace->reveal());
        $workspace->getQueryManager()->willReturn($queryManager->reveal());
        $queryManager->getQOMFactory()->willReturn($qomf->reveal());

        $this->converter = new StructureQueryBuilderConverter(
            $session->reveal(),
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
            $this->prophesize(MetadataFactoryInterface::class)->reveal(),
            $this->encoder->reveal(),
            $this->prophesize(DocumentStrategyInterface::class)->reveal(),
            $this->metadataFactory->reveal()
        );
    }

    /**
     * It should thow an exception if getQuery is passed something that is not a Sulu QueryBuilder.
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage StructureQueryBuilderConverter must be passed
     */
    public function testPassedNonSuluQueryBuilder()
    {
        $queryBuilder = $this->prophesize('Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder');
        $this->converter->getQuery($queryBuilder->reveal());
    }
}

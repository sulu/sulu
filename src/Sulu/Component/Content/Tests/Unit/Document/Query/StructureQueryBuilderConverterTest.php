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

use Sulu\Component\Content\Document\Query\StructureQueryBuilderConverter;

class StructureQueryBuilderConverterTest extends \PHPUnit_Framework_TestCase
{
    private $metadataFactory;
    private $encoder;

    public function setUp()
    {
        $this->metadataFactory = $this->prophesize('Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory');
        $this->encoder = $this->prophesize('Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder');
        $session = $this->prophesize('PHPCR\SessionInterface');
        $workspace = $this->prophesize('PHPCR\WorkspaceInterface');
        $queryManager = $this->prophesize('PHPCR\Query\QueryManagerInterface');
        $qomf = $this->prophesize('PHPCR\Query\QOM\QueryObjectModelFactoryInterface');
        $session->getWorkspace()->willReturn($workspace->reveal());
        $workspace->getQueryManager()->willReturn($queryManager->reveal());
        $queryManager->getQOMFactory()->willReturn($qomf->reveal());

        $this->converter = new StructureQueryBuilderConverter(
            $session->reveal(),
            $this->prophesize('Symfony\Component\EventDispatcher\EventDispatcherInterface')->reveal(),
            $this->prophesize('Sulu\Component\DocumentManager\MetadataFactoryInterface')->reveal(),
            $this->encoder->reveal(),
            $this->prophesize('Sulu\Component\DocumentManager\DocumentStrategyInterface')->reveal(),
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

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventSubscriber;

use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Query\Query;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Bundle\ContentBundle\Search\ReIndex\StructureProvider;


class StructureProviderTest extends \PHPUnit_Framework_TestCase
{
    private $provider;
    private $documentManager;
    private $metadataFactory;
    private $structureFactory;
    private $query;
    private $metadata1;
    private $metadata2;

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->structureFactory = $this->prophesize(StructureMetadataFactoryInterface::class);

        $this->provider = new StructureProvider(
            $this->documentManager->reveal(),
            $this->metadataFactory->reveal(),
            $this->structureFactory->reveal()
        );

        $this->query = $this->prophesize(Query::class);
        $this->metadata1 = $this->prophesize(Metadata::class);
        $this->metadata2 = $this->prophesize(Metadata::class);
    }

    /**
     * It should provide a batch of documents.
     */
    public function testDocumentBatch()
    {
        $maxResults = 10;
        $offset = 5;
        $classFqn = 'Foo';
        $objects = [new \stdClass(), new \stdClass()];

        $this->metadataFactory->getMetadataForClass($classFqn)->willReturn(
            $this->metadata1->reveal()
        );
        $this->documentManager->createQuery(Argument::type('string'))->willReturn(
            $this->query->reveal()
        );
        $this->query->setFirstResult($offset)->shouldBeCalled();
        $this->query->setMaxResults($maxResults)->shouldBeCalled();
        $this->query->execute()->willReturn($objects);

        $results = $this->provider->provide($classFqn, $offset, $maxResults);
        $this->assertEquals($objects, $results);
    }

    /**
     * It should provide all the class FQNs
     */
    public function testClassFqns()
    {
        $alias = 'a';
        $class = 'Foo';

        $this->metadataFactory->getAllMetadata()->willReturn([
            $this->metadata1->reveal()
        ]);
        $this->metadata1->getAlias()->willReturn($alias);
        $this->structureFactory->hasStructuresFor($alias)->willReturn(true);
        $this->metadata1->getClass()->willReturn($class);

        $classFqns = $this->provider->getClassFqns();

        $this->assertEquals([$class], $classFqns);
    }

    /**
     * It should NOT return class FQNs that are not mapped to a Structure.
     */
    public function testClassFqnsNoStructure()
    {
        $alias = 'a';
        $class = 'Foo';

        $this->metadataFactory->getAllMetadata()->willReturn([
            $this->metadata1->reveal()
        ]);
        $this->metadata1->getAlias()->willReturn($alias);
        $this->structureFactory->hasStructuresFor($alias)->willReturn(false);

        $classFqns = $this->provider->getClassFqns();

        $this->assertEquals([], $classFqns);
    }

    /**
     * It should return the total count for a given class FQN.
     */
    public function testCount()
    {
        $class = 'Foo';
        $objects = [new \stdClass(), new \stdClass()];

        $this->metadataFactory->getMetadataForClass($class)->willReturn(
            $this->metadata1->reveal()
        );
        $this->documentManager->createQuery(Argument::type('string'))->willReturn(
            $this->query->reveal()
        );
        $this->query->execute()->willReturn($objects);

        $count = $this->provider->getCount($class);

        $this->assertEquals(count($objects), $count);
    }
}

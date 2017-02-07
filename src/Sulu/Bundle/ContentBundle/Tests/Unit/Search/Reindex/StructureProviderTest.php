<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Search\Reindex;

use Massive\Bundle\SearchBundle\Search\Reindex\LocalizedReindexProviderInterface;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Search\Reindex\StructureProvider;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Query\Query;

class StructureProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocalizedReindexProviderInterface
     */
    private $provider;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureFactory;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var Metadata
     */
    private $metadata1;

    /**
     * @var StructureBehavior
     */
    private $structure;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var SecurityBehavior
     */
    private $secureStructure;

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->structureFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);

        $this->provider = new StructureProvider(
            $this->documentManager->reveal(),
            $this->metadataFactory->reveal(),
            $this->structureFactory->reveal(),
            $this->inspector->reveal(),
            'admin'
        );

        $this->query = $this->prophesize(Query::class);
        $this->metadata1 = $this->prophesize(Metadata::class);
        $this->structure = $this->prophesize(StructureBehavior::class);
        $this->secureStructure = $this->prophesize(StructureBehavior::class)
            ->willImplement(SecurityBehavior::class);
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
     * It should provide all the class FQNs.
     */
    public function testClassFqns()
    {
        $alias = 'a';
        $class = 'Foo';

        $this->metadataFactory->getAllMetadata()->willReturn([
            $this->metadata1->reveal(),
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

        $this->metadataFactory->getAllMetadata()->willReturn([
            $this->metadata1->reveal(),
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

    /**
     * It should get the locales for a given document.
     */
    public function testGetLocales()
    {
        $locales = ['de', 'fr'];
        $this->inspector->getLocales($this->structure->reveal())->willReturn($locales);

        $result = $this->provider->getLocalesForObject($this->structure->reveal());
        $this->assertEquals($locales, $result);
    }

    /**
     * It should translate a given object.
     */
    public function testTranslate()
    {
        $locale = 'de';
        $uuid = '1234';
        $this->inspector->getUuid($this->structure->reveal())->willReturn($uuid);
        $this->documentManager->find($uuid, $locale)->willReturn($this->structure->reveal());

        $translated = $this->provider->translateObject($this->structure->reveal(), $locale);

        $this->assertSame($this->structure->reveal(), $translated);
    }

    /**
     * It should not index secure documents which have permissions.
     */
    public function testSecureDocuments()
    {
        $classFqn = 'Foo';
        $offset = 0;
        $maxResults = 50;
        $objects = [
            $this->structure->reveal(),
            $this->secureStructure->reveal(),
            $this->structure->reveal(),
        ];

        $this->metadataFactory->getMetadataForClass($classFqn)->willReturn(
            $this->metadata1->reveal()
        );
        $this->documentManager->createQuery(Argument::type('string'))->willReturn(
            $this->query->reveal()
        );
        $this->query->setFirstResult($offset)->shouldBeCalled();
        $this->query->setMaxResults($maxResults)->shouldBeCalled();
        $this->query->execute()->willReturn($objects);
        $this->secureStructure->getPermissions()->willReturn(['one']);

        $results = $this->provider->provide($classFqn, $offset, $maxResults);
        $this->assertEquals([
            $this->structure->reveal(),
            $this->structure->reveal(),
        ], $results);
    }

    public function testCleanup()
    {
        $classFqn = 'Foo';
        $this->provider->cleanUp($classFqn);

        $this->documentManager->clear()->shouldBeCalled();
    }
}

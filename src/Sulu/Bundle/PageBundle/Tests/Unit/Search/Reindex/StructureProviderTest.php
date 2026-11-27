<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Search\Reindex;

use Massive\Bundle\SearchBundle\Search\Reindex\LocalizedReindexProviderInterface;
use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Search\Reindex\StructureProvider;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Metadata\Factory\Exception\StructureTypeNotFoundException;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Query\Query;

class StructureProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var LocalizedReindexProviderInterface
     */
    private $provider;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<MetadataFactoryInterface>
     */
    private $metadataFactory;

    /**
     * @var ObjectProphecy<StructureMetadataFactoryInterface>
     */
    private $structureFactory;

    /**
     * @var ObjectProphecy<Query>
     */
    private $query;

    /**
     * @var ObjectProphecy<Metadata>
     */
    private $metadata1;

    /**
     * @var ObjectProphecy<StructureBehavior>
     */
    private $structure;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $inspector;

    /**
     * @var ObjectProphecy<StructureBehavior&SecurityBehavior>
     */
    private $secureStructure;

    public function setUp(): void
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
    public function testDocumentBatch(): void
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
    public function testClassFqns(): void
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
    public function testClassFqnsNoStructure(): void
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
    public function testCount(): void
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

        $this->assertEquals(\count($objects), $count);
    }

    /**
     * It should get the locales for a given document.
     */
    public function testGetLocales(): void
    {
        $locales = ['de', 'fr'];
        $this->inspector->getLocales($this->structure->reveal())->willReturn($locales);

        $result = $this->provider->getLocalesForObject($this->structure->reveal());
        $this->assertEquals($locales, $result);
    }

    /**
     * It should translate a given object.
     */
    public function testTranslate(): void
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
    public function testSecureDocuments(): void
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

    public function testCleanup(): void
    {
        $classFqn = 'Foo';
        $this->provider->cleanUp($classFqn);

        $this->documentManager->clear()->shouldBeCalled();
    }

    public function testGetLocalesSkipsLocalesWithMissingStructure(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $provider = $this->createProviderWithEncoder($logger->reveal());

        $document = $this->prophesize(StructureBehavior::class)->willImplement(LocalizedStructureBehavior::class);
        $this->mockDocument($document->reveal(), ['de', 'en', 'fr'], [
            'i18n:de-template' => 'default',
            'i18n:en-template' => 'removed',
            // no template for "fr"
        ]);

        $this->structureFactory->getStructureMetadata('page', 'default')->willReturn(new StructureMetadata('default'));
        $this->structureFactory->getStructureMetadata('page', 'removed')
            ->willThrow(new StructureTypeNotFoundException('Could not load structure type "removed"'));
        $this->structureFactory->getStructureMetadata('page', null)->willReturn(null);

        $logger->warning(Argument::containingString('in locale "en"'))->shouldBeCalledOnce();
        $logger->warning(Argument::containingString('in locale "fr"'))->shouldBeCalledOnce();

        $this->assertSame(['de'], $provider->getLocalesForObject($document->reveal()));
    }

    public function testGetLocalesUsesStructureOfShadowBaseLocale(): void
    {
        $provider = $this->createProviderWithEncoder();

        $document = $this->prophesize(StructureBehavior::class)
            ->willImplement(LocalizedStructureBehavior::class)
            ->willImplement(ShadowLocaleBehavior::class);
        $this->mockDocument($document->reveal(), ['de', 'en'], [
            'i18n:de-template' => 'default',
            'i18n:en-template' => 'removed',
            'i18n:en-shadow-on' => true,
            'i18n:en-shadow-base' => 'de',
        ]);

        $this->structureFactory->getStructureMetadata('page', 'default')->willReturn(new StructureMetadata('default'));
        $this->structureFactory->getStructureMetadata('page', 'removed')->shouldNotBeCalled();

        $this->assertSame(['de', 'en'], $provider->getLocalesForObject($document->reveal()));
    }

    public function testGetLocalesLooksUpEachStructureOnlyOnce(): void
    {
        $provider = $this->createProviderWithEncoder();

        $document = $this->prophesize(StructureBehavior::class)->willImplement(LocalizedStructureBehavior::class);
        $this->mockDocument($document->reveal(), ['de', 'en'], [
            'i18n:de-template' => 'default',
            'i18n:en-template' => 'default',
        ]);

        $this->structureFactory->getStructureMetadata('page', 'default')
            ->shouldBeCalledOnce()
            ->willReturn(new StructureMetadata('default'));

        $this->assertSame(['de', 'en'], $provider->getLocalesForObject($document->reveal()));
        $this->assertSame(['de', 'en'], $provider->getLocalesForObject($document->reveal()));
    }

    private function createProviderWithEncoder(?LoggerInterface $logger = null): StructureProvider
    {
        return new StructureProvider(
            $this->documentManager->reveal(),
            $this->metadataFactory->reveal(),
            $this->structureFactory->reveal(),
            $this->inspector->reveal(),
            'admin',
            new PropertyEncoder(new NamespaceRegistry(['system_localized' => 'i18n', 'content' => ''])),
            $logger
        );
    }

    /**
     * @param string[] $locales
     * @param array<string, mixed> $properties
     */
    private function mockDocument(object $document, array $locales, array $properties): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault(Argument::type('string'), Argument::any())
            ->will(function(array $arguments) use ($properties) {
                [$name, $default] = $arguments;

                return \is_string($name) && \array_key_exists($name, $properties) ? $properties[$name] : $default;
            });

        $metadata = $this->prophesize(Metadata::class);
        $metadata->getAlias()->willReturn('page');

        $this->inspector->getLocales($document)->willReturn($locales);
        $this->inspector->getNode($document)->willReturn($node->reveal());
        $this->inspector->getMetadata($document)->willReturn($metadata->reveal());
        $this->inspector->getUuid($document)->willReturn('123-123-123');
    }
}

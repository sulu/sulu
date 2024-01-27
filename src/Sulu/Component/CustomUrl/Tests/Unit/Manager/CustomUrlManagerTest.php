<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Manager;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlCreatedEvent;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlModifiedEvent;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRemovedEvent;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRouteRemovedEvent;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Manager\CustomUrlManager;
use Sulu\Component\CustomUrl\Manager\RouteNotRemovableException;
use Sulu\Component\CustomUrl\Manager\TitleAlreadyExistsException;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\NodeNameAlreadyExistsException;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;

class CustomUrlManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var ObjectProphecy<CustomUrlRepository>
     */
    private $customUrlRepository;

    /**
     * @var ObjectProphecy<MetadataFactoryInterface>
     */
    private $metadataFactory;

    /**
     * @var ObjectProphecy<PathBuilder>
     */
    private $pathBuilder;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var ObjectProphecy<DocumentDomainEventCollectorInterface>
     */
    private $documentDomainEventCollector;

    /**
     * @var PageDocument
     */
    private $targetDocument;

    /**
     * @var ObjectProphecy<Metadata>
     */
    private $metadata;

    /**
     * @var CustomUrlManager
     */
    private $manager;

    protected function setUp(): void
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->pathBuilder = $this->prophesize(PathBuilder::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->documentDomainEventCollector = $this->prophesize(DocumentDomainEventCollectorInterface::class);

        $this->targetDocument = $this->prophesize(PageDocument::class)->reveal();
        $this->metadata = $this->prophesize(Metadata::class);

        $this->manager = new CustomUrlManager(
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->customUrlRepository->reveal(),
            $this->metadataFactory->reveal(),
            $this->pathBuilder->reveal(),
            $this->webspaceManager->reveal(),
            $this->environment,
            $this->documentDomainEventCollector->reveal()
        );
    }

    public function testCreate(): void
    {
        $this->metadata->getFieldMappings()->willReturn($this->getMapping());
        $this->metadataFactory->getMetadataForAlias('custom_url')->willReturn($this->metadata);

        $testDocument = new CustomUrlDocument();
        $this->documentManager->create('custom_url')->willReturn($testDocument);
        $this->documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])
            ->willReturn($this->targetDocument);

        $this->documentManager->persist(
            $testDocument,
            'en',
            ['parent_path' => '/cmf/sulu_io/custom_urls/items', 'load_ghost_content' => true, 'auto_rename' => false]
        )->shouldBeCalledTimes(1);
        $this->documentManager->publish($testDocument, 'en')->shouldBeCalled();
        $this->documentDomainEventCollector->collect(Argument::type(CustomUrlCreatedEvent::class))->shouldBeCalled();

        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $result = $this->manager->create(
            'sulu_io',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['test-1', 'test-1', 'test-2'],
                'targetDocument' => '123-123-123',
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ]
        );

        $this->assertEquals($testDocument, $result);
        $this->assertEquals('Test', $result->getTitle());
        $this->assertEquals('de', $result->getTargetLocale());
        $this->assertEquals('*.sulu.io', $result->getBaseDomain());
        $this->assertEquals(['test-1', 'test-1', 'test-2'], $result->getDomainParts());
        $this->assertEquals($this->targetDocument, $result->getTargetDocument());
        $this->assertTrue($result->isPublished());
        $this->assertTrue($result->isCanonical());
        $this->assertTrue($result->isRedirect());
    }

    public function testFindList(): void
    {
        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $this->customUrlRepository->findList('/cmf/sulu_io/custom_urls/items', ['*.sulu.io', 'sulu.io/*'])
            ->willReturn([['title' => 'Test-1'], ['title' => 'Test-2']]);

        $url1 = $this->prophesize(CustomUrl::class);
        $url1->getUrl()->willReturn('*.sulu.io');

        $url2 = $this->prophesize(CustomUrl::class);
        $url2->getUrl()->willReturn('sulu.io/*');

        $environment = $this->prophesize(Environment::class);
        $environment->getCustomUrls()->willReturn([$url1->reveal(), $url2->reveal()]);

        $portal = $this->prophesize(Portal::class);
        $portal->getEnvironment($this->environment)->willReturn($environment->reveal());

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getPortals()->willReturn([$portal->reveal()]);

        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace->reveal());

        $result = $this->manager->findList('sulu_io');

        $this->assertEquals([['title' => 'Test-1'], ['title' => 'Test-2']], $result);
    }

    public function testFindUrls(): void
    {
        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $this->customUrlRepository->findUrls('/cmf/sulu_io/custom_urls/items')
            ->willReturn(['1.sulu.lo', '1.sulu.lo/2']);

        $result = $this->manager->findUrls('sulu_io');

        $this->assertEquals(['1.sulu.lo', '1.sulu.lo/2'], $result);
    }

    public function testFindHistoryRoutesById(): void
    {
        $customUrlDocument = $this->prophesize(CustomUrlDocument::class);
        $this->documentManager->find('123-456-789', 'en', ['load_ghost_content' => true])
            ->willReturn($customUrlDocument->reveal());

        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom_urls/routes');

        $routeDocument1 = $this->prophesize(RouteDocument::class);
        $routeDocument1->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test1');
        $routeDocument1->isHistory()->willReturn(true);
        $routeDocument2 = $this->prophesize(RouteDocument::class);
        $routeDocument2->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test2');
        $routeDocument2->isHistory()->willReturn(false);
        $routeDocument3 = $this->prophesize(RouteDocument::class);
        $routeDocument3->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test3');
        $routeDocument3->isHistory()->willReturn(true);

        $this->documentInspector->getReferrers($customUrlDocument->reveal())->willReturn(
            [
                $routeDocument1->reveal(),
                $routeDocument2->reveal(),
            ]
        );

        $this->documentInspector->getReferrers($routeDocument1)->willReturn([$routeDocument3]);
        $this->documentInspector->getReferrers($routeDocument2)->willReturn([]);
        $this->documentInspector->getReferrers($routeDocument3)->willReturn([]);

        $this->assertEquals(
            ['sulu.io/test1' => $routeDocument1->reveal(), 'sulu.io/test3' => $routeDocument3->reveal()],
            $this->manager->findHistoryRoutesById('123-456-789', 'sulu_io')
        );
    }

    public function testFind(): void
    {
        $document = $this->prophesize(CustomUrlDocument::class);

        $this->documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])
            ->willReturn($document->reveal());

        $result = $this->manager->find('123-123-123');

        $this->assertEquals($document->reveal(), $result);
    }

    public function testFindByUrl(): void
    {
        $routeDocument = $this->prophesize(RouteDocument::class);
        $customUrlDocument = $this->prophesize(CustomUrlDocument::class);

        $routeDocument->getTargetDocument()->willReturn($customUrlDocument->reveal());

        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom_urls/routes');

        $this->documentManager->find('/cmf/sulu_io/custom_urls/routes/sulu.io/test', 'de', ['load_ghost_content' => true])
            ->willReturn($routeDocument->reveal());

        $result = $this->manager->findByUrl('sulu.io/test', 'sulu_io', 'de');

        $this->assertEquals($customUrlDocument->reveal(), $result);
    }

    public function testFindRouteByUrl(): void
    {
        $routeDocument = $this->prophesize(RouteDocument::class);

        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom_urls/routes');

        $this->documentManager->find('/cmf/sulu_io/custom_urls/routes/sulu.io/test', 'en', ['load_ghost_content' => true])
            ->willReturn($routeDocument->reveal());

        $result = $this->manager->findRouteByUrl('sulu.io/test', 'sulu_io', 'en');

        $this->assertEquals($routeDocument->reveal(), $result);
    }

    public function testUpdate(): void
    {
        $document = $this->prophesize(CustomUrlDocument::class);
        $targetDocument = $this->prophesize(PageDocument::class);

        $document->getPath()->willReturn('/cmf/sulu_io/custom_urls/items/test');
        $document->getTitle()->willReturn('Test-1');

        $document->setTitle('Test')->shouldBeCalled();
        $document->setPublished(true)->shouldBeCalled();
        $document->setRedirect(true)->shouldBeCalled();
        $document->setCanonical(true)->shouldBeCalled();
        $document->setTargetLocale('de')->shouldBeCalled();
        $document->setBaseDomain('*.sulu.io')->shouldBeCalled();
        $document->setDomainParts(['test-1', 'test-1', 'test-2'])->shouldBeCalled();
        $document->setTargetDocument($targetDocument->reveal())->shouldBeCalled();

        $this->documentInspector->getWebspace($document->reveal())->willReturn('sulu_io');

        $this->metadata->getFieldMappings()->willReturn($this->getMapping());
        $this->metadataFactory->getMetadataForAlias('custom_url')->willReturn($this->metadata->reveal());

        $this->documentManager->find('312-312-312', 'en', ['load_ghost_content' => true])->willReturn($document->reveal());
        $this->documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])->willReturn(
            $targetDocument->reveal()
        );
        $this->documentManager->persist(
            $document,
            'en',
            [
                'parent_path' => '/cmf/sulu_io/custom_urls/items',
                'load_ghost_content' => true,
                'auto_rename' => false,
                'auto_name_locale' => 'en',
            ]
        )->shouldBeCalledTimes(1);
        $this->documentManager->publish($document, 'en')->shouldBeCalledTimes(1);
        $this->documentDomainEventCollector->collect(Argument::type(CustomUrlModifiedEvent::class))->shouldBeCalled();

        $result = $this->manager->save(
            '312-312-312',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['test-1', 'test-1', 'test-2'],
                'targetDocument' => '123-123-123',
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ]
        );

        $this->assertEquals($document->reveal(), $result);
    }

    public function testUpdateItemExists(): void
    {
        $document = $this->prophesize(CustomUrlDocument::class);
        $targetDocument = $this->prophesize(PageDocument::class);

        $document->getPath()->willReturn('/cmf/sulu_io/custom_urls/items/test');
        $document->getTitle()->willReturn('Test-1');

        $document->setTitle('Test')->shouldBeCalled();
        $document->setPublished(true)->shouldBeCalled();
        $document->setRedirect(true)->shouldBeCalled();
        $document->setCanonical(true)->shouldBeCalled();
        $document->setTargetLocale('de')->shouldBeCalled();
        $document->setBaseDomain('*.sulu.io')->shouldBeCalled();
        $document->setDomainParts(['test-1', 'test-1', 'test-2'])->shouldBeCalled();
        $document->setTargetDocument($targetDocument->reveal())->shouldBeCalled();

        $this->documentInspector->getWebspace($document->reveal())->willReturn('sulu_io');

        $this->metadata->getFieldMappings()->willReturn($this->getMapping());
        $this->metadataFactory->getMetadataForAlias('custom_url')->willReturn($this->metadata->reveal());

        $this->documentManager->find('312-312-312', 'en', ['load_ghost_content' => true])
             ->willReturn($document->reveal());
        $this->documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])->willReturn(
            $targetDocument->reveal()
        );
        $this->documentManager->persist(
            $document,
            'en',
            [
                'parent_path' => '/cmf/sulu_io/custom_urls/items',
                'load_ghost_content' => true,
                'auto_rename' => false,
                'auto_name_locale' => 'en',
            ]
        )->willThrow(NodeNameAlreadyExistsException::class);

        $this->expectException(TitleAlreadyExistsException::class);

        $this->manager->save(
            '312-312-312',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['test-1', 'test-1', 'test-2'],
                'targetDocument' => '123-123-123',
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ]
        );
    }

    public function testDelete(): void
    {
        $document = $this->prophesize(CustomUrlDocument::class);
        $document->getUuid()->willReturn('1234-1234-1234-1234');
        $document->getTitle()->willReturn('Test-1');
        $this->documentInspector->getWebspace($document->reveal())->willReturn('sulu_io');

        $this->documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])->willReturn($document->reveal());
        $this->documentManager->remove($document->reveal())->shouldBeCalled();
        $this->documentDomainEventCollector->collect(Argument::type(CustomUrlRemovedEvent::class))->shouldBeCalled();

        $this->manager->delete('123-123-123');
    }

    public function testDeleteRoute(): void
    {
        $routeDocument = $this->prophesize(RouteDocument::class);
        $customUrlDocument = $this->prophesize(CustomUrlDocument::class);

        $routeDocument->getUuid()->willReturn('1234-1234-1234-1234');
        $routeDocument->isHistory()->willReturn(true);
        $routeDocument->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test');
        $routeDocument->getTargetDocument()->willReturn($customUrlDocument->reveal());

        $this->documentManager->find('123-123-123')->willReturn($routeDocument->reveal());
        $this->documentManager->remove($routeDocument->reveal())->shouldBeCalled();
        $this->documentDomainEventCollector->collect(Argument::type(CustomUrlRouteRemovedEvent::class))->shouldBeCalled();

        $result = $this->manager->deleteRoute('sulu_io', '123-123-123');
        $this->assertEquals($routeDocument->reveal(), $result);
    }

    public function testDeleteHistory(): void
    {
        $this->expectException(RouteNotRemovableException::class);

        $document = $this->prophesize(RouteDocument::class);
        $customUrlDocument = $this->prophesize(CustomUrlDocument::class);

        $document->isHistory()->willReturn(false);
        $document->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test');
        $document->getTargetDocument()->willReturn($customUrlDocument->reveal());

        $this->documentManager->find('123-123-123')->willReturn($document->reveal());
        $this->documentManager->remove($document->reveal())->shouldNotBeCalled();

        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom_urls/routes');

        $this->manager->deleteRoute('sulu_io', '123-123-123');
    }

    private function getMapping()
    {
        return [
            'title' => ['property' => 'title'],
            'published' => ['property' => 'published'],
            'baseDomain' => ['property' => 'baseDomain'],
            'domainParts' => ['property' => 'domainParts', 'type' => 'json_array'],
            'targetDocument' => ['property' => 'targetDocument', 'type' => 'reference'],
            'canonical' => ['property' => 'canonical'],
            'redirect' => ['property' => 'redirect'],
            'targetLocale' => ['property' => 'targetLocale'],
        ];
    }
}

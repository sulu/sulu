<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Manager;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
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
use Sulu\Component\HttpCache\HandlerInvalidatePathInterface;

/**
 * Provides testcases for custom-url-manager.
 */
class CustomUrlManagerTest extends \PHPUnit_Framework_TestCase
{
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

    public function testCreate()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $targetDocument = $this->prophesize(PageDocument::class)->reveal();

        $metadata = $this->prophesize(Metadata::class);
        $metadata->getFieldMappings()->willReturn($this->getMapping());
        $metadataFactory->getMetadataForAlias('custom_url')->willReturn($metadata);

        $testDocument = new CustomUrlDocument();
        $documentManager->create('custom_url')->willReturn($testDocument);
        $documentManager->persist(
            $testDocument,
            'en',
            ['parent_path' => '/cmf/sulu_io/custom_urls/items', 'load_ghost_content' => true, 'auto_rename' => false]
        )->shouldBeCalledTimes(1);
        $documentManager->publish($testDocument, 'en')->shouldBeCalled();
        $documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])
            ->willReturn($targetDocument);

        $pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $result = $manager->create(
            'sulu_io',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']],
                'targetDocument' => ['uuid' => '123-123-123'],
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ],
            'en'
        );

        $this->assertEquals($testDocument, $result);
        $this->assertEquals('Test', $result->getTitle());
        $this->assertEquals('en', $result->getLocale());
        $this->assertEquals('de', $result->getTargetLocale());
        $this->assertEquals('*.sulu.io', $result->getBaseDomain());
        $this->assertEquals(['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']], $result->getDomainParts());
        $this->assertEquals($targetDocument, $result->getTargetDocument());
        $this->assertTrue($result->isPublished());
        $this->assertTrue($result->isCanonical());
        $this->assertTrue($result->isRedirect());
    }

    public function testFindList()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);

        $pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $customUrlRepository->findList('/cmf/sulu_io/custom_urls/items', 'de')
            ->willReturn([['title' => 'Test-1'], ['title' => 'Test-2']]);

        $result = $manager->findList('sulu_io', 'de');

        $this->assertEquals([['title' => 'Test-1'], ['title' => 'Test-2']], $result);
    }

    public function testFindUrls()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);

        $pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $customUrlRepository->findUrls('/cmf/sulu_io/custom_urls/items')
            ->willReturn(['1.sulu.lo', '1.sulu.lo/2']);

        $result = $manager->findUrls('sulu_io');

        $this->assertEquals(['1.sulu.lo', '1.sulu.lo/2'], $result);
    }

    public function testFind()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $document = $this->prophesize(CustomUrlDocument::class);

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $documentManager->find('123-123-123', 'de', ['load_ghost_content' => true])->willReturn($document->reveal());

        $result = $manager->find('123-123-123', 'de');

        $this->assertEquals($document->reveal(), $result);
    }

    public function testFindByUrl()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $routeDocument = $this->prophesize(RouteDocument::class);
        $customUrlDocument = $this->prophesize(CustomUrlDocument::class);

        $routeDocument->getTargetDocument()->willReturn($customUrlDocument->reveal());

        $pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom_urls/routes');

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $documentManager->find('/cmf/sulu_io/custom_urls/routes/sulu.io/test', 'de', ['load_ghost_content' => true])
            ->willReturn($routeDocument->reveal());

        $result = $manager->findByUrl('sulu.io/test', 'sulu_io', 'de');

        $this->assertEquals($customUrlDocument->reveal(), $result);
    }

    public function testFindRouteByUrl()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $routeDocument = $this->prophesize(RouteDocument::class);

        $pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom_urls/routes');

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $documentManager->find('/cmf/sulu_io/custom_urls/routes/sulu.io/test', 'de', ['load_ghost_content' => true])
            ->willReturn($routeDocument->reveal());

        $result = $manager->findRouteByUrl('sulu.io/test', 'sulu_io', 'de');

        $this->assertEquals($routeDocument->reveal(), $result);
    }

    public function testUpdate()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $targetDocument = $this->prophesize(PageDocument::class);

        $document->getPath()->willReturn('/cmf/sulu_io/custom_urls/items/test');
        $document->getTitle()->willReturn('Test-1');

        $document->setTitle('Test')->shouldBeCalled();
        $document->setPublished(true)->shouldBeCalled();
        $document->setRedirect(true)->shouldBeCalled();
        $document->setCanonical(true)->shouldBeCalled();
        $document->setLocale('en')->shouldBeCalled();
        $document->setTargetLocale('de')->shouldBeCalled();
        $document->setBaseDomain('*.sulu.io')->shouldBeCalled();
        $document->setDomainParts(['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']])->shouldBeCalled();
        $document->setTargetDocument($targetDocument->reveal())->shouldBeCalled();

        $metadata = $this->prophesize(Metadata::class);
        $metadata->getFieldMappings()->willReturn($this->getMapping());
        $metadataFactory->getMetadataForAlias('custom_url')->willReturn($metadata);

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $documentManager->find('312-312-312', 'en', ['load_ghost_content' => true])->willReturn($document->reveal());
        $documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])->willReturn(
            $targetDocument->reveal()
        );
        $documentManager->persist(
            $document,
            'en',
            [
                'parent_path' => '/cmf/sulu_io/custom_urls/items',
                'load_ghost_content' => true,
                'auto_rename' => false,
                'auto_name_locale' => 'en',
            ]
        )->shouldBeCalledTimes(1);
        $documentManager->publish($document, 'en')->shouldBeCalledTimes(1);

        $result = $manager->save(
            '312-312-312',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']],
                'targetDocument' => ['uuid' => '123-123-123'],
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ],
            'en'
        );

        $this->assertEquals($document->reveal(), $result);
    }

    public function testUpdateItemExists()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $targetDocument = $this->prophesize(PageDocument::class);

        $document->getPath()->willReturn('/cmf/sulu_io/custom_urls/items/test');
        $document->getTitle()->willReturn('Test-1');

        $document->setTitle('Test')->shouldBeCalled();
        $document->setPublished(true)->shouldBeCalled();
        $document->setRedirect(true)->shouldBeCalled();
        $document->setCanonical(true)->shouldBeCalled();
        $document->setLocale('en')->shouldBeCalled();
        $document->setTargetLocale('de')->shouldBeCalled();
        $document->setBaseDomain('*.sulu.io')->shouldBeCalled();
        $document->setDomainParts(['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']])->shouldBeCalled();
        $document->setTargetDocument($targetDocument->reveal())->shouldBeCalled();

        $metadata = $this->prophesize(Metadata::class);
        $metadata->getFieldMappings()->willReturn($this->getMapping());
        $metadataFactory->getMetadataForAlias('custom_url')->willReturn($metadata);

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $documentManager->find('312-312-312', 'en', ['load_ghost_content' => true])->willReturn($document->reveal());
        $documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])->willReturn(
            $targetDocument->reveal()
        );
        $documentManager->persist(
            $document,
            'en',
            [
                'parent_path' => '/cmf/sulu_io/custom_urls/items',
                'load_ghost_content' => true,
                'auto_rename' => false,
                'auto_name_locale' => 'en',
            ]
        )->willThrow(NodeNameAlreadyExistsException::class);

        $this->setExpectedException(TitleAlreadyExistsException::class);

        $manager->save(
            '312-312-312',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']],
                'targetDocument' => ['uuid' => '123-123-123'],
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ],
            'en'
        );
    }

    public function testDelete()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $document = $this->prophesize(CustomUrlDocument::class);

        $documentManager->find('123-123-123', null, ['load_ghost_content' => true])->willReturn($document->reveal());
        $documentManager->remove($document->reveal())->shouldBeCalled();

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $manager->delete('123-123-123');
    }

    public function testDeleteRoute()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $document = $this->prophesize(RouteDocument::class);
        $customUrlDocument = $this->prophesize(CustomUrlDocument::class);

        $document->isHistory()->willReturn(true);
        $document->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test');
        $document->getTargetDocument()->willReturn($customUrlDocument->reveal());

        $documentManager->find('123-123-123')->willReturn($document->reveal());
        $documentManager->remove($document->reveal())->shouldBeCalled();

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $result = $manager->deleteRoute('sulu_io', '123-123-123');
        $this->assertEquals($document->reveal(), $result);
    }

    public function testDeleteHistory()
    {
        $this->setExpectedException(RouteNotRemovableException::class);

        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $document = $this->prophesize(RouteDocument::class);
        $customUrlDocument = $this->prophesize(CustomUrlDocument::class);

        $document->isHistory()->willReturn(false);
        $document->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test');
        $document->getTargetDocument()->willReturn($customUrlDocument->reveal());

        $documentManager->find('123-123-123')->willReturn($document->reveal());
        $documentManager->remove($document->reveal())->shouldNotBeCalled();

        $pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom_urls/routes');

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $manager->deleteRoute('sulu_io', '123-123-123');
    }

    public function testInvalidate()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $document = $this->prophesize(CustomUrlDocument::class);

        $document->getRoutes()->willReturn(
            [
                'sulu.io/en' => $this->prophesize(RouteDocument::class)->reveal(),
                'sulu.io/de' => $this->prophesize(RouteDocument::class)->reveal(),
            ]
        );

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $manager->invalidate($document->reveal());

        $cacheHandler->invalidatePath('sulu.io/en')->shouldBeCalled();
        $cacheHandler->invalidatePath('sulu.io/de')->shouldBeCalled();
    }

    public function testInvalidateRoute()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $document = $this->prophesize(RouteDocument::class);

        $document->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/en');
        $pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom_urls/routes');

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal(),
            $cacheHandler->reveal()
        );

        $manager->invalidateRoute('sulu_io', $document->reveal());

        $cacheHandler->invalidatePath('sulu.io/en')->shouldBeCalled();
    }
}

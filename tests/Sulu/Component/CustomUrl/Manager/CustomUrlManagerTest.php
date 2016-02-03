<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use PHPCR\ItemExistsException;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
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
            'target' => ['property' => 'target', 'type' => 'reference'],
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
        $metadataFactory->getMetadataForAlias('custom_urls')->willReturn($metadata);

        $testDocument = new CustomUrlDocument();
        $documentManager->create('custom_urls')->willReturn($testDocument);
        $documentManager->persist(
            $testDocument,
            'en',
            ['parent_path' => '/cmf/sulu_io/custom_urls/items', 'node_name' => 'test', 'load_ghost_content' => true]
        )->shouldBeCalledTimes(1);
        $documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])
            ->willReturn($targetDocument);

        $pathBuilder->build(['%base%', 'sulu_io', '%custom-urls%', '%custom-urls-items%'])
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
                'target' => ['uuid' => '123-123-123'],
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ],
            'en'
        );

        self::assertEquals($testDocument, $result);
        self::assertEquals('Test', $result->getTitle());
        self::assertEquals('en', $result->getLocale());
        self::assertEquals('de', $result->getTargetLocale());
        self::assertEquals('*.sulu.io', $result->getBaseDomain());
        self::assertEquals(['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']], $result->getDomainParts());
        self::assertEquals($targetDocument, $result->getTarget());
        self::assertTrue($result->isPublished());
        self::assertTrue($result->isCanonical());
        self::assertTrue($result->isRedirect());
    }

    public function testReadList()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);

        $pathBuilder->build(['%base%', 'sulu_io', '%custom-urls%', '%custom-urls-items%'])
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

        $result = $manager->readList('sulu_io', 'de');

        self::assertEquals([['title' => 'Test-1'], ['title' => 'Test-2']], $result);
    }

    public function testRead()
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

        $result = $manager->read('123-123-123', 'de');

        self::assertEquals($document->reveal(), $result);
    }

    public function testReadByUrl()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $routeDocument = $this->prophesize(RouteDocument::class);
        $customUrlDocument = $this->prophesize(CustomUrlDocument::class);

        $routeDocument->getTargetDocument()->willReturn($customUrlDocument->reveal());

        $pathBuilder->build(['%base%', 'sulu_io', '%custom-urls%', '%custom-urls-routes%'])
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

        $result = $manager->readByUrl('sulu.io/test', 'sulu_io', 'de');

        self::assertEquals($customUrlDocument->reveal(), $result);
    }

    public function testReadRouteByUrl()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $routeDocument = $this->prophesize(RouteDocument::class);

        $pathBuilder->build(['%base%', 'sulu_io', '%custom-urls%', '%custom-urls-routes%'])
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

        $result = $manager->readRouteByUrl('sulu.io/test', 'sulu_io', 'de');

        self::assertEquals($routeDocument->reveal(), $result);
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
        $document->setTarget($targetDocument->reveal())->shouldBeCalled();

        $metadata = $this->prophesize(Metadata::class);
        $metadata->getFieldMappings()->willReturn($this->getMapping());
        $metadataFactory->getMetadataForAlias('custom_urls')->willReturn($metadata);

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
            ['parent_path' => '/cmf/sulu_io/custom_urls/items', 'node_name' => 'test-1', 'load_ghost_content' => true]
        )->shouldBeCalledTimes(1);

        $result = $manager->update(
            '312-312-312',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']],
                'target' => ['uuid' => '123-123-123'],
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ],
            'en'
        );

        self::assertEquals($document->reveal(), $result);
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
        $document->setTarget($targetDocument->reveal())->shouldBeCalled();

        $metadata = $this->prophesize(Metadata::class);
        $metadata->getFieldMappings()->willReturn($this->getMapping());
        $metadataFactory->getMetadataForAlias('custom_urls')->willReturn($metadata);

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
            ['parent_path' => '/cmf/sulu_io/custom_urls/items', 'node_name' => 'test-1', 'load_ghost_content' => true]
        )->willThrow(new ItemExistsException());

        self::setExpectedException(TitleExistsException::class);

        $manager->update(
            '312-312-312',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']],
                'target' => ['uuid' => '123-123-123'],
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
        self::assertEquals($document->reveal(), $result);
    }

    public function testDeleteHistory()
    {
        self::setExpectedException(CannotDeleteCurrentRouteException::class);

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

        $pathBuilder->build(['%base%', 'sulu_io', '%custom-urls%', '%custom-urls-routes%'])
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
        $pathBuilder->build(['%base%', 'sulu_io', '%custom-urls%', '%custom-urls-routes%'])
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

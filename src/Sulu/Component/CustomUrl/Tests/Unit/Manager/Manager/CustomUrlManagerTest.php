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
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;

/**
 * Provides testcases for custom-url-manager.
 */
class CustomUrlManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var CustomUrlRepository
     */
    private $customUrlRepository;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    /**
     * @var HandlerInvalidatePathInterface
     */
    private $cacheHandler;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var PageDocument
     */
    private $targetDocument;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var CustomUrlManager
     */
    private $manager;

    protected function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->pathBuilder = $this->prophesize(PathBuilder::class);
        $this->cacheHandler = $this->prophesize(HandlerInvalidatePathInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->targetDocument = $this->prophesize(PageDocument::class)->reveal();
        $this->metadata = $this->prophesize(Metadata::class);

        $this->manager = new CustomUrlManager(
            $this->documentManager->reveal(),
            $this->customUrlRepository->reveal(),
            $this->metadataFactory->reveal(),
            $this->pathBuilder->reveal(),
            $this->cacheHandler->reveal(),
            $this->webspaceManager->reveal(),
            $this->environment
        );
    }

    public function testCreate()
    {
        $this->metadata->getFieldMappings()->willReturn($this->getMapping());
        $this->metadataFactory->getMetadataForAlias('custom_url')->willReturn($this->metadata);

        $testDocument = new CustomUrlDocument();
        $this->documentManager->create('custom_url')->willReturn($testDocument);
        $this->documentManager->persist(
            $testDocument,
            'en',
            ['parent_path' => '/cmf/sulu_io/custom_urls/items', 'load_ghost_content' => true, 'auto_rename' => false]
        )->shouldBeCalledTimes(1);
        $this->documentManager->publish($testDocument, 'en')->shouldBeCalled();
        $this->documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])->willReturn(
                $this->targetDocument
            );

        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $result = $this->manager->create(
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
        $this->assertEquals($this->targetDocument, $result->getTargetDocument());
        $this->assertTrue($result->isPublished());
        $this->assertTrue($result->isCanonical());
        $this->assertTrue($result->isRedirect());
    }

    public function testFindList()
    {
        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $this->customUrlRepository->findList('/cmf/sulu_io/custom_urls/items', 'de', ['*.sulu.io', 'sulu.io/*'])
            ->willReturn([['title' => 'Test-1'], ['title' => 'Test-2']]);

        $url1 = $this->prophesize(CustomUrl::class);
        $url1->getUrl()->willReturn('*.sulu.io');

        $url2 = $this->prophesize(CustomUrl::class);
        $url2->getUrl()->willReturn('sulu.io/*');

        $environment = $this->prophesize(Environment::class);
        $environment->getCustomUrls()->willReturn([$url1->reveal(), $url2->reveal()]);

        $portal = $this->prophesize(Portal::class);
        $portal->getEnvironment($this->environment)->willReturn($environment->reveal());

        $webspace = $this->prophesize(WebspaceManagerInterface::class);
        $webspace->getPortals()->willReturn([$portal->reveal()]);

        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace->reveal());

        $result = $this->manager->findList('sulu_io', 'de');

        $this->assertEquals([['title' => 'Test-1'], ['title' => 'Test-2']], $result);
    }

    public function testFindUrls()
    {
        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $this->customUrlRepository->findUrls('/cmf/sulu_io/custom_urls/items')
            ->willReturn(['1.sulu.lo', '1.sulu.lo/2']);

        $result = $this->manager->findUrls('sulu_io');

        $this->assertEquals(['1.sulu.lo', '1.sulu.lo/2'], $result);
    }

    public function testFind()
    {
        $document = $this->prophesize(CustomUrlDocument::class);

        $this->documentManager->find('123-123-123', 'de', ['load_ghost_content' => true])
            ->willReturn($document->reveal());

        $result = $this->manager->find('123-123-123', 'de');

        $this->assertEquals($document->reveal(), $result);
    }

    public function testFindByUrl()
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

    public function testFindRouteByUrl()
    {
        $routeDocument = $this->prophesize(RouteDocument::class);

        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom_urls/routes');

        $this->documentManager->find('/cmf/sulu_io/custom_urls/routes/sulu.io/test', 'de', ['load_ghost_content' => true])
            ->willReturn($routeDocument->reveal());

        $result = $this->manager->findRouteByUrl('sulu.io/test', 'sulu_io', 'de');

        $this->assertEquals($routeDocument->reveal(), $result);
    }

    public function testUpdate()
    {
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

        $result = $this->manager->save(
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
        )->willThrow(NodeNameAlreadyExistsException::class);

        $this->setExpectedException(TitleAlreadyExistsException::class);

        $this->manager->save(
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
        $document = $this->prophesize(CustomUrlDocument::class);

        $this->documentManager->find('123-123-123', null, ['load_ghost_content' => true])->willReturn($document->reveal());
        $this->documentManager->remove($document->reveal())->shouldBeCalled();

        $this->manager->delete('123-123-123');
    }

    public function testDeleteRoute()
    {
        $document = $this->prophesize(RouteDocument::class);
        $customUrlDocument = $this->prophesize(CustomUrlDocument::class);

        $document->isHistory()->willReturn(true);
        $document->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test');
        $document->getTargetDocument()->willReturn($customUrlDocument->reveal());

        $this->documentManager->find('123-123-123')->willReturn($document->reveal());
        $this->documentManager->remove($document->reveal())->shouldBeCalled();

        $result = $this->manager->deleteRoute('sulu_io', '123-123-123');
        $this->assertEquals($document->reveal(), $result);
    }

    public function testDeleteHistory()
    {
        $this->setExpectedException(RouteNotRemovableException::class);

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

    public function testInvalidate()
    {
        $document = $this->prophesize(CustomUrlDocument::class);

        $document->getRoutes()->willReturn(
            [
                'sulu.io/en' => $this->prophesize(RouteDocument::class)->reveal(),
                'sulu.io/de' => $this->prophesize(RouteDocument::class)->reveal(),
            ]
        );

        $this->manager->invalidate($document->reveal());

        $this->cacheHandler->invalidatePath('sulu.io/en')->shouldBeCalled();
        $this->cacheHandler->invalidatePath('sulu.io/de')->shouldBeCalled();
    }

    public function testInvalidateRoute()
    {
        $document = $this->prophesize(RouteDocument::class);

        $document->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/en');
        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom_urls/routes');

        $this->manager->invalidateRoute('sulu_io', $document->reveal());

        $this->cacheHandler->invalidatePath('sulu.io/en')->shouldBeCalled();
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

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

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PathBuilder;

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
            'multilingual' => ['property' => 'multilingual'],
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
            $pathBuilder->reveal()
        );

        $result = $manager->create(
            'sulu_io',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']],
                'target' => ['uuid' => '123-123-123'],
                'multilingual' => true,
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
        $this->assertEquals($targetDocument, $result->getTarget());
        $this->assertTrue($result->isPublished());
        $this->assertTrue($result->isMultilingual());
        $this->assertTrue($result->isCanonical());
        $this->assertTrue($result->isRedirect());
    }

    public function testReadList()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);

        $pathBuilder->build(['%base%', 'sulu_io', '%custom-urls%', '%custom-urls-items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal()
        );

        $customUrlRepository->findList('/cmf/sulu_io/custom_urls/items')
            ->willReturn([['title' => 'Test-1'], ['title' => 'Test-2']]);

        $result = $manager->readList('sulu_io');

        $this->assertEquals([['title' => 'Test-1'], ['title' => 'Test-2']], $result);
    }

    public function testRead()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $document = $this->prophesize(CustomUrlDocument::class);

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal()
        );

        $documentManager->find('123-123-123', 'de', ['load_ghost_content' => true])->willReturn($document->reveal());

        $result = $manager->read('123-123-123', 'de');

        $this->assertEquals($document->reveal(), $result);
    }

    public function testUpdate()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $targetDocument = $this->prophesize(PageDocument::class);

        $metadata = $this->prophesize(Metadata::class);
        $metadata->getFieldMappings()->willReturn($this->getMapping());
        $metadataFactory->getMetadataForAlias('custom_urls')->willReturn($metadata);

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $metadataFactory->reveal(),
            $pathBuilder->reveal()
        );

        $documentManager->find('312-312-312', 'en', ['load_ghost_content' => true])->willReturn($document->reveal());
        $documentManager->find('123-123-123', 'en', ['load_ghost_content' => true])->willReturn(
            $targetDocument->reveal()
        );
        $documentManager->persist($document, 'en')->shouldBeCalledTimes(1);

        $result = $manager->update(
            '312-312-312',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']],
                'target' => ['uuid' => '123-123-123'],
                'multilingual' => true,
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ],
            'en'
        );

        $document->setTitle('Test')->shouldBeCalled();
        $document->setPublished(true)->shouldBeCalled();
        $document->setMultilingual(true)->shouldBeCalled();
        $document->setRedirect(true)->shouldBeCalled();
        $document->setCanonical(true)->shouldBeCalled();
        $document->setLocale('en')->shouldBeCalled();
        $document->setTargetLocale('de')->shouldBeCalled();
        $document->setBaseDomain('*.sulu.io')->shouldBeCalled();
        $document->setDomainParts(['prefix' => 'test-1', 'postfix' => ['test-1', 'test-2']])->shouldBeCalled();
        $document->setTarget($targetDocument->reveal())->shouldBeCalled();

        $this->assertEquals($document->reveal(), $result);
    }
}

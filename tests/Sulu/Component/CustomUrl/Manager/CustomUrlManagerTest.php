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

use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\PathBuilder;

/**
 * Provides testcases for custom-url-manager.
 */
class CustomUrlManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);

        $testDocument = new CustomUrlDocument();
        $documentManager->create('custom_urls')->willReturn($testDocument);
        $documentManager->persist(
            $testDocument,
            null,
            ['parent_path' => '/cmf/sulu_io/custom_urls/items', 'node_name' => 'test']
        )
            ->shouldBeCalledTimes(1);

        $pathBuilder->build(['%base%', 'sulu_io', '%custom-urls%', '%custom-urls-items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $pathBuilder->reveal()
        );

        $result = $manager->create('sulu_io', ['title' => 'Test', 'published' => true]);

        $this->assertEquals($testDocument, $result);
        $this->assertEquals('Test', $result->getTitle());
        $this->assertEquals(true, $result->isPublished());
    }

    public function testReadList()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $pathBuilder = $this->prophesize(PathBuilder::class);

        $pathBuilder->build(['%base%', 'sulu_io', '%custom-urls%', '%custom-urls-items%'])
            ->willReturn('/cmf/sulu_io/custom_urls/items');

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $pathBuilder->reveal()
        );

        $customUrlRepository->findList('/cmf/sulu_io/custom_urls/items')
            ->willReturn([['title' => 'Test-1'], ['title' => 'Test-2']]);

        $result = $manager->readList('sulu_io');

        $this->assertEquals([['title' => 'Test-1'], ['title' => 'Test-2']], $result);
    }
}

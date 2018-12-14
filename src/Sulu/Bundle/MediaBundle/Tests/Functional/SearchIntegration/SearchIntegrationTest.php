<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\SearchIntegration;

use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\MediaBundle\Api\Media as ApiMedia;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\NodeManager;

class SearchIntegrationTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var HomeDocument
     */
    private $webspaceDocument;

    /**
     * @var ApiMedia
     */
    private $media;

    /**
     * @var MediaSelectionContainer
     */
    private $mediaSelectionContainer;

    public function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->nodeManager = $this->getContainer()->get('sulu_document_manager_test.node_manager');
        $this->webspaceDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $mediaEntity = new Media();
        $tagManager = $this->prophesize(TagManagerInterface::class);
        $this->media = new ApiMedia($mediaEntity, 'de', null, $tagManager);

        $this->mediaSelectionContainer = $this->prophesize(MediaSelectionContainer::class);
        $this->mediaSelectionContainer->getData('de')->willReturn([$this->media]);
        $this->mediaSelectionContainer->toArray()->willReturn(null);
    }

    public function provideIndex()
    {
        return [
            ['sulu-170x170', null],
            ['invalid', '\InvalidArgumentException'],
        ];
    }

    /**
     * @dataProvider provideIndex
     */
    public function testIndex($format, $expectException)
    {
        if ($expectException) {
            $this->expectException($expectException);
        }

        $this->media->setFormats([
            $format => 'myimage.jpg',
        ]);

        $testAdapter = $this->getContainer()->get('sulu_test.massive_search.adapter.test');

        // remove the documents indexed when creating the fixtures
        foreach ($testAdapter->listIndexes() as $indexName) {
            $testAdapter->purge($indexName);
        }

        $document = $this->documentManager->create('pages');
        $document->setTitle('Hallo');
        $document->setResourceSegment('/hallo/fo');
        $document->setStructureType('images');
        $document->setParent($this->webspaceDocument);
        $document->getStructure()->bind([
            'images' => $this->mediaSelectionContainer->reveal(),
        ], false);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $documents = $testAdapter->getDocuments();
        $this->assertCount(1, $documents);
        $document = end($documents);
        $this->assertInstanceOf('Massive\Bundle\SearchBundle\Search\Document', $document);
        $this->assertEquals('myimage.jpg', $document->getImageUrl());
    }

    public function testIndexNoMedia()
    {
        $testAdapter = $this->getContainer()->get('sulu_test.massive_search.adapter.test');

        $document = $this->documentManager->create('pages');
        $document->setStructureType('images');
        $document->setTitle('Hallo');
        $document->setResourceSegment('/hallo');
        $document->setParent($this->webspaceDocument);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $documents = $testAdapter->getDocuments();
        $document = end($documents);
        $this->assertInstanceOf('Massive\Bundle\SearchBundle\Search\Document', $document);
        $this->assertNull($document->getImageUrl());
    }
}

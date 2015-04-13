<?php

namespace Sulu\Bundle\MediaBundle\Tests\Functional\SearchIntegration;

use Prophecy\Argument;
use Sulu\Bundle\MediaBundle\Api\Media as ApiMedia;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class SearchIntegrationTest extends SuluTestCase
{
    private $documentManager;

    protected function getKernelConfiguration()
    {
        return array(
            'sulu_context' => 'website'
        );
    }

    public function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->container->get('sulu_document_manager.document_manager');
        $this->nodeManager = $this->container->get('sulu_document_manager.node_manager');
        $this->webspaceDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $mediaEntity = new Media();
        $tagManager = $this->getMock('Sulu\Bundle\TagBundle\Tag\TagManagerInterface');
        $this->media = new ApiMedia($mediaEntity, 'de', null, $tagManager);

        $this->mediaSelectionContainer = $this->prophesize(MediaSelectionContainer::class);
        $this->mediaSelectionContainer->getData('de')->willReturn(array($this->media));
        $this->mediaSelectionContainer->toArray()->willReturn(null);
    }

    public function provideIndex()
    {
        return array(
            array('170x170', null),
            array('invalid', '\InvalidArgumentException'),
        );
    }

    /**
     * @dataProvider provideIndex
     */
    public function testIndex($format, $expectedException)
    {
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }

        $this->media->setFormats(array(
            $format => 'myimage.jpg',
        ));

        $testAdapter = $this->container->get('massive_search.adapter.test');

        $document = $this->documentManager->create('page');
        $document->setTitle('Hallo');
        $document->setResourceSegment('/hallo/fo');
        $document->setStructureType('images');
        $document->setParent($this->webspaceDocument);
        $document->getContent()->bind(array(
            'images' => $this->mediaSelectionContainer->reveal()
        ), false);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $documents = $testAdapter->getDocuments();
        $this->assertCount(1, $documents);
        $document = current($documents);
        $this->assertInstanceOf('Massive\Bundle\SearchBundle\Search\Document', $document);
        $this->assertEquals('myimage.jpg', $document->getImageUrl());
    }

    public function testIndexNoMedia()
    {
        $document = $this->documentManager->create('page');
        $document->setStructureType('images');
        $document->setTitle('Hallo');
        $document->setResourceSegment('/hallo');
        $document->setParent($this->webspaceDocument);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();
    }
}

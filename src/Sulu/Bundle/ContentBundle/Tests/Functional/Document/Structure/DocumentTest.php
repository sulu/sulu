<?php

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Document\Structure;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class DocumentTest extends SuluTestCase
{
    /**
     * @var mixed
     */
    private $documentManager;

    public function setUp()
    {
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->nodeManager = $this->getContainer()->get('sulu_document_manager.node_manager');
        $this->structureFactory = $this->getContainer()->get('sulu_content.structure.factory');
        $this->initPhpcr();
    }

    public function testFoo()
    {
        $page = $this->documentManager->create('page');
        $page->setTitle('Hello');
        $page->setStructureType('contact');
        $page->setResourceSegment('/foo');
        $this->documentManager->persist($page, 'de', [
            'path' => '/cmf/sulu_io/contents/foo'
        ]);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $node = $this->nodeManager->find('/cmf/sulu_io/contents/foo');
        $node->setProperty('i18n:de-template', 'foo');
        $this->nodeManager->save();

        $document = $this->documentManager->find('/cmf/sulu_io/contents/foo');
        $metadata = $this->structureFactory->getStructureMetadata('page', $document->getStructureType());

        $this->assertFalse($metadata->isValid());
        $this->assertContains('The file "foo.xml" does not exist', $metadata->getExceptionMessage());
    }
}

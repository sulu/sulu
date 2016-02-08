<?php

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Routing;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Document\WorkflowStage;

class RouteSubscriberTest extends SuluTestCase
{
    private $manager;

    public function setUp()
    {
        $this->manager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->initPhpcr();
    }

    /**
     * It should generate auto route documents.
     */
    public function testGenerate()
    {
        $document = new PageDocument();
        $document->setStructureType('default');
        $document->setTitle('Hello world');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setResourceSegment('hello');

        $this->manager->persist($document, 'de', array(
            'path' => '/cmf/sulu_io/contents/foo'
        ));
        $this->manager->flush();
    }

    /**
     * It should update the URL on copy or move.
     */
    public function testCopyMove()
    {
        $this->markTestSkipped('TODO');
    }

}

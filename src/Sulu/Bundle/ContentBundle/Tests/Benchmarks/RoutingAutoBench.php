<?php

namespace Sulu\Bundle\ContentBundle\Tests\Benchmarks;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;

/**
 * @Revs(1)
 * @Warmup(1)
 * @Iterations(10)
 * @BeforeMethods({"setUp"})
 * @OutputTimeUnit("seconds")
 * @OutputMode("throughput")
 */
class RoutingAutoBench extends SuluTestCase
{
    private $manager;

    public function setUp()
    {
        $this->manager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->initPhpcr();
    }

    public function benchGenerate()
    {
        static $index = 0;

        $document = new PageDocument();
        $document->setStructureType('default');
        $document->setTitle('Hello world');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setResourceSegment('hello-' . $index++);

        $this->manager->persist($document, 'de', array(
            'path' => '/cmf/sulu_io/contents/foo' . $index
        ));

        $this->manager->flush();
    }
}

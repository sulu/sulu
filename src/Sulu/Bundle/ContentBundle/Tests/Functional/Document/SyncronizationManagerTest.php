<?php

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Document;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

use Sulu\Bundle\ContentBundle\Tests\app\MultipleDocumentManagerKernel;
use Sulu\Bundle\ContentBundle\Document\PageDocument;

class SyncronizationManagerTest extends SuluTestCase
{
    /**
     * @var mixed
     */
    private $manager;

    /**
     * @var mixed
     */
    private $syncManager;

    /**
     * @var mixed
     */
    private $publishDocumentManager;

    public function setUp()
    {
        $this->manager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->syncManager = $this->getContainer()->get('sulu_content.document.synchronization_manager');
        $this->publishDocumentManager = $this->syncManager->getPublishDocumentManager();
        $this->initPhpcr();
        $this->parent = $this->manager->find('/cmf/sulu_io/contents', 'de');
    }

    protected static function createKernel(array $options = [])
    {
        return parent::createKernel([ 'environment' => 'multiple_document_managers' ]);
    }

    /**
     * Assert that the test system is confgiured to use two separate document managers.
     */
    public function testSystemUsesTwoDocumentManagers()
    {
        $this->assertNotSame($this->manager, $this->syncManager->getPublishDocumentManager());
    }

    /**
     * New documents are automatically synced via. the subscriber.
     */
    public function testAutomaticSync()
    {
        $page = $this->createPage([
            'title' => 'Foobar',
            'integer' => 1234,
        ]);
        $page->setResourceSegment('/bar');

        $this->manager->persist($page, 'de');
        $this->manager->flush();

        $this->manager->find($page->getUuid(), 'de');
        $this->assertEquals(['live'], $page->getSynchronizedManagers());

        $this->assertExistsInPublishDocumentManager($page);
    }

    /**
     * It should update the published document when synchronized action is invoked.
     */
    public function testSynchronize()
    {
        $page = $this->createPage([
            'title' => 'Foobar',
            'integer' => 1234,
        ]);
        $page->setResourceSegment('/bar');

        $this->manager->persist($page, 'de');
        $this->manager->flush();

        $page->setTitle('Barbar');
        $this->manager->persist($page, 'de');
        $this->manager->flush();

        $this->syncManager->synchronizeFull($page);
        $this->assertExistsInPublishDocumentManager($page);

        $page = $this->publishDocumentManager->find($page->getUuid(), 'de');
        $this->assertEquals('Barbar', $page->getTitle());
    }


    /**
     * It should publish documents that have been moved in the default document manager.
     */
    public function testMovedInDefault()
    {
        $page = $this->createPage([
            'title' => 'Foobar',
            'integer' => 1234,
        ]);
        $page->setResourceSegment('/bar');

        $this->manager->persist($page, 'de');
        $this->manager->flush();
        $this->manager->getNodeManager()->createPath('/cmf/sulu_io/contents/foo/bar');
        $this->manager->move($page, '/cmf/sulu_io/contents/foo/bar');

        $this->syncManager->synchronizeFull($page);

        $this->assertExistsInPublishDocumentManager($page);
        $page = $this->publishDocumentManager->find($page->getUuid(), 'de');
        $this->assertEquals('/cmf/sulu_io/contents/foo/bar/foobar', $page->getPath());
    }

    private function createPage($data)
    {
        $page = new PageDocument();

        $page->setTitle($data['title']);
        $page->setParent($this->parent);
        $page->setStructureType('contact');
        $page->setResourceSegment('/foo');
        $page->getStructure()->bind($data, true);

        return $page;
    }

    private function assertExistsInPublishDocumentManager($document)
    {
        $path = $this->manager->getInspector()->getPath($document);
        $this->assertTrue($this->publishDocumentManager->getNodeManager()->has($path), sprintf('Document "%s" exists in PDM', $path));
    }

    private function assertNotExistsInPublishDocumentManager($document)
    {
        $path = $this->manager->getInspector()->getPath($document);
        $this->assertFalse($this->publishDocumentManager->getNodeManager()->has($path), 'Page does not exist in PDM');
    }
}

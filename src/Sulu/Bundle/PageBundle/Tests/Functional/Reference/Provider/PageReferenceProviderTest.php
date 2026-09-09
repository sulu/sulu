<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Reference\Provider;

use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Reference\Provider\PageReferenceProvider;
use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\PHPCR\SessionManager\SessionManager;

class PageReferenceProviderTest extends SuluTestCase
{
    private PageReferenceProvider $pageReferenceProvider;
    private DocumentManagerInterface $documentManager;
    private SessionManager $sessionManager;

    /**
     * @var EntityRepository<Reference>
     */
    private EntityRepository $referenceRepository;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $this->initPhpcr();

        $this->pageReferenceProvider = $this->getContainer()->get('sulu_page.reference_provider');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->referenceRepository = $this->getContainer()->get('sulu.repository.reference');
    }

    public function testUpdateReferences(): void
    {
        /** @var SnippetDocument $snippet */
        $snippet = $this->documentManager->create('snippet');
        $snippet->setStructureType('animal');
        $snippet->setTitle('Example snippet');
        $snippet->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($snippet, 'en');

        /** @var PageDocument $page */
        $page = $this->documentManager->create('page');
        $page->setTitle('Example page');
        $page->setLocale('en');
        $page->setStructureType('test_page');
        $page->setResourceSegment('/example-page-123');
        $page->setParent($this->documentManager->find($this->sessionManager->getContentPath('sulu_io')));

        $page->getStructure()->bind([
            'title' => 'Example page',
            'template' => 'test_page',
            'url' => '/test',
            'animals' => [$snippet->getUuid()],
        ]);

        $this->documentManager->persist($page, 'en');
        $this->documentManager->publish($page, 'en');
        $this->documentManager->flush();

        $this->pageReferenceProvider->updateReferences($page, 'en', 'test');
        $this->getEntityManager()->flush();

        /** @var Reference[] $references */
        $references = $this->referenceRepository->findBy(['referenceContext' => 'test']);
        $this->assertCount(1, $references);
        self::assertSame($snippet->getUuid(), $references[0]->getResourceId());
    }

    public function testUpdatePageAndTeaserReferences(): void
    {
        /** @var PageDocument $targetPage */
        $targetPage = $this->documentManager->create('page');
        $targetPage->setTitle('Target page');
        $targetPage->setLocale('en');
        $targetPage->setStructureType('test_page');
        $targetPage->setResourceSegment('/target-page');
        $targetPage->setParent($this->documentManager->find($this->sessionManager->getContentPath('sulu_io')));
        $targetPage->getStructure()->bind([
            'title' => 'Target page',
            'template' => 'test_page',
            'url' => '/target-page',
        ]);
        $this->documentManager->persist($targetPage, 'en');
        $this->documentManager->publish($targetPage, 'en');
        $this->documentManager->flush();

        /** @var PageDocument $page */
        $page = $this->documentManager->create('page');
        $page->setTitle('Example page');
        $page->setLocale('en');
        $page->setStructureType('test_page');
        $page->setResourceSegment('/example-page-123');
        $page->setParent($this->documentManager->find($this->sessionManager->getContentPath('sulu_io')));

        $page->getStructure()->bind([
            'title' => 'Example page',
            'template' => 'test_page',
            'url' => '/test',
            'page' => $targetPage->getUuid(),
            'teaser' => ['items' => [['type' => 'pages', 'id' => $targetPage->getUuid()]]],
        ]);

        $this->documentManager->persist($page, 'en');
        $this->documentManager->publish($page, 'en');
        $this->documentManager->flush();

        $this->pageReferenceProvider->updateReferences($page, 'en', 'test');
        $this->getEntityManager()->flush();

        /** @var Reference[] $references */
        $references = $this->referenceRepository->findBy(['referenceContext' => 'test'], ['referenceProperty' => 'ASC']);

        // one reference from the single_page_selection, one from the teaser_selection, both pointing at the target page
        $this->assertCount(2, $references);
        foreach ($references as $reference) {
            self::assertSame(PageDocument::RESOURCE_KEY, $reference->getResourceKey());
            self::assertSame($targetPage->getUuid(), $reference->getResourceId());
        }
    }

    public function testUpdateUnpublishedReferences(): void
    {
        /** @var SnippetDocument $snippet */
        $snippet = $this->documentManager->create('snippet');
        $snippet->setStructureType('animal');
        $snippet->setTitle('Example snippet');
        $snippet->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($snippet, 'en');

        /** @var PageDocument $page */
        $page = $this->documentManager->create('page');
        $page->setTitle('Example page');
        $page->setLocale('en');
        $page->setStructureType('test_page');
        $page->setResourceSegment('/example-page-123');
        $page->setParent($this->documentManager->find($this->sessionManager->getContentPath('sulu_io')));

        $page->getStructure()->bind([
            'title' => 'Example page',
            'template' => 'test_page',
            'url' => '/test',
            'animals' => [$snippet->getUuid()],
        ]);

        $this->documentManager->persist($page, 'en');
        $this->documentManager->publish($page, 'en');
        $this->documentManager->flush();

        $this->documentManager->unpublish($page, 'en');
        $this->documentManager->flush();
        $this->documentManager->clear();

        static::ensureKernelShutdown();
        static::bootKernel(['sulu.context' => SuluKernel::CONTEXT_WEBSITE]);
        // refrech services from new kernel
        $this->pageReferenceProvider = $this->getContainer()->get('sulu_page.reference_provider');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->referenceRepository = $this->getContainer()->get('sulu.repository.reference');

        /** @var PageDocument $page */
        $page = $this->documentManager->find($page->getUuid(), 'en', [
            'load_ghost_content' => false,
        ]);

        $this->pageReferenceProvider->updateReferences($page, 'en', 'test');
        $this->getEntityManager()->flush();

        $references = $this->referenceRepository->findBy(['referenceContext' => 'test']);
        $this->assertCount(0, $references);
    }
}

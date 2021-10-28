<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Trash\SnippetTrashItemHandler;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class SnippetTrashItemHandlerTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SnippetTrashItemHandler
     */
    private $snippetTrashItemHandler;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function setUp(): void
    {
        static::purgeDatabase();
        static::initPhpcr();

        $this->documentManager = static::getContainer()->get('sulu_document_manager.document_manager');
        $this->snippetTrashItemHandler = static::getContainer()->get('sulu_snippet.snippet_trash_item_handler');
        $this->entityManager = static::getEntityManager();
    }

    public function testStoreAndRestore(): void
    {
        /** @var SnippetDocument $snippet1De */
        $snippet1De = $this->documentManager->create(Structure::TYPE_SNIPPET);
        $snippet1De->setTitle('test-title-de');
        $snippet1De->setLocale('de');
        $snippet1De->setCreator(101);
        $snippet1De->setCreated(new \DateTime('1999-04-20'));
        $snippet1De->setStructureType('car');
        $snippet1De->getStructure()->bind([
            'description' => 'german description content',
        ]);
        $snippet1De->setExtensionsData([
            'excerpt' => [
                'title' => 'excerpt title de',
            ],
            'seo' => [
                'title' => 'seo title de',
            ],
        ]);
        $this->documentManager->persist($snippet1De, 'de');

        /** @var SnippetDocument $snippet1En */
        $snippet1En = $this->documentManager->find($snippet1De->getUuid(), 'en', ['load_ghost_content' => false]);
        $snippet1En->setTitle('test-title-en');
        $snippet1En->setLocale('en');
        $snippet1En->setCreator(303);
        $snippet1En->setCreated(new \DateTime('1999-04-22'));
        $snippet1En->setStructureType('car');
        $snippet1En->getStructure()->bind([
            'description' => 'english description content',
        ]);
        $snippet1En->setExtensionsData([
            'excerpt' => [
                'title' => 'excerpt title en',
            ],
            'seo' => [
                'title' => 'seo title en',
            ],
        ]);
        $this->documentManager->persist($snippet1En, 'en');

        /** @var SnippetDocument $snippet2De */
        $snippet2De = $this->documentManager->create(Structure::TYPE_SNIPPET);
        $snippet2De->setTitle('second snippet');
        $snippet2De->setLocale('de');
        $snippet2De->setStructureType('hotel');
        $this->documentManager->persist($snippet2De, 'de');

        $this->documentManager->flush();
        $originalSnippetUuid = $snippet1De->getUuid();

        $trashItem = $this->snippetTrashItemHandler->store($snippet1De);
        $this->documentManager->remove($snippet1De);
        $this->documentManager->flush();
        $this->documentManager->clear();

        static::assertSame($originalSnippetUuid, $trashItem->getResourceId());
        static::assertSame('test-title-de', $trashItem->getResourceTitle());
        static::assertSame('test-title-en', $trashItem->getResourceTitle('en'));
        static::assertSame('test-title-de', $trashItem->getResourceTitle('de'));

        /** @var SnippetDocument $restoredSnippet */
        $restoredSnippet = $this->snippetTrashItemHandler->restore($trashItem, []);
        static::assertSame($originalSnippetUuid, $restoredSnippet->getUuid());

        /** @var SnippetDocument $restoredSnippetDe */
        $restoredSnippetDe = $this->documentManager->find($originalSnippetUuid, 'de');
        static::assertSame($originalSnippetUuid, $restoredSnippetDe->getUuid());
        static::assertSame('test-title-de', $restoredSnippetDe->getTitle());
        static::assertSame('de', $restoredSnippetDe->getLocale());
        static::assertSame(101, $restoredSnippetDe->getCreator());
        static::assertSame('1999-04-20T00:00:00+00:00', $restoredSnippetDe->getCreated()->format('c'));
        static::assertSame('car', $restoredSnippetDe->getStructureType());
        static::assertSame('german description content', $restoredSnippetDe->getStructure()->toArray()['description']);
        static::assertSame('excerpt title de', $restoredSnippetDe->getExtensionsData()['excerpt']['title']);
        static::assertSame('seo title de', $restoredSnippetDe->getExtensionsData()['seo']['title']);

        /** @var SnippetDocument $restoredSnippetEn */
        $restoredSnippetEn = $this->documentManager->find($originalSnippetUuid, 'en');
        static::assertSame($originalSnippetUuid, $restoredSnippetEn->getUuid());
        static::assertSame('test-title-en', $restoredSnippetEn->getTitle());
        static::assertSame('en', $restoredSnippetEn->getLocale());
        static::assertSame(303, $restoredSnippetEn->getCreator());
        static::assertSame('1999-04-22T00:00:00+00:00', $restoredSnippetEn->getCreated()->format('c'));
        static::assertSame('car', $restoredSnippetEn->getStructureType());
        static::assertSame('english description content', $restoredSnippetEn->getStructure()->toArray()['description']);
        static::assertSame('excerpt title en', $restoredSnippetEn->getExtensionsData()['excerpt']['title']);
        static::assertSame('seo title en', $restoredSnippetEn->getExtensionsData()['seo']['title']);
    }
}

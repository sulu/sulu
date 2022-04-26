<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Trash\PageTrashItemHandler;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class PageTrashItemHandlerTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var PageTrashItemHandler
     */
    private $pageTrashItemHandler;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ObjectRepository<ActivityInterface>
     */
    private $activityRepository;

    public function setUp(): void
    {
        static::purgeDatabase();
        static::initPhpcr();

        $this->documentManager = static::getContainer()->get('sulu_document_manager.document_manager');
        $this->pageTrashItemHandler = static::getContainer()->get('sulu_page.page_trash_item_handler');
        $this->entityManager = static::getEntityManager();
        $this->activityRepository = $this->entityManager->getRepository(ActivityInterface::class);
    }

    public function testStoreAndRestore(): void
    {
        $role = $this->createRole();
        $homepageDocument = $this->documentManager->find('/cmf/test_io/contents');

        /** @var PageDocument $page1De */
        $page1De = $this->documentManager->create(Structure::TYPE_PAGE);
        $page1De->setParent($homepageDocument);
        $page1De->setTitle('test-title-de');
        $page1De->setResourceSegment('test-resource-segment-de');
        $page1De->setSuluOrder(555);
        $page1De->setLocale('de');
        $page1De->setCreator(101);
        $page1De->setCreated(new \DateTime('1999-04-20'));
        $page1De->setAuthor(202);
        $page1De->setAuthored(new \DateTime('2000-04-20'));
        $page1De->setStructureType('article');
        $page1De->getStructure()->bind([
            'article' => 'german article content',
        ]);
        $page1De->setExtensionsData([
            'excerpt' => [
                'title' => 'excerpt title de',
            ],
            'seo' => [
                'title' => 'seo title de',
            ],
        ]);
        $page1De->setPermissions([
            $role->getId() => [
                'view' => true,
                'edit' => true,
                'delete' => false,
            ],
        ]);
        $page1De->setNavigationContexts(['main', 'other']);
        $this->documentManager->persist($page1De, 'de');

        /** @var PageDocument $page1En */
        $page1En = $this->documentManager->find($page1De->getUuid(), 'en', ['load_ghost_content' => false]);
        $page1En->setTitle('test-title-en');
        $page1En->setResourceSegment('test-resource-segment-en');
        $page1En->setLocale('en');
        $page1En->setCreator(303);
        $page1En->setCreated(new \DateTime('1999-04-22'));
        $page1En->setAuthor(404);
        $page1En->setAuthored(new \DateTime('2000-04-22'));
        $page1En->setStructureType('article');
        $page1En->getStructure()->bind([
            'article' => 'english article content',
        ]);
        $page1En->setExtensionsData([
            'excerpt' => [
                'title' => 'excerpt title en',
            ],
            'seo' => [
                'title' => 'seo title en',
            ],
        ]);
        $page1En->setNavigationContexts(['other']);
        $this->documentManager->persist($page1En, 'en');

        /** @var PageDocument $page2De */
        $page2De = $this->documentManager->create(Structure::TYPE_PAGE);
        $page2De->setParent($homepageDocument);
        $page2De->setTitle('second page');
        $page2De->setResourceSegment('second-page');
        $page2De->setLocale('de');
        $page2De->setStructureType('default');
        $this->documentManager->persist($page2De, 'de');

        $this->documentManager->flush();
        $originalPageUuid = $page1De->getUuid();

        $trashItem = $this->pageTrashItemHandler->store($page1De);
        $this->documentManager->remove($page1De);
        $this->documentManager->flush();
        $this->documentManager->clear();

        static::assertSame($originalPageUuid, $trashItem->getResourceId());
        static::assertSame('test-title-de', $trashItem->getResourceTitle());
        static::assertSame('test-title-en', $trashItem->getResourceTitle('en'));
        static::assertSame('test-title-de', $trashItem->getResourceTitle('de'));
        static::assertSame([], $trashItem->getRestoreOptions());

        /** @var PageDocument $restoredPage */
        $restoredPage = $this->pageTrashItemHandler->restore($trashItem, ['parentUuid' => $page2De->getUuid()]);
        /** @var BasePageDocument $restoredPageParent */
        $restoredPageParent = $restoredPage->getParent();
        static::assertSame($originalPageUuid, $restoredPage->getUuid());
        static::assertSame($page2De->getUuid(), $restoredPageParent->getUuid());

        /** @var PageDocument $restoredPageDe */
        $restoredPageDe = $this->documentManager->find($originalPageUuid, 'de');
        /** @var BasePageDocument $restoredPageDeParent */
        $restoredPageDeParent = $restoredPageDe->getParent();
        static::assertSame($originalPageUuid, $restoredPageDe->getUuid());
        static::assertSame($page2De->getUuid(), $restoredPageDeParent->getUuid());
        static::assertSame('test-title-de', $restoredPageDe->getTitle());
        static::assertSame('test-resource-segment-de', $restoredPageDe->getResourceSegment());
        static::assertSame(555, $restoredPageDe->getSuluOrder());
        static::assertSame('de', $restoredPageDe->getLocale());
        static::assertSame(101, $restoredPageDe->getCreator());
        static::assertSame('1999-04-20T00:00:00+00:00', $restoredPageDe->getCreated()->format('c'));
        static::assertSame(202, $restoredPageDe->getAuthor());
        static::assertSame('2000-04-20T00:00:00+00:00', $restoredPageDe->getAuthored()->format('c'));
        static::assertSame('article', $restoredPageDe->getStructureType());
        static::assertSame('german article content', $restoredPageDe->getStructure()->toArray()['article']);
        static::assertSame('excerpt title de', $restoredPageDe->getExtensionsData()['excerpt']['title']);
        static::assertSame('seo title de', $restoredPageDe->getExtensionsData()['seo']['title']);
        static::assertSame(
            [
                $role->getId() => [
                    'view' => true,
                    'add' => false,
                    'edit' => true,
                    'delete' => false,
                    'archive' => false,
                    'live' => false,
                    'security' => false,
                ],
            ],
            $restoredPageDe->getPermissions()
        );
        static::assertSame(['main', 'other'], $restoredPageDe->getNavigationContexts());

        /** @var PageDocument $restoredPageEn */
        $restoredPageEn = $this->documentManager->find($originalPageUuid, 'en');
        /** @var BasePageDocument $restoredPageEnParent */
        $restoredPageEnParent = $restoredPageEn->getParent();
        static::assertSame($originalPageUuid, $restoredPageEn->getUuid());
        static::assertSame($page2De->getUuid(), $restoredPageEnParent->getUuid());
        static::assertSame('test-title-en', $restoredPageEn->getTitle());
        static::assertSame('test-resource-segment-en', $restoredPageEn->getResourceSegment());
        static::assertSame('en', $restoredPageEn->getLocale());
        static::assertSame(303, $restoredPageEn->getCreator());
        static::assertSame('1999-04-22T00:00:00+00:00', $restoredPageEn->getCreated()->format('c'));
        static::assertSame(404, $restoredPageEn->getAuthor());
        static::assertSame('2000-04-22T00:00:00+00:00', $restoredPageEn->getAuthored()->format('c'));
        static::assertSame('article', $restoredPageEn->getStructureType());
        static::assertSame('english article content', $restoredPageEn->getStructure()->toArray()['article']);
        static::assertSame('excerpt title en', $restoredPageEn->getExtensionsData()['excerpt']['title']);
        static::assertSame('seo title en', $restoredPageEn->getExtensionsData()['seo']['title']);
        static::assertSame(
            [
                $role->getId() => [
                    'view' => true,
                    'add' => false,
                    'edit' => true,
                    'delete' => false,
                    'archive' => false,
                    'live' => false,
                    'security' => false,
                ],
            ],
            $restoredPageEn->getPermissions()
        );
        static::assertSame(['other'], $restoredPageEn->getNavigationContexts());

        $activity = $this->activityRepository->findOneBy(['type' => 'restored']);
        $this->assertNotNull($activity);
        $this->assertSame($originalPageUuid, $activity->getResourceId());
    }

    public function testStoreAndRestoreShadowPage(): void
    {
        /** @var BasePageDocument $homepageDocument */
        $homepageDocument = $this->documentManager->find('/cmf/test_io/contents');
        $homepageUuid = $homepageDocument->getUuid();

        /** @var PageDocument $pageDe */
        $pageDe = $this->documentManager->create(Structure::TYPE_PAGE);
        $pageDe->setParent($homepageDocument);
        $pageDe->setTitle('target-locale-title');
        $pageDe->setResourceSegment('target-locale-resource-segment-de');
        $pageDe->setLocale('de');
        $pageDe->setStructureType('default');
        $this->documentManager->persist($pageDe, 'de');

        /** @var PageDocument $pageEn */
        $pageEn = $this->documentManager->find($pageDe->getUuid(), 'en', ['load_ghost_content' => false]);
        $pageEn->setParent($homepageDocument);
        $pageEn->setTitle('source-locale-title');
        $pageEn->setResourceSegment('source-locale-resource-segment-en');
        $pageEn->setLocale('en');
        $pageEn->setShadowLocaleEnabled(true);
        $pageEn->setShadowLocale('de');
        $this->documentManager->persist($pageEn, 'en');

        $this->documentManager->flush();

        $trashItem = $this->pageTrashItemHandler->store($pageDe);
        $this->documentManager->remove($pageDe);
        $this->documentManager->flush();
        $this->documentManager->clear();

        /** @var PageDocument $restoredPage */
        $restoredPage = $this->pageTrashItemHandler->restore($trashItem, ['parentUuid' => $homepageUuid]);
        /** @var BasePageDocument $restoredPageParent */
        $restoredPageParent = $restoredPage->getParent();
        static::assertSame($homepageUuid, $restoredPageParent->getUuid());

        /** @var PageDocument $restoredPageDe */
        $restoredPageDe = $this->documentManager->find($restoredPage->getUuid(), 'de');
        static::assertSame('target-locale-title', $restoredPageDe->getTitle());
        static::assertSame('target-locale-resource-segment-de', $restoredPageDe->getResourceSegment());
        static::assertSame('de', $restoredPageDe->getLocale());
        static::assertSame('de', $restoredPageDe->getOriginalLocale());
        static::assertNull($restoredPageDe->getShadowLocale());

        /** @var PageDocument $restoredPageEn */
        $restoredPageEn = $this->documentManager->find($restoredPage->getUuid(), 'en');
        static::assertSame('target-locale-title', $restoredPageEn->getTitle());
        static::assertSame('source-locale-resource-segment-en', $restoredPageEn->getResourceSegment());
        static::assertSame('de', $restoredPageEn->getLocale());
        static::assertSame('en', $restoredPageEn->getOriginalLocale());
        static::assertSame('de', $restoredPageEn->getShadowLocale());
    }

    public function testStoreAndRestoreInternalLinkPage(): void
    {
        /** @var BasePageDocument $homepageDocument */
        $homepageDocument = $this->documentManager->find('/cmf/test_io/contents');
        $homepageUuid = $homepageDocument->getUuid();

        /** @var PageDocument $pageDe */
        $pageDe = $this->documentManager->create(Structure::TYPE_PAGE);
        $pageDe->setParent($homepageDocument);
        $pageDe->setTitle('content-locale-title');
        $pageDe->setResourceSegment('content-locale-resource-segment');
        $pageDe->setLocale('de');
        $pageDe->setStructureType('default');
        $this->documentManager->persist($pageDe, 'de');

        /** @var PageDocument $pageEn */
        $pageEn = $this->documentManager->find($pageDe->getUuid(), 'en', ['load_ghost_content' => false]);
        $pageEn->setParent($homepageDocument);
        $pageEn->setTitle('internal-link-locale-title');
        $pageEn->setResourceSegment('internal-link-locale-resource-segment');
        $pageEn->setLocale('en');
        $pageEn->setRedirectType(RedirectType::INTERNAL);
        $pageEn->setRedirectTarget($homepageDocument);
        $this->documentManager->persist($pageEn, 'en');

        $this->documentManager->flush();

        $trashItem = $this->pageTrashItemHandler->store($pageDe);
        $this->documentManager->remove($pageDe);
        $this->documentManager->flush();
        $this->documentManager->clear();

        /** @var PageDocument $restoredPage */
        $restoredPage = $this->pageTrashItemHandler->restore($trashItem, ['parentUuid' => $homepageUuid]);
        /** @var BasePageDocument $restoredPageParent */
        $restoredPageParent = $restoredPage->getParent();
        static::assertSame($homepageUuid, $restoredPageParent->getUuid());

        /** @var PageDocument $restoredPageDe */
        $restoredPageDe = $this->documentManager->find($restoredPage->getUuid(), 'de');
        static::assertSame('content-locale-title', $restoredPageDe->getTitle());
        static::assertSame('content-locale-resource-segment', $restoredPageDe->getResourceSegment());
        static::assertSame(RedirectType::NONE, $restoredPageDe->getRedirectType());
        static::assertNull($restoredPageDe->getRedirectTarget());
        static::assertNull($restoredPageDe->getRedirectExternal());

        /** @var PageDocument $restoredPageEn */
        $restoredPageEn = $this->documentManager->find($restoredPage->getUuid(), 'en');
        /** @var BasePageDocument|null $restoredPageEnRedirectTarget */
        $restoredPageEnRedirectTarget = $restoredPageEn->getRedirectTarget();
        static::assertSame('internal-link-locale-title', $restoredPageEn->getTitle());
        static::assertSame('internal-link-locale-resource-segment', $restoredPageEn->getResourceSegment());
        static::assertSame(RedirectType::INTERNAL, $restoredPageEn->getRedirectType());
        static::assertNotNull($restoredPageEnRedirectTarget);
        static::assertSame($homepageUuid, $restoredPageEnRedirectTarget->getUuid());
        static::assertNull($restoredPageEn->getRedirectExternal());
    }

    public function testStoreAndRestoreExternalLinkPage(): void
    {
        /** @var BasePageDocument $homepageDocument */
        $homepageDocument = $this->documentManager->find('/cmf/test_io/contents');
        $homepageUuid = $homepageDocument->getUuid();

        /** @var PageDocument $pageDe */
        $pageDe = $this->documentManager->create(Structure::TYPE_PAGE);
        $pageDe->setParent($homepageDocument);
        $pageDe->setTitle('content-locale-title');
        $pageDe->setResourceSegment('content-locale-resource-segment');
        $pageDe->setLocale('de');
        $pageDe->setStructureType('default');
        $this->documentManager->persist($pageDe, 'de');

        /** @var PageDocument $pageEn */
        $pageEn = $this->documentManager->find($pageDe->getUuid(), 'en', ['load_ghost_content' => false]);
        $pageEn->setParent($homepageDocument);
        $pageEn->setTitle('external-link-locale-title');
        $pageEn->setResourceSegment('external-link-locale-resource-segment');
        $pageEn->setLocale('en');
        $pageEn->setRedirectType(RedirectType::EXTERNAL);
        $pageEn->setRedirectExternal('www.google.com');
        $this->documentManager->persist($pageEn, 'en');

        $this->documentManager->flush();

        $trashItem = $this->pageTrashItemHandler->store($pageDe);
        $this->documentManager->remove($pageDe);
        $this->documentManager->flush();
        $this->documentManager->clear();

        /** @var PageDocument $restoredPage */
        $restoredPage = $this->pageTrashItemHandler->restore($trashItem, ['parentUuid' => $homepageUuid]);
        /** @var BasePageDocument $restoredPageParent */
        $restoredPageParent = $restoredPage->getParent();
        static::assertSame($homepageUuid, $restoredPageParent->getUuid());

        /** @var PageDocument $restoredPageDe */
        $restoredPageDe = $this->documentManager->find($restoredPage->getUuid(), 'de');
        static::assertSame('content-locale-title', $restoredPageDe->getTitle());
        static::assertSame('content-locale-resource-segment', $restoredPageDe->getResourceSegment());
        static::assertSame(RedirectType::NONE, $restoredPageDe->getRedirectType());
        static::assertNull($restoredPageDe->getRedirectTarget());
        static::assertNull($restoredPageDe->getRedirectExternal());

        /** @var PageDocument $restoredPageEn */
        $restoredPageEn = $this->documentManager->find($restoredPage->getUuid(), 'en');
        static::assertSame('external-link-locale-title', $restoredPageEn->getTitle());
        static::assertSame('external-link-locale-resource-segment', $restoredPageEn->getResourceSegment());
        static::assertSame(RedirectType::EXTERNAL, $restoredPageEn->getRedirectType());
        static::assertNull($restoredPageEn->getRedirectTarget());
        static::assertSame('www.google.com', $restoredPageEn->getRedirectExternal());
    }

    public function testStoreAndRestoreSingleTranslation(): void
    {
        $role = $this->createRole();
        /** @var HomeDocument $homepageDocument */
        $homepageDocument = $this->documentManager->find('/cmf/test_io/contents');

        /** @var PageDocument $page1De */
        $page1De = $this->documentManager->create(Structure::TYPE_PAGE);
        $page1De->setParent($homepageDocument);
        $page1De->setTitle('test-title-de');
        $page1De->setResourceSegment('test-resource-segment-de');
        $page1De->setSuluOrder(555);
        $page1De->setLocale('de');
        $page1De->setCreator(101);
        $page1De->setCreated(new \DateTime('1999-04-20'));
        $page1De->setAuthor(202);
        $page1De->setAuthored(new \DateTime('2000-04-20'));
        $page1De->setStructureType('article');
        $page1De->getStructure()->bind([
            'article' => 'german article content',
        ]);
        $page1De->setExtensionsData([
            'excerpt' => [
                'title' => 'excerpt title de',
            ],
            'seo' => [
                'title' => 'seo title de',
            ],
        ]);
        $page1De->setPermissions([
            $role->getId() => [
                'view' => true,
                'edit' => true,
                'delete' => false,
            ],
        ]);
        $page1De->setNavigationContexts(['main', 'other']);
        $this->documentManager->persist($page1De, 'de');

        /** @var PageDocument $page1En */
        $page1En = $this->documentManager->find($page1De->getUuid(), 'en', ['load_ghost_content' => false]);
        $page1En->setTitle('test-title-en');
        $page1En->setResourceSegment('test-resource-segment-en');
        $page1En->setLocale('en');
        $page1En->setCreator(303);
        $page1En->setCreated(new \DateTime('1999-04-22'));
        $page1En->setAuthor(404);
        $page1En->setAuthored(new \DateTime('2000-04-22'));
        $page1En->setStructureType('article');
        $page1En->getStructure()->bind([
            'article' => 'english article content',
        ]);
        $page1En->setExtensionsData([
            'excerpt' => [
                'title' => 'excerpt title en',
            ],
            'seo' => [
                'title' => 'seo title en',
            ],
        ]);
        $page1En->setNavigationContexts(['other']);
        $this->documentManager->persist($page1En, 'en');

        $this->documentManager->flush();
        $originalPageUuid = $page1De->getUuid();

        $trashItem = $this->pageTrashItemHandler->store($page1En, ['locale' => 'en']);
        $this->documentManager->removeLocale($page1En, 'en');
        $this->documentManager->flush();
        $this->documentManager->clear();

        static::assertSame($originalPageUuid, $trashItem->getResourceId());
        static::assertSame('test-title-en', $trashItem->getResourceTitle());
        static::assertSame('test-title-en', $trashItem->getResourceTitle('en'));
        static::assertSame(['locale' => 'en'], $trashItem->getRestoreOptions());

        /** @var PageDocument $restoredPage */
        $restoredPage = $this->pageTrashItemHandler->restore($trashItem, ['parentUuid' => $homepageDocument->getUuid()]);
        /** @var BasePageDocument $restoredPageParent */
        $restoredPageParent = $restoredPage->getParent();
        static::assertSame($originalPageUuid, $restoredPage->getUuid());
        static::assertSame($homepageDocument->getUuid(), $restoredPageParent->getUuid());

        /** @var PageDocument $restoredPageDe */
        $restoredPageDe = $this->documentManager->find($originalPageUuid, 'de');
        /** @var BasePageDocument $restoredPageDeParent */
        $restoredPageDeParent = $restoredPageDe->getParent();
        static::assertSame($originalPageUuid, $restoredPageDe->getUuid());
        static::assertSame($homepageDocument->getUuid(), $restoredPageDeParent->getUuid());
        static::assertSame('test-title-de', $restoredPageDe->getTitle());
        static::assertSame('test-resource-segment-de', $restoredPageDe->getResourceSegment());
        static::assertSame(555, $restoredPageDe->getSuluOrder());
        static::assertSame('de', $restoredPageDe->getLocale());
        static::assertSame(101, $restoredPageDe->getCreator());
        static::assertSame('1999-04-20T00:00:00+00:00', $restoredPageDe->getCreated()->format('c'));
        static::assertSame(202, $restoredPageDe->getAuthor());
        static::assertSame('2000-04-20T00:00:00+00:00', $restoredPageDe->getAuthored()->format('c'));
        static::assertSame('article', $restoredPageDe->getStructureType());
        static::assertSame('german article content', $restoredPageDe->getStructure()->toArray()['article']);
        static::assertSame('excerpt title de', $restoredPageDe->getExtensionsData()['excerpt']['title']);
        static::assertSame('seo title de', $restoredPageDe->getExtensionsData()['seo']['title']);
        static::assertSame(
            [
                $role->getId() => [
                    'view' => true,
                    'add' => false,
                    'edit' => true,
                    'delete' => false,
                    'archive' => false,
                    'live' => false,
                    'security' => false,
                ],
            ],
            $restoredPageDe->getPermissions()
        );
        static::assertSame(['main', 'other'], $restoredPageDe->getNavigationContexts());

        /** @var PageDocument $restoredPageEn */
        $restoredPageEn = $this->documentManager->find($originalPageUuid, 'en');
        /** @var BasePageDocument $restoredPageEnParent */
        $restoredPageEnParent = $restoredPageEn->getParent();
        static::assertSame($originalPageUuid, $restoredPageEn->getUuid());
        static::assertSame($homepageDocument->getUuid(), $restoredPageEnParent->getUuid());
        static::assertSame('test-title-en', $restoredPageEn->getTitle());
        static::assertSame('test-resource-segment-en', $restoredPageEn->getResourceSegment());
        static::assertSame('en', $restoredPageEn->getLocale());
        static::assertSame(303, $restoredPageEn->getCreator());
        static::assertSame('1999-04-22T00:00:00+00:00', $restoredPageEn->getCreated()->format('c'));
        static::assertSame(404, $restoredPageEn->getAuthor());
        static::assertSame('2000-04-22T00:00:00+00:00', $restoredPageEn->getAuthored()->format('c'));
        static::assertSame('article', $restoredPageEn->getStructureType());
        static::assertSame('english article content', $restoredPageEn->getStructure()->toArray()['article']);
        static::assertSame('excerpt title en', $restoredPageEn->getExtensionsData()['excerpt']['title']);
        static::assertSame('seo title en', $restoredPageEn->getExtensionsData()['seo']['title']);
        static::assertSame(
            [
                $role->getId() => [
                    'view' => true,
                    'add' => false,
                    'edit' => true,
                    'delete' => false,
                    'archive' => false,
                    'live' => false,
                    'security' => false,
                ],
            ],
            $restoredPageEn->getPermissions()
        );
        static::assertSame(['other'], $restoredPageEn->getNavigationContexts());

        $activity = $this->activityRepository->findOneBy(['type' => 'translation_restored']);
        $this->assertNotNull($activity);
        $this->assertSame($originalPageUuid, $activity->getResourceId());
    }

    private function createRole(string $name = 'Role', string $system = 'Website'): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setSystem($system);

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return $role;
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Application\Message\CopyPageMessage;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Application\Message\MovePageMessage;
use Sulu\Page\Application\MessageHandler\CopyPageMessageHandler;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Application\MessageHandler\ModifyPageMessageHandler;
use Sulu\Page\Application\MessageHandler\MovePageMessageHandler;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

#[CoversNothing]
class PageMoveCopyRoutingTest extends SuluTestCase
{
    private PageRepositoryInterface $pageRepository;

    private RouteRepositoryInterface $routeRepository;

    protected function setUp(): void
    {
        self::purgeDatabase();

        self::assertInstanceOf(KernelInterface::class, self::$kernel);
        $application = new Application(self::$kernel);
        $command = $application->find('sulu:page:initialize');
        (new CommandTester($command))->execute([]);

        $this->pageRepository = $this->getContainer()->get('sulu_page.page_repository');
        $this->routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
    }

    public function testMoveUpdatesUrlToNewParentPath(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->move($child->getUuid(), $parentB->getUuid());

        $this->assertRouteSlug('/parent-b/child', $child->getUuid(), 'en');
    }

    public function testMoveToRootUsesHomepageSlug(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->move($child->getUuid(), $homepage->getUuid());

        $this->assertRouteSlug('/child', $child->getUuid(), 'en');
    }

    public function testMoveCascadesDescendantSlugs(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);
        $grandchild = $this->createPage($child->getUuid(), ['title' => 'Grandchild', 'url' => '/parent-a/child/grandchild']);

        $this->move($child->getUuid(), $parentB->getUuid());

        $this->assertRouteSlug('/parent-b/child', $child->getUuid(), 'en');
        $this->assertRouteSlug('/parent-b/child/grandchild', $grandchild->getUuid(), 'en');
    }

    public function testMoveAppendsSuffixWhenDestinationSlugIsTaken(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $this->createPage($parentB->getUuid(), ['title' => 'Existing', 'url' => '/parent-b/child']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->move($child->getUuid(), $parentB->getUuid());

        $this->assertRouteSlug('/parent-b/child-1', $child->getUuid(), 'en');
    }

    public function testMoveCreatesHistoryRoute(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->move($child->getUuid(), $parentB->getUuid());

        $historyRoute = $this->routeRepository->findOneBy([
            'webspace' => 'sulu-io',
            'locale' => 'en',
            'slug' => '/parent-a/child',
        ]);

        self::assertNotNull($historyRoute, 'A history route should be created at the old slug.');
        self::assertTrue($historyRoute->isHistory());
    }

    public function testMoveDoesNothingWhenParentIsUnchanged(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->move($child->getUuid(), $parentA->getUuid());

        $this->assertRouteSlug('/parent-a/child', $child->getUuid(), 'en');
        self::assertNull($this->routeRepository->findOneBy([
            'webspace' => 'sulu-io',
            'locale' => 'en',
            'slug' => '/parent-a/child',
            'resourceKey' => Route::HISTORY_RESOURCE_KEY,
        ]));
    }

    public function testMoveReanchorsRouteSoLaterParentRenameCascades(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->move($child->getUuid(), $parentB->getUuid());
        $this->assertRouteSlug('/parent-b/child', $child->getUuid(), 'en');

        // Renaming the new parent must cascade to the moved child, which only works if the moved
        // route's slug now sits under parent B's slug (the cascade matches by slug prefix).
        $this->renameRoute($parentB->getUuid(), 'en', '/parent-b-renamed');

        $this->assertRouteSlug('/parent-b-renamed/child', $child->getUuid(), 'en');
    }

    public function testCopyCreatesRouteSoLaterParentRenameCascades(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $source = $this->createPage($parentA->getUuid(), ['title' => 'Source', 'url' => '/parent-a/source']);

        $target = $this->copy($source->getUuid(), $parentB->getUuid());
        $this->assertRouteSlug('/parent-b/source', $target->getUuid(), 'en');

        // Renaming the parent must cascade to the copied route, which only works if the copied
        // route's slug sits under parent B's slug (the cascade matches by slug prefix).
        $this->renameRoute($parentB->getUuid(), 'en', '/parent-b-renamed');

        $this->assertRouteSlug('/parent-b-renamed/source', $target->getUuid(), 'en');
    }

    public function testCopyCreatesRouteWithUniqueSlug(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $source = $this->createPage($parentA->getUuid(), ['title' => 'Source', 'url' => '/parent-a/source']);

        $target = $this->copy($source->getUuid(), $parentB->getUuid());

        $this->assertRouteSlug('/parent-b/source', $target->getUuid(), 'en');
    }

    public function testCopyToSameParentAppendsSuffix(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $source = $this->createPage($parentA->getUuid(), ['title' => 'Source', 'url' => '/parent-a/source']);

        $target = $this->copy($source->getUuid(), $parentA->getUuid());

        $this->assertRouteSlug('/parent-a/source-1', $target->getUuid(), 'en');
    }

    public function testCopyIncrementsSuffixWhenFirstCandidateIsTaken(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $this->createPage($parentA->getUuid(), ['title' => 'Existing', 'url' => '/parent-a/source-1']);
        $source = $this->createPage($parentA->getUuid(), ['title' => 'Source', 'url' => '/parent-a/source']);

        $target = $this->copy($source->getUuid(), $parentA->getUuid());

        $this->assertRouteSlug('/parent-a/source-2', $target->getUuid(), 'en');
    }

    public function testCopyUnderHomepageCollapsesParentSlash(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $source = $this->createPage($parentA->getUuid(), ['title' => 'Source', 'url' => '/parent-a/source']);

        $target = $this->copy($source->getUuid(), $homepage->getUuid());

        $this->assertRouteSlug('/source', $target->getUuid(), 'en');
    }

    public function testMoveKeepsSlugWhenChildSlugSharesParentPrefixWithoutBoundary(): void
    {
        // The child's slug ("/sportswear") shares a string prefix with the parent ("/sport") but
        // is not actually nested under it. Re-anchoring must not strip a partial segment.
        $homepage = $this->getHomepage();
        $sport = $this->createPage($homepage->getUuid(), ['title' => 'Sport', 'url' => '/sport']);
        $leisure = $this->createPage($homepage->getUuid(), ['title' => 'Leisure', 'url' => '/leisure']);
        $child = $this->createPage($sport->getUuid(), ['title' => 'Sportswear', 'url' => '/sportswear']);

        $this->move($child->getUuid(), $leisure->getUuid());

        $this->assertRouteSlug('/leisure/sportswear', $child->getUuid(), 'en');
    }

    public function testCopyKeepsSlugWhenSourceSlugSharesParentPrefixWithoutBoundary(): void
    {
        $homepage = $this->getHomepage();
        $sport = $this->createPage($homepage->getUuid(), ['title' => 'Sport', 'url' => '/sport']);
        $leisure = $this->createPage($homepage->getUuid(), ['title' => 'Leisure', 'url' => '/leisure']);
        $source = $this->createPage($sport->getUuid(), ['title' => 'Sportswear', 'url' => '/sportswear']);

        $target = $this->copy($source->getUuid(), $leisure->getUuid());

        $this->assertRouteSlug('/leisure/sportswear', $target->getUuid(), 'en');
    }

    public function testMoveDoesNotReanchorRouteAnchoredToExternalParentRoute(): void
    {
        // A route with a parentRoute (e.g. from a page_tree_route field) is anchored to an external
        // page, not to the page tree, so moving the page in the tree must leave its slug untouched.
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $anchor = $this->createPage($homepage->getUuid(), ['title' => 'Anchor', 'url' => '/anchor']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->anchorRouteToParent($child->getUuid(), $anchor->getUuid(), 'en');

        $this->move($child->getUuid(), $parentB->getUuid());

        $this->assertRouteSlug('/parent-a/child', $child->getUuid(), 'en');
    }

    public function testMoveThenOldParentRenameMustNotDragMovedChildInUntranslatedLocale(): void
    {
        // Multi-locale move where the destination (parent B) is not translated in "de". createPage()
        // creates only the "en" locale, so "de" is added to parent A and the child via translatePage().
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->translatePage($parentA->getUuid(), ['title' => 'Eltern A', 'url' => '/eltern-a']);
        $this->translatePage($child->getUuid(), ['title' => 'Kind', 'url' => '/eltern-a/kind']);

        $this->move($child->getUuid(), $parentB->getUuid());

        // The "en" route is correctly re-anchored under the new parent.
        $this->assertRouteSlug('/parent-b/child', $child->getUuid(), 'en');

        // The child has moved out of parent A, so a later rename of parent A in "de" must not drag
        // the moved-away child's "de" route along.
        $this->renameRoute($parentA->getUuid(), 'de', '/eltern-a-neu');

        $childDeRoute = $this->routeRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $child->getUuid(),
            'locale' => 'de',
        ]);
        self::assertNotNull($childDeRoute, 'Expected a "de" route for the moved child.');
        self::assertStringStartsNotWith(
            '/eltern-a-neu/',
            $childDeRoute->getSlug(),
            'A page moved out of parent A must not be re-parented under it when parent A is later renamed.',
        );
    }

    public function testMoveReanchorsRouteRootRelativeWhenNewParentUntranslatedInLocale(): void
    {
        // Parent B is not translated in "de", so the moved child's "de" URL cannot sit under it. The
        // route must be re-generated root-relative ("/kind") instead of being left stale under parent A.
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->translatePage($parentA->getUuid(), ['title' => 'Eltern A', 'url' => '/eltern-a']);
        $this->translatePage($child->getUuid(), ['title' => 'Kind', 'url' => '/eltern-a/kind']);

        $this->move($child->getUuid(), $parentB->getUuid());

        $this->assertRouteSlug('/parent-b/child', $child->getUuid(), 'en');
        $this->assertRouteSlug('/kind', $child->getUuid(), 'de');
    }

    public function testMoveReanchorsRouteRootRelativeWhenPreviousParentUntranslatedInLocale(): void
    {
        // Edge: the child was given a "de" URL nested under parent A even though parent A itself is not
        // translated in "de" (a manually typed URL). On move, the slug cannot be resolved relative to
        // the untranslated previous parent, so it falls back to the child's own segment ("/child").
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->translatePage($child->getUuid(), ['title' => 'Kind', 'url' => '/parent-a/child']);

        $this->move($child->getUuid(), $parentB->getUuid());

        $this->assertRouteSlug('/child', $child->getUuid(), 'de');
    }

    public function testMoveAnchorPageCascadesToAnchoredPageTreeRouteArticle(): void
    {
        // An article using a page_tree_route field is stored as a webspace-NULL route whose slug sits
        // under its anchor page and whose parent_id points at the anchor page's route. When the anchor
        // page is moved (its own slug changes), the article URL must follow via the slug-prefix cascade.
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $anchor = $this->createPage($parentA->getUuid(), ['title' => 'Anchor', 'url' => '/parent-a/anchor']);

        $this->createArticleRoute('article-1', $anchor->getUuid(), 'en', '/parent-a/anchor/my-article');

        $this->move($anchor->getUuid(), $parentB->getUuid());

        $this->assertRouteSlug('/parent-b/anchor', $anchor->getUuid(), 'en');
        $this->assertArticleRouteSlug('/parent-b/anchor/my-article', 'article-1', 'en');
    }

    public function testMoveUpdatesRoutesInAllTranslatedLocales(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $child = $this->createPage($parentA->getUuid(), ['title' => 'Child', 'url' => '/parent-a/child']);

        $this->translatePage($parentA->getUuid(), ['title' => 'Eltern A', 'url' => '/eltern-a']);
        $this->translatePage($parentB->getUuid(), ['title' => 'Eltern B', 'url' => '/eltern-b']);
        $this->translatePage($child->getUuid(), ['title' => 'Kind', 'url' => '/eltern-a/kind']);

        $this->move($child->getUuid(), $parentB->getUuid());

        $this->assertRouteSlug('/parent-b/child', $child->getUuid(), 'en');
        $this->assertRouteSlug('/eltern-b/kind', $child->getUuid(), 'de');
    }

    public function testCopyCreatesRoutesInAllTranslatedLocales(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $parentB = $this->createPage($homepage->getUuid(), ['title' => 'Parent B', 'url' => '/parent-b']);
        $source = $this->createPage($parentA->getUuid(), ['title' => 'Source', 'url' => '/parent-a/source']);

        $this->translatePage($parentA->getUuid(), ['title' => 'Eltern A', 'url' => '/eltern-a']);
        $this->translatePage($parentB->getUuid(), ['title' => 'Eltern B', 'url' => '/eltern-b']);
        $this->translatePage($source->getUuid(), ['title' => 'Quelle', 'url' => '/eltern-a/quelle']);

        $target = $this->copy($source->getUuid(), $parentB->getUuid());

        $this->assertRouteSlug('/parent-b/source', $target->getUuid(), 'en');
        $this->assertRouteSlug('/eltern-b/quelle', $target->getUuid(), 'de');
    }

    public function testCopyCreatesParentlessRouteWithoutHistory(): void
    {
        $homepage = $this->getHomepage();
        $parentA = $this->createPage($homepage->getUuid(), ['title' => 'Parent A', 'url' => '/parent-a']);
        $source = $this->createPage($parentA->getUuid(), ['title' => 'Source', 'url' => '/parent-a/source']);

        $target = $this->copy($source->getUuid(), $parentA->getUuid());

        $targetRoute = $this->routeRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $target->getUuid(),
            'locale' => 'en',
        ]);
        self::assertNotNull($targetRoute, 'Expected a route for the copied page.');
        self::assertNull(
            $targetRoute->getParentRoute(),
            'A copied page route must stay anchored to the tree (parent_id null), like a normal page route.',
        );

        // Copy is a fresh INSERT (not a slug change), so it must not create a history route.
        $historyRoute = $this->routeRepository->findOneBy([
            'resourceKey' => Route::HISTORY_RESOURCE_KEY,
            'locale' => 'en',
            'slug' => $targetRoute->getSlug(),
        ]);
        self::assertNull($historyRoute, 'Copying a page must not create a history route for the new slug.');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createPage(string $parentId, array $data): PageInterface
    {
        $data = \array_merge(['locale' => 'en', 'template' => 'default'], $data);

        /** @var CreatePageMessageHandler $handler */
        $handler = self::getContainer()->get('sulu_page.create_page_handler');
        $page = $handler(new CreatePageMessage('sulu-io', $parentId, $data));
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        return $this->pageRepository->getOneBy(['uuid' => $page->getUuid()]);
    }

    /**
     * Adds a translation (and its route) in an additional locale to an existing page.
     *
     * @param array<string, mixed> $data
     */
    private function translatePage(string $uuid, array $data): void
    {
        $data = \array_merge(['locale' => 'de', 'template' => 'default'], $data);

        /** @var ModifyPageMessageHandler $handler */
        $handler = self::getContainer()->get('sulu_page.modify_page_handler');
        $handler(new ModifyPageMessage(['uuid' => $uuid], $data));
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();
    }

    private function move(string $uuid, string $destinationParentUuid): void
    {
        /** @var MovePageMessageHandler $handler */
        $handler = self::getContainer()->get('sulu_page.move_page_handler');
        $handler(new MovePageMessage(['uuid' => $uuid], ['uuid' => $destinationParentUuid], 'en'));
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();
    }

    private function copy(string $sourceUuid, string $destinationParentUuid): PageInterface
    {
        /** @var CopyPageMessageHandler $handler */
        $handler = self::getContainer()->get('sulu_page.copy_page_handler');
        $target = $handler(new CopyPageMessage(['uuid' => $sourceUuid], ['uuid' => $destinationParentUuid], 'en'));
        $targetUuid = $target->getUuid();
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        return $this->pageRepository->getOneBy(['uuid' => $targetUuid]);
    }

    private function renameRoute(string $resourceId, string $locale, string $newSlug): void
    {
        $route = $this->routeRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $resourceId,
            'locale' => $locale,
        ]);
        self::assertNotNull($route, \sprintf('Expected a route for page %s in locale %s.', $resourceId, $locale));

        $route->setSlug($newSlug);
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();
    }

    private function anchorRouteToParent(string $resourceId, string $parentResourceId, string $locale): void
    {
        $route = $this->routeRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $resourceId,
            'locale' => $locale,
        ]);
        self::assertNotNull($route, \sprintf('Expected a route for page %s in locale %s.', $resourceId, $locale));

        $parentRoute = $this->routeRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $parentResourceId,
            'locale' => $locale,
        ]);
        self::assertNotNull($parentRoute, \sprintf('Expected a route for page %s in locale %s.', $parentResourceId, $locale));

        $route->setParentRoute($parentRoute);
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();
    }

    private function getHomepage(): PageInterface
    {
        $homepage = $this->pageRepository->findOneBy([
            'webspaceKey' => 'sulu-io',
            'parentId' => null,
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ]);
        self::assertNotNull($homepage, 'Homepage should be initialized in setUp.');

        return $homepage;
    }

    private function assertRouteSlug(string $expectedSlug, string $resourceId, string $locale): void
    {
        $route = $this->routeRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $resourceId,
            'locale' => $locale,
        ]);

        self::assertNotNull($route, \sprintf('Expected a route for page %s in locale %s.', $resourceId, $locale));
        self::assertSame($expectedSlug, $route->getSlug());
    }

    /**
     * Creates a route the way a page_tree_route article field does: webspace NULL, anchored to the
     * given page's route via parent_id.
     */
    private function createArticleRoute(string $resourceId, string $anchorResourceId, string $locale, string $slug): void
    {
        $anchorRoute = $this->routeRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $anchorResourceId,
            'locale' => $locale,
        ]);
        self::assertNotNull($anchorRoute, \sprintf('Expected a route for anchor page %s in locale %s.', $anchorResourceId, $locale));

        $route = new Route('articles', $resourceId, $locale, $slug, null, $anchorRoute);
        $this->routeRepository->add($route);
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();
    }

    private function assertArticleRouteSlug(string $expectedSlug, string $resourceId, string $locale): void
    {
        $route = $this->routeRepository->findOneBy([
            'resourceKey' => 'articles',
            'resourceId' => $resourceId,
            'locale' => $locale,
        ]);

        self::assertNotNull($route, \sprintf('Expected an article route %s in locale %s.', $resourceId, $locale));
        self::assertSame($expectedSlug, $route->getSlug());
    }
}

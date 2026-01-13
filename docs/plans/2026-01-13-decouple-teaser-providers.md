# Decouple PageTeaserProvider Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Decouple PageTeaserProvider from the abstract ContentTeaserProvider and its traits, creating a standalone implementation optimized for Page entity with proper webspace handling and URL generation.

**Architecture:** PageTeaserProvider will be a fully self-contained class implementing `TeaserProviderInterface` directly. It will use `PageRepositoryInterface` for optimized entity loading (with proper selects), `ContentAggregatorInterface` for dimension content resolution, and direct excerpt field access (no hardcoded fallbacks). The abstract class and traits will be deprecated but not removed.

**Tech Stack:** PHP 8.2+, Doctrine ORM, Symfony DI, Sulu Content/Route bundles

---

## Background Context

### Current Architecture
- `ContentTeaserProvider` (abstract) in `packages/content/src/Infrastructure/Sulu/Teaser/`
- Uses 3 traits: `FindContentRichEntitiesTrait`, `ResolveContentTrait`, `ResolveContentDimensionUrlTrait`
- `PageTeaserProvider` extends the abstract class

### Key Points
- **Page**: `webspaceKey` on `PageInterface` entity level
- Uses `ContentEnhancerInterface` for page links
- Uses `uuid` as ID field
- Service config is in `SuluPageBundle.php`

### Interface Contract
```php
interface TeaserProviderInterface {
    public function getConfiguration(): TeaserConfiguration;
    public function find(array $ids, $locale): array; // Returns Teaser[]
}
```

### Underlying Services (instead of ContentManager facade)
- `ContentAggregatorInterface::aggregate()` - resolves dimension content from entity
- `ContentNormalizerInterface::normalize()` - normalizes dimension content (not needed if we use direct excerpt access)

---

## Task 1: Add Deprecation to Abstract ContentTeaserProvider

**Files:**
- Modify: `packages/content/src/Infrastructure/Sulu/Teaser/ContentTeaserProvider.php:30-34`

**Step 1: Add deprecation annotation to abstract class**

Add `@deprecated` annotation to the class docblock:

```php
/**
 * @template B of DimensionContentInterface
 * @template T of ContentRichEntityInterface<B>
 *
 * @deprecated since 3.0, use entity-specific teaser providers instead (ArticleTeaserProvider, PageTeaserProvider)
 */
abstract class ContentTeaserProvider implements TeaserProviderInterface
```

**Step 2: Continue to Task 2 (commit later)**

---

## Task 2: Add Deprecation to Traits

**Files:**
- Modify: `packages/content/src/Infrastructure/Sulu/Traits/FindContentRichEntitiesTrait.php:22-27`
- Modify: `packages/content/src/Infrastructure/Sulu/Traits/ResolveContentTrait.php:19-24`
- Modify: `packages/content/src/Infrastructure/Sulu/Traits/ResolveContentDimensionUrlTrait.php:22-27`

**Step 1: Add deprecation to FindContentRichEntitiesTrait**

```php
/**
 * @template D of ContentRichEntityInterface
 *
 * @internal
 *
 * @deprecated since 3.0, implement entity loading directly in your teaser provider
 */
trait FindContentRichEntitiesTrait
```

**Step 2: Add deprecation to ResolveContentTrait**

```php
/**
 * @internal
 *
 * @deprecated since 3.0, implement content resolution directly in your teaser provider
 */
trait ResolveContentTrait
```

**Step 3: Add deprecation to ResolveContentDimensionUrlTrait**

```php
/**
 * @internal
 *
 * @deprecated since 3.0, implement URL resolution directly in your teaser provider
 */
trait ResolveContentDimensionUrlTrait
```

**Step 4: Commit all deprecations**

```bash
git add packages/content/src/Infrastructure/Sulu/Teaser/ContentTeaserProvider.php
git add packages/content/src/Infrastructure/Sulu/Traits/
git commit -m "Deprecate ContentTeaserProvider and related traits"
```

---

## Task 3: Write Functional Test for Standalone PageTeaserProvider

**Files:**
- Modify: `packages/page/tests/Functional/Infrastructure/Sulu/Content/PageTeaserProviderTest.php`

**Step 1: Rewrite functional test using real implementations**

```php
<?php

declare(strict_types=1);

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Content;

use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Infrastructure\Sulu\Content\PageTeaserProvider;
use Sulu\Page\Tests\Traits\CreatePageTrait;

#[CoversClass(PageTeaserProvider::class)]
class PageTeaserProviderTest extends SuluTestCase
{
    use CreatePageTrait;

    private PageTeaserProvider $teaserProvider;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->teaserProvider = self::getContainer()->get('sulu_page.page_teaser_provider');
    }

    public function testGetConfiguration(): void
    {
        $configuration = $this->teaserProvider->getConfiguration();

        $this->assertSame('pages', $configuration->getResourceKey());
        $this->assertSame('column_list', $configuration->getView());
        $this->assertSame(['title'], $configuration->getResultToView());
    }

    public function testFindReturnsEmptyArrayForEmptyIds(): void
    {
        $result = $this->teaserProvider->find([], 'en');

        $this->assertSame([], $result);
    }

    public function testFindReturnsTeasersWithExcerptData(): void
    {
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Test Page Title',
                    'url' => '/test-page',
                    'template' => 'default',
                    'excerpt' => [
                        'title' => 'Excerpt Title',
                        'description' => 'Excerpt description text',
                        'more' => 'Read more',
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $teaser = $teasers[0];

        $this->assertSame($page->getUuid(), $teaser->getId());
        $this->assertSame('pages', $teaser->getType());
        $this->assertSame('en', $teaser->getLocale());
        $this->assertSame('Excerpt Title', $teaser->getTitle());
        $this->assertSame('Excerpt description text', $teaser->getDescription());
        $this->assertSame('Read more', $teaser->getMoreText());
        $this->assertSame('/test-page', $teaser->getUrl());
    }

    public function testFindReturnsNullTitleWhenNoExcerptTitle(): void
    {
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Page Without Excerpt Title',
                    'url' => '/no-excerpt-title',
                    'template' => 'default',
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertNull($teasers[0]->getTitle());
    }

    public function testFindSkipsUnpublishedPages(): void
    {
        $page = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Draft Only Page',
                    'url' => '/draft-page',
                    'template' => 'default',
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(0, $teasers);
    }

    public function testFindSkipsPagesWithoutMatchingLocale(): void
    {
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'English Only Page',
                    'url' => '/english-only',
                    'template' => 'default',
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'de');

        $this->assertCount(0, $teasers);
    }

    public function testFindPreservesOrder(): void
    {
        $page1 = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'First Page',
                    'url' => '/first',
                    'template' => 'default',
                    'excerpt' => ['title' => 'First'],
                ],
            ],
        ]);

        $page2 = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Second Page',
                    'url' => '/second',
                    'template' => 'default',
                    'excerpt' => ['title' => 'Second'],
                ],
            ],
        ]);

        $page3 = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Third Page',
                    'url' => '/third',
                    'template' => 'default',
                    'excerpt' => ['title' => 'Third'],
                ],
            ],
        ]);

        // Request in reverse order
        $teasers = $this->teaserProvider->find([
            $page3->getUuid(),
            $page1->getUuid(),
            $page2->getUuid(),
        ], 'en');

        $this->assertCount(3, $teasers);
        $this->assertSame($page3->getUuid(), $teasers[0]->getId());
        $this->assertSame($page1->getUuid(), $teasers[1]->getId());
        $this->assertSame($page2->getUuid(), $teasers[2]->getId());
    }

    public function testFindWithExternalLink(): void
    {
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'External Link Page',
                    'url' => '/external-link',
                    'template' => 'default',
                    'linkOn' => true,
                    'linkData' => [
                        'href' => 'https://example.com',
                        'provider' => 'external',
                    ],
                    'excerpt' => [
                        'title' => 'External Link Title',
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame('https://example.com', $teasers[0]->getUrl());
    }

    public function testFindWithMultipleLocales(): void
    {
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'English Title',
                    'url' => '/english',
                    'template' => 'default',
                    'excerpt' => ['title' => 'EN Excerpt'],
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'German Title',
                    'url' => '/deutsch',
                    'template' => 'default',
                    'excerpt' => ['title' => 'DE Excerpt'],
                ],
            ],
        ]);

        $englishTeasers = $this->teaserProvider->find([$page->getUuid()], 'en');
        $germanTeasers = $this->teaserProvider->find([$page->getUuid()], 'de');

        $this->assertCount(1, $englishTeasers);
        $this->assertCount(1, $germanTeasers);
        $this->assertSame('EN Excerpt', $englishTeasers[0]->getTitle());
        $this->assertSame('DE Excerpt', $germanTeasers[0]->getTitle());
        $this->assertSame('/english', $englishTeasers[0]->getUrl());
        $this->assertSame('/deutsch', $germanTeasers[0]->getUrl());
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/phpunit packages/page/tests/Functional/Infrastructure/Sulu/Content/PageTeaserProviderTest.php -v`
Expected: Some tests should fail because current implementation uses ContentManager and has different title logic

**Step 3: Continue to Task 4 (commit later with full implementation)**

---

## Task 4: Implement Standalone PageTeaserProvider

**Files:**
- Modify: `packages/page/src/Infrastructure/Sulu/Content/PageTeaserProvider.php`

**Step 1: Rewrite PageTeaserProvider as standalone implementation**

Replace the entire file content:

```php
<?php

declare(strict_types=1);

namespace Sulu\Page\Infrastructure\Sulu\Content;

use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\AdminBundle\Teaser\Teaser;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentEnhancer\ContentEnhancerInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\LinkInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageTeaserProvider implements TeaserProviderInterface
{
    public function __construct(
        private readonly PageRepositoryInterface $pageRepository,
        private readonly ContentAggregatorInterface $contentAggregator,
        private readonly ContentEnhancerInterface $contentEnhancer,
        private readonly RouteGeneratorInterface $routeGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getConfiguration(): TeaserConfiguration
    {
        return new TeaserConfiguration(
            $this->translator->trans('sulu_page.page', [], 'admin'),
            PageInterface::RESOURCE_KEY,
            'column_list',
            ['title'],
            $this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin'),
        );
    }

    /**
     * @param array<string> $ids
     *
     * @return Teaser[]
     */
    public function find(array $ids, $locale): array
    {
        if (0 === \count($ids)) {
            return [];
        }

        $pages = $this->findPagesByUuids($ids, $locale);

        $teasers = [];
        foreach ($pages as $page) {
            $teaser = $this->createTeaserFromPage($page, $locale);
            if (null !== $teaser) {
                $teasers[] = $teaser;
            }
        }

        return $teasers;
    }

    /**
     * @param array<string> $uuids
     *
     * @return array<PageInterface>
     */
    private function findPagesByUuids(array $uuids, string $locale): array
    {
        /** @var array<PageInterface> $pages */
        $pages = \iterator_to_array($this->pageRepository->findBy(
            [
                'uuids' => $uuids,
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            [],
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'dimensionAttributes' => [
                        'locale' => $locale,
                        'stage' => DimensionContentInterface::STAGE_LIVE,
                    ],
                ],
            ]
        ));

        // Sort by original order
        $uuidPositions = \array_flip($uuids);
        \usort(
            $pages,
            static fn (PageInterface $a, PageInterface $b) => ($uuidPositions[$a->getUuid()] ?? 0) - ($uuidPositions[$b->getUuid()] ?? 0)
        );

        return $pages;
    }

    private function createTeaserFromPage(PageInterface $page, string $locale): ?Teaser
    {
        $dimensionContent = $this->resolveDimensionContent($page, $locale);
        if (null === $dimensionContent) {
            return null;
        }

        // Enhance content for page links
        $dimensionContent = $this->contentEnhancer->enhance($dimensionContent);

        $url = $this->resolveUrl($dimensionContent, $page);
        if (null === $url) {
            return null;
        }

        return new Teaser(
            $page->getUuid(),
            PageInterface::RESOURCE_KEY,
            $locale,
            $this->resolveTitle($dimensionContent),
            $this->resolveDescription($dimensionContent),
            $this->resolveMoreText($dimensionContent),
            $url,
            $this->resolveMediaId($dimensionContent),
            []
        );
    }

    private function resolveDimensionContent(PageInterface $page, string $locale): ?PageDimensionContentInterface
    {
        try {
            /** @var PageDimensionContentInterface $dimensionContent */
            $dimensionContent = $this->contentAggregator->aggregate($page, [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ]);
        } catch (ContentNotFoundException) {
            return null;
        }

        if (DimensionContentInterface::STAGE_LIVE !== $dimensionContent->getStage()
            || $locale !== $dimensionContent->getLocale()
        ) {
            return null;
        }

        return $dimensionContent;
    }

    private function resolveUrl(PageDimensionContentInterface $dimensionContent, PageInterface $page): ?string
    {
        // Check for external link first
        if ($dimensionContent instanceof LinkInterface && $dimensionContent->getLinkOn()) {
            $linkData = $dimensionContent->getLinkData();
            if (isset($linkData['href']) && \is_string($linkData['href'])) {
                return $linkData['href'];
            }
        }

        // Generate URL from route
        if ($dimensionContent instanceof RoutableInterface) {
            $route = $dimensionContent->getRoute();
            if (null !== $route) {
                return $this->routeGenerator->generate(
                    $route->getSlug(),
                    $route->getLocale(),
                    $page->getWebspaceKey(),
                );
            }
        }

        return null;
    }

    private function resolveTitle(PageDimensionContentInterface $dimensionContent): ?string
    {
        if ($dimensionContent instanceof ExcerptInterface) {
            $excerptTitle = $dimensionContent->getExcerptTitle();
            if (null !== $excerptTitle && '' !== $excerptTitle) {
                return $excerptTitle;
            }
        }

        return null;
    }

    private function resolveDescription(PageDimensionContentInterface $dimensionContent): ?string
    {
        if ($dimensionContent instanceof ExcerptInterface) {
            $excerptDescription = $dimensionContent->getExcerptDescription();
            if (null !== $excerptDescription && '' !== $excerptDescription) {
                return \strip_tags($excerptDescription);
            }
        }

        return null;
    }

    private function resolveMoreText(PageDimensionContentInterface $dimensionContent): ?string
    {
        if ($dimensionContent instanceof ExcerptInterface) {
            $excerptMore = $dimensionContent->getExcerptMore();
            if (null !== $excerptMore && '' !== $excerptMore) {
                return $excerptMore;
            }
        }

        return null;
    }

    private function resolveMediaId(PageDimensionContentInterface $dimensionContent): ?int
    {
        if ($dimensionContent instanceof ExcerptInterface) {
            $excerptImage = $dimensionContent->getExcerptImage();
            if (\is_array($excerptImage) && isset($excerptImage['id'])) {
                return $excerptImage['id'];
            }
        }

        return null;
    }
}
```

**Step 2: Run functional tests to verify implementation**

Run: `./vendor/bin/phpunit packages/page/tests/Functional/Infrastructure/Sulu/Content/PageTeaserProviderTest.php -v`
Expected: Will fail until service definition is updated

**Step 3: Continue to Task 5 (commit later with full implementation)**

---

## Task 5: Update PageTeaserProvider Service Definition in SuluPageBundle

**Files:**
- Modify: `packages/page/src/Infrastructure/Symfony/HttpKernel/SuluPageBundle.php:418-429`

**Step 1: Update service definition**

Find the current service definition (around line 418):

```php
// Content services
$services->set('sulu_page.page_teaser_provider')
    ->class(PageTeaserProvider::class)
    ->args([
        new Reference('sulu_content.content_manager'), // TODO teaser provider should not build on manager
        new Reference('doctrine.orm.entity_manager'),
        new Reference('sulu_content.content_metadata_inspector'),
        new Reference('sulu_admin.metadata_provider_registry'),
        new Reference('translator'),
        new Reference('sulu_content.content_enhancer'),
        new Reference('sulu_route.route_generator'),
    ])
    ->tag('sulu.teaser.provider', ['alias' => PageInterface::RESOURCE_KEY]);
```

Replace with:

```php
// Content services
$services->set('sulu_page.page_teaser_provider')
    ->class(PageTeaserProvider::class)
    ->args([
        new Reference('sulu_page.page_repository'),
        new Reference('sulu_content.content_aggregator'),
        new Reference('sulu_content.content_enhancer'),
        new Reference('sulu_route.route_generator'),
        new Reference('translator'),
    ])
    ->tag('sulu.teaser.provider', ['alias' => PageInterface::RESOURCE_KEY]);
```

**Step 2: Remove unused imports if any**

Check if these imports are still needed, remove if not used elsewhere:
- `MetadataProviderRegistry`
- `ContentMetadataInspectorInterface`

**Step 3: Verify service compiles**

Run: `bin/console cache:clear --env=test`
Expected: No errors

**Step 4: Run all PageTeaserProvider tests**

Run: `./vendor/bin/phpunit packages/page/tests/Functional/Infrastructure/Sulu/Content/PageTeaserProviderTest.php -v`
Expected: PASS

**Step 5: Commit full PageTeaserProvider implementation**

```bash
git add packages/page/tests/Functional/Infrastructure/Sulu/Content/PageTeaserProviderTest.php
git add packages/page/src/Infrastructure/Sulu/Content/PageTeaserProvider.php
git add packages/page/src/Infrastructure/Symfony/HttpKernel/SuluPageBundle.php
git commit -m "Decouple PageTeaserProvider from abstract class and traits"
```

---

## Task 6: Run Full Test Suite and Static Analysis

**Files:**
- No file changes

**Step 1: Run PageTeaserProvider functional tests**

Run: `./vendor/bin/phpunit packages/page/tests/Functional/Infrastructure/Sulu/Content/PageTeaserProviderTest.php -v`
Expected: PASS

**Step 2: Run ContentTeaserProvider tests (ensure abstract still works for other consumers)**

Run: `./vendor/bin/phpunit packages/content/tests/Functional/Infrastructure/Sulu/Teaser/ContentTeaserProviderTest.php -v`
Expected: PASS

**Step 3: Run all teaser-related tests**

Run: `./vendor/bin/phpunit --filter Teaser -v`
Expected: All PASS

**Step 4: Run static analysis**

Run: `composer phpstan`
Expected: No new errors

---

## Summary of Changes

### Deprecated (not removed):
- `packages/content/src/Infrastructure/Sulu/Teaser/ContentTeaserProvider.php`
- `packages/content/src/Infrastructure/Sulu/Traits/FindContentRichEntitiesTrait.php`
- `packages/content/src/Infrastructure/Sulu/Traits/ResolveContentTrait.php`
- `packages/content/src/Infrastructure/Sulu/Traits/ResolveContentDimensionUrlTrait.php`

### Refactored:
- `packages/page/src/Infrastructure/Sulu/Content/PageTeaserProvider.php` - Standalone implementation

### Key Architectural Decisions:
1. **Uses PageRepository**: Optimized entity loading with proper selects for dimension content
2. **Uses ContentAggregatorInterface**: Direct service instead of ContentManager facade
3. **Page webspace handling**: Uses `$page->getWebspaceKey()` from entity level
4. **URL resolution**: Checks for external links first, then generates from route
5. **Title/Description**: Only uses excerpt values (no hardcoded fallbacks like 'article' or 'description')
6. **Content enhancement**: Uses ContentEnhancer for page links
7. **No shared code**: Fully self-contained, no inheritance or traits

### Commit Strategy:
1. **First commit**: All deprecations (ContentTeaserProvider + 3 traits)
2. **Second commit**: Full PageTeaserProvider implementation (tests + code + service definition)
3. **Third commit** (later): ArticleTeaserProvider implementation

### Next Steps (for ArticleTeaserProvider):
After PageTeaserProvider is working, continue with ArticleTeaserProvider which has different requirements:
- Webspace on dimension content level (not entity)
- Multi-webspace support via `additionalWebspaces`
- Multi-webspace tests required
- No content enhancement needed

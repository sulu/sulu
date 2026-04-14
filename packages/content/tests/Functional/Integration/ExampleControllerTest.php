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

namespace Sulu\Content\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\AppCache;
use Sulu\Content\Tests\Application\CacheTagsKernel;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;
use Sulu\Content\Tests\Functional\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Sulu\Content\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Content\Tests\Traits\CreateExampleTrait;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class ExampleControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;
    use CreateCategoryTrait;
    use CreateExampleTrait;
    use CreateMediaTrait;
    use CreateTagTrait;

    /**
     * @var KernelBrowser
     */
    protected $client;

    /**
     * @var ReferenceRepositoryInterface
     */
    private $referenceRepository;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        $this->referenceRepository = $this->getContainer()->get(ReferenceRepositoryInterface::class);
    }

    /**
     * Create a website client with cache tags enabled for testing cache tag functionality.
     */
    private function createCacheTagsWebsiteClient(): KernelBrowser
    {
        $kernel = new CacheTagsKernel('test', true, SuluKernel::CONTEXT_WEBSITE);
        $kernel->boot();

        $client = new KernelBrowser($kernel);

        return $client;
    }

    public function testPostPublish(): int
    {
        self::purgeDatabase();

        $tag1 = self::createTag(['name' => 'Tag 1']);
        $tag2 = self::createTag(['name' => 'Tag 2']);
        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/examples?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test Example',
            'url' => '/my-example',
            'published' => '2020-05-08T00:00:00+00:00', // Should be ignored
            'images' => null,
            'seo' => [
                'title' => 'Seo Title',
                'description' => 'Seo Description',
                'canonicalUrl' => 'https://sulu.io/',
                'keywords' => 'Seo Keyword 1, Seo Keyword 2',
            ],
            'seoNoIndex' => true,
            'seoNoFollow' => true,
            'seoHideInSitemap' => true,
            'excerpt' => [
                'title' => 'Excerpt Title',
                'description' => 'Excerpt Description',
                'more' => 'Excerpt More',
            ],
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'author' => null,
            'authored' => '2020-05-08T00:00:00+00:00',
            'lastModifiedEnabled' => true,
            'lastModified' => '2022-05-08T00:00:00+00:00',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $this->assertResponseSnapshot('example_post_publish.json', $response, 201);
        $this->assertNotSame('2020-05-08T00:00:00+00:00', $content['published']); // @phpstan-ignore-line

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $crawler = $websiteClient->request('GET', '/en/my-example');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('EXAMPLE 2 TEMPLATE', $content);

        $urls = [];
        $crawler->filter('nav[aria-label="Language switcher"] a')->each(function(Crawler $node) use (&$urls) {
            $urls[$node->text()] = $node->attr('href');
        });

        $this->assertSame([
            'de' => '/',
            'en' => '/en/my-example',
        ], $urls);

        return $id;
    }

    #[Depends('testPostPublish')]
    public function testCaching(): void
    {
        self::ensureKernelShutdown();

        $cacheKernel = new AppCache(self::bootKernel(['sulu.context' => SuluKernel::CONTEXT_WEBSITE]));
        $cookieJar = new CookieJar();
        $client = new KernelBrowser($cacheKernel, [], null, $cookieJar);
        $client->disableReboot();

        $client->request('PURGE', 'http://localhost');
        $cookieJar->clear();

        // first request should be cache miss
        $client->request('GET', '/en/my-example');
        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertSame('GET /en/my-example: miss, store', $response->headers->get('x-symfony-cache'));
        $this->assertTrue($response->isCacheable());
        $this->assertSame('max-age=240, public, s-maxage=240', $response->headers->get('Cache-Control'));
        $this->assertCount(0, $response->headers->getCookies());

        // second request should be cache hit
        $client->request('GET', '/en/my-example');
        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $this->assertSame('GET /en/my-example: fresh', $response->headers->get('x-symfony-cache'));
        $this->assertTrue($response->isCacheable());
        $this->assertSame('max-age=240, public, s-maxage=240', $response->headers->get('Cache-Control'));
        $this->assertCount(0, $response->headers->getCookies());
    }

    #[Depends('testPostPublish')]
    public function testCacheTagsAreGenerated(int $id): void
    {
        self::ensureKernelShutdown();

        $websiteClient = $this->createCacheTagsWebsiteClient();
        $websiteClient->request('GET', '/en/my-example');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $cacheTags = $response->headers->get('x-cache-tags');
        $this->assertNotNull($cacheTags, 'Cache tags header should be present in website response');

        $tags = \explode(',', $cacheTags);
        $tags = \array_map('trim', $tags);

        // 2 tags + webspace + example entity
        $this->assertCount(4, $tags, 'Cache tags should be present in response');

        $this->assertContains('webspace-sulu-io', $tags, 'Webspace cache tag should be in ReferenceStore');
        $this->assertContains('examples-' . $id, $tags, 'Example content cache tag should be in ReferenceStore');
    }

    #[Depends('testPostPublish')]
    public function testWebsite406ForNotExistFormat(int $id): int
    {
        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/en/my-example.rss');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(406, $response);

        return $id;
    }

    #[Depends('testPostPublish')]
    public function testWebsiteAcceptHeaderNotUsed(int $id): int
    {
        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/en/my-example', [], [], [
            'HTTP_ACCEPT' => 'text/plain',
        ]);

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->assertStringStartsWith(
            'text/html',
            $response->headers->get('Content-Type', '')
        );

        return $id;
    }

    #[Depends('testPostPublish')]
    public function testVersionListAfterPublish(int $id): int
    {
        $this->client->request('GET', '/admin/api/examples/' . $id . '/versions?page=1&locale=en&fields=title,version,changer,id');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('example_get_versions.json', $response, 200);

        return $id;
    }

    #[Depends('testVersionListAfterPublish')]
    public function testVersionListAfterPostModifyAndPublish(int $id): string
    {
        \sleep(1); // Ensure that the version timestamp is different from the previous version

        $modTag1 = self::createTag(['name' => 'Modified Tag 1']);
        $modTag2 = self::createTag(['name' => 'Modified Tag 2']);
        self::getEntityManager()->flush();

        $this->client->request(
            'PUT', '/admin/api/examples/' . $id . '?locale=en&action=publish', [], [], [],
            \json_encode(
                [
                    'template' => 'example-2',
                    'title' => 'Test modified version example',
                    'url' => '/my-example',
                    'images' => null,
                    'seo' => [
                        'title' => 'Modified Seo Title',
                        'description' => 'Modified Seo Description',
                        'canonicalUrl' => 'https://modified-sulu.io/',
                        'keywords' => 'Modified Seo Keyword 1, Modified Seo Keyword 2',
                    ],
                    'seoNoIndex' => true,
                    'seoNoFollow' => true,
                    'seoHideInSitemap' => true,
                    'excerpt' => [
                        'title' => 'Modified Excerpt Title',
                        'description' => 'Modified Excerpt Description',
                        'more' => 'Modified Excerpt More',
                    ],
                    'excerptTags' => [$modTag1->getId(), $modTag2->getId()],
                    'excerptCategories' => [],
                    'excerptIcon' => null,
                    'excerptMedia' => null,
                    'author' => null,
                    'authored' => '2020-05-08T00:00:00+00:00',
                    'lastModifiedEnabled' => true,
                    'lastModified' => '2022-05-08T00:00:00+00:00',
                    'mainWebspace' => 'sulu-io',
                ],
            ) ?: null,
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->client->request('GET', '/admin/api/examples/' . $id . '/versions?page=1&locale=en&fields=title,version,changer,id');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('example_get_versions_after_modify_and_publish.json', $response, 200);
        $content = \json_decode((string) $response->getContent(), true);

        /** @var string $version */
        $version = $content['_embedded']['examples_versions'][1]['version'] ?? null; // @phpstan-ignore-line
        $this->assertNotEmpty($version, 'Version should not be empty after publish');

        return $id . '::' . $version;
    }

    #[Depends('testVersionListAfterPostModifyAndPublish')]
    public function testRestoreVersion(string $idVersion): int
    {
        [$id, $version] = \explode('::', $idVersion, 2);

        $this->client->request('POST', '/admin/api/examples/' . $id . '?locale=en&action=restore&version=' . $version);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->client->request('GET', '/admin/api/examples/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('example_get_after_restore.json', $response, 200);

        return (int) $id;
    }

    #[Depends('testRestoreVersion')]
    public function testPostTriggerUnpublish(int $id): void
    {
        $this->client->request('POST', '/admin/api/examples/' . $id . '?locale=en&action=unpublish');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('example_post_trigger_unpublish.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/en/my-example');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    public function testPost(): int
    {
        self::purgeDatabase();

        $tag1 = self::createTag(['name' => 'Tag 1']);
        $tag2 = self::createTag(['name' => 'Tag 2']);
        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/examples?locale=en', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test Example',
            'url' => '/my-example',
            'images' => null,
            'seo' => [
                'title' => 'Seo Title',
                'description' => 'Seo Description',
                'canonicalUrl' => 'https://sulu.io/',
                'keywords' => 'Seo Keyword 1, Seo Keyword 2',
            ],
            'seoNoIndex' => true,
            'seoNoFollow' => true,
            'seoHideInSitemap' => true,
            'excerpt' => [
                'title' => 'Excerpt Title',
                'description' => 'Excerpt Description',
                'more' => 'Excerpt More',
            ],
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'mainWebspace' => 'sulu-io',
            'authored' => '2020-05-08T00:00:00+00:00',
            'lastModifiedEnabled' => true,
            'lastModified' => '2022-05-08T00:00:00+00:00',
        ]) ?: null);

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('example_post.json', $response, 201);

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(1, $routeRepository->findBy([]));

        /** @var int $id */
        $id = \json_decode((string) $response->getContent(), true)['id'] ?? null; // @phpstan-ignore-line

        return $id;
    }

    #[Depends('testPost')]
    public function testGet(int $id): void
    {
        $this->client->request('GET', '/admin/api/examples/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('example_get.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/en/my-example');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    #[Depends('testPost')]
    public function testGetGhostLocale(int $id): void
    {
        $this->client->request('GET', '/admin/api/examples/' . $id . '?locale=de');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('example_get_ghost_locale.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/de/my-example');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    #[Depends('testPost')]
    public function testPostTriggerCopyLocale(int $id): void
    {
        $this->client->request('POST', '/admin/api/examples/' . $id . '?locale=de&action=copy_locale&src=en&dest=de');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('example_post_trigger_copy_locale.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testGet')]
    public function testPut(int $id): void
    {
        $tag3 = self::createTag(['name' => 'Tag 3']);
        $tag4 = self::createTag(['name' => 'Tag 4']);
        self::getEntityManager()->flush();

        $this->client->request('PUT', '/admin/api/examples/' . $id . '?locale=en', [], [], [], \json_encode([
            'template' => 'default',
            'title' => 'Test Example 2',
            'url' => '/my-example-2',
            'article' => '<p>Test Article 2</p>',
            'seo' => [
                'title' => 'Seo Title 2',
                'description' => 'Seo Description 2',
                'canonicalUrl' => 'https://sulu.io/2',
                'keywords' => 'Seo Keyword 3, Seo Keyword 4',
            ],
            'seoNoIndex' => false,
            'seoNoFollow' => false,
            'seoHideInSitemap' => false,
            'excerpt' => [
                'title' => 'Excerpt Title 2',
                'description' => 'Excerpt Description 2',
                'more' => 'Excerpt More 2',
            ],
            'excerptTags' => [$tag3->getId(), $tag4->getId()],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'authored' => '2020-06-09T00:00:00+00:00',
            'mainWebspace' => 'sulu-io',
            'lastModifiedEnabled' => true,
            'lastModified' => '2022-05-08T00:00:00+00:00',
        ]) ?: null);

        $response = $this->client->getResponse();

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(3, $routeRepository->findBy([]));

        $this->assertResponseSnapshot('example_put.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testPut')]
    public function testGetList(): void
    {
        $this->client->request('GET', '/admin/api/examples?locale=en');
        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('example_cget.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testGetList')]
    public function testDelete(int $id): void
    {
        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(3, $routeRepository->findBy([]));
    }

    public function testReferencesCreatedWithMediaReferences(): int
    {
        self::purgeDatabase();

        // Create media entities
        $collection = $this->createCollection(['title' => 'Test Collection', 'locale' => 'en']);

        $media1 = $this->createMedia($collection, ['title' => 'Media 1', 'locale' => 'en']);
        $media2 = $this->createMedia($collection, ['title' => 'Media 2', 'locale' => 'en']);
        $media3 = $this->createMedia($collection, ['title' => 'Media 3', 'locale' => 'en']);
        $media4 = $this->createMedia($collection, ['title' => 'Media 4', 'locale' => 'en']);

        self::getEntityManager()->flush();

        // Create example with media references using the API
        $this->client->request('POST', '/admin/api/examples?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test Example with Media References',
            'url' => '/media-reference-example',
            'images' => [
                'ids' => [$media1->getId(), $media2->getId()],
            ],
            'seo' => [
                'title' => 'Media References Test',
                'description' => 'Testing media references',
            ],
            'excerpt' => [
                'title' => 'Media Test',
                'description' => 'Media test description',
                'image' => [
                    'id' => $media3->getId(),
                ],
                'icon' => [
                    'id' => $media4->getId(),
                ],
            ],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $this->assertHttpStatusCode(201, $response);

        // We should have 4 media references (2 for images + 1 for excerptImage + 1 for excerptIcon)
        $websiteReferenceCount = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'live',
        ]);
        $this->assertSame(4, $websiteReferenceCount);

        $adminReferenceCount = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'draft',
        ]);
        $this->assertSame(4, $adminReferenceCount);

        return $id;
    }

    public function testReferencesCreatedWithExampleReferences(): int
    {
        self::purgeDatabase();

        $referencedExample1 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Referenced Example 1',
                    'url' => '/referenced-1',
                ],
            ],
        ]);

        $referencedExample2 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Referenced Example 2',
                    'url' => '/referenced-2',
                ],
            ],
        ]);

        static::getEntityManager()->flush();

        // Create example with example references using the API
        $this->client->request('POST', '/admin/api/examples?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'default-example-selection',
            'title' => 'Test Example with Example References',
            'url' => '/example-reference-example',
            'examples' => [$referencedExample1->getId(), $referencedExample2->getId()],
            'seo' => [
                'title' => 'Example References Test',
                'description' => 'Testing example references',
            ],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $this->assertHttpStatusCode(201, $response);

        // We should have 2 example references per context
        $websiteReferenceCount = $this->referenceRepository->count([
            'resourceKey' => Example::RESOURCE_KEY,  // The referenced example entities
            'referenceResourceKey' => Example::RESOURCE_KEY,  // The Example entity that references them
            'referenceResourceId' => (string) $id,  // The specific example ID
            'referenceLocale' => 'en',
            'referenceContext' => DimensionContentInterface::STAGE_LIVE,
        ]);
        $this->assertSame(2, $websiteReferenceCount);

        $draftReferenceCount = $this->referenceRepository->count([
            'resourceKey' => Example::RESOURCE_KEY,  // The referenced example entities
            'referenceResourceKey' => Example::RESOURCE_KEY,  // The Example entity that references them
            'referenceResourceId' => (string) $id,  // The specific example ID
            'referenceLocale' => 'en',
            'referenceContext' => DimensionContentInterface::STAGE_DRAFT,
        ]);
        $this->assertSame(2, $draftReferenceCount);

        return $id;
    }

    public function testUpdateMediaReferences(): int
    {
        self::purgeDatabase();

        $collection = $this->createCollection(['title' => 'Test Collection', 'locale' => 'en']);

        $media1 = $this->createMedia($collection, ['title' => 'Media 1', 'locale' => 'en']);
        $media2 = $this->createMedia($collection, ['title' => 'Media 2', 'locale' => 'en']);
        $media3 = $this->createMedia($collection, ['title' => 'Media 3', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/examples?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test Example for Update',
            'url' => '/update-example',
            'images' => [
                'ids' => [$media1->getId(), $media2->getId()],
            ],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line
        $this->assertHttpStatusCode(201, $response);

        $initialReferenceCount = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'live',
        ]);
        $this->assertSame(2, $initialReferenceCount);

        $this->client->request('PUT', '/admin/api/examples/' . $id . '?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test Example for Update',
            'url' => '/update-example',
            'images' => [
                'ids' => [$media2->getId(), $media3->getId()],
            ],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $updatedReferenceCount = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'live',
        ]);
        $this->assertSame(2, $updatedReferenceCount);

        $media1ReferencesCount = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'resourceId' => (string) $media1->getId(),
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
        ]);
        $this->assertSame(0, $media1ReferencesCount);

        $media3ReferencesCount = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'resourceId' => (string) $media3->getId(),
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
        ]);
        $this->assertSame(2, $media3ReferencesCount);

        return $id;
    }

    public function testUpdateExampleReferences(): int
    {
        self::purgeDatabase();

        $referencedExample1 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Referenced Example 1',
                    'url' => '/referenced-1',
                ],
            ],
        ]);

        $referencedExample2 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Referenced Example 2',
                    'url' => '/referenced-2',
                ],
            ],
        ]);

        $referencedExample3 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Referenced Example 3',
                    'url' => '/referenced-3',
                ],
            ],
        ]);

        static::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/examples?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'default-example-selection',
            'title' => 'Test Example Update References',
            'url' => '/update-example-refs',
            'examples' => [$referencedExample1->getId(), $referencedExample2->getId()],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line
        $this->assertHttpStatusCode(201, $response);

        $initialReferenceCount = $this->referenceRepository->count([
            'resourceKey' => Example::RESOURCE_KEY,
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => DimensionContentInterface::STAGE_LIVE,
        ]);
        $this->assertSame(2, $initialReferenceCount);

        $this->client->request('PUT', '/admin/api/examples/' . $id . '?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'default-example-selection',
            'title' => 'Test Example Update References',
            'url' => '/update-example-refs',
            'examples' => [$referencedExample2->getId(), $referencedExample3->getId()],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $updatedReferenceCount = $this->referenceRepository->count([
            'resourceKey' => Example::RESOURCE_KEY,
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => DimensionContentInterface::STAGE_LIVE,
        ]);
        $this->assertSame(2, $updatedReferenceCount);

        $example1ReferencesCount = $this->referenceRepository->count([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $referencedExample1->getId(),
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
        ]);
        $this->assertSame(0, $example1ReferencesCount);

        $example3ReferencesCount = $this->referenceRepository->count([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $referencedExample3->getId(),
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
        ]);
        $this->assertSame(2, $example3ReferencesCount);

        return $id;
    }

    public function testDeleteEntityCleansUpMediaReferences(): void
    {
        self::purgeDatabase();

        $collection = $this->createCollection(['title' => 'Test Collection', 'locale' => 'en']);
        $media1 = $this->createMedia($collection, ['title' => 'Media 1', 'locale' => 'en']);
        $media2 = $this->createMedia($collection, ['title' => 'Media 2', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/examples?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test Delete Media References',
            'url' => '/delete-media-test',
            'images' => [
                'ids' => [$media1->getId(), $media2->getId()],
            ],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line
        $this->assertHttpStatusCode(201, $response);

        $referenceCount = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
        ]);
        $this->assertSame(4, $referenceCount);

        $this->client->request('DELETE', '/admin/api/examples/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        // Check if the entity was actually deleted
        $this->client->request('GET', '/admin/api/examples/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(404, $response);

        $referenceCountAfterDelete = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
        ]);
        $this->assertSame(0, $referenceCountAfterDelete);
    }

    public function testDeleteEntityCleansUpExampleReferences(): void
    {
        self::purgeDatabase();

        $referencedExample1 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Referenced Example 1',
                    'url' => '/ref-1',
                ],
            ],
        ]);
        $referencedExample2 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Referenced Example 2',
                    'url' => '/ref-2',
                ],
            ],
        ]);

        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/examples?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'default-example-selection',
            'title' => 'Test Delete Example References',
            'url' => '/delete-example-test',
            'examples' => [$referencedExample1->getId(), $referencedExample2->getId()],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line
        $this->assertHttpStatusCode(201, $response);

        $referenceCount = $this->referenceRepository->count([
            'resourceKey' => Example::RESOURCE_KEY,
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
        ]);
        $this->assertSame(4, $referenceCount);

        $this->client->request('DELETE', '/admin/api/examples/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $referenceCountAfterDelete = $this->referenceRepository->count([
            'resourceKey' => Example::RESOURCE_KEY,
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
        ]);
        $this->assertSame(0, $referenceCountAfterDelete);
    }

    public function testUnpublishCleansUpLiveReferences(): int
    {
        self::purgeDatabase();

        $collection = $this->createCollection(['title' => 'Test Collection', 'locale' => 'en']);
        $media1 = $this->createMedia($collection, ['title' => 'Media 1', 'locale' => 'en']);
        $media2 = $this->createMedia($collection, ['title' => 'Media 2', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/examples?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test Unpublish References',
            'url' => '/unpublish-test',
            'images' => [
                'ids' => [$media1->getId(), $media2->getId()],
            ],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line
        $this->assertHttpStatusCode(201, $response);

        $liveReferenceCount = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'live',
        ]);
        $this->assertSame(2, $liveReferenceCount);

        $draftReferenceCount = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'draft',
        ]);
        $this->assertSame(2, $draftReferenceCount);

        $this->client->request('POST', '/admin/api/examples/' . $id . '?locale=en&action=unpublish');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $liveReferenceCountAfterUnpublish = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'live',
        ]);
        $this->assertSame(0, $liveReferenceCountAfterUnpublish);

        $draftReferenceCountAfterUnpublish = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'draft',
        ]);
        $this->assertSame(2, $draftReferenceCountAfterUnpublish);

        return $id;
    }

    public function testMixedMediaAndExampleReferences(): int
    {
        self::purgeDatabase();

        $collection = $this->createCollection(['title' => 'Test Collection', 'locale' => 'en']);
        $media1 = $this->createMedia($collection, ['title' => 'Test Media', 'locale' => 'en']);

        $referencedExample1 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Referenced Example 1',
                    'url' => '/mixed-ref-1',
                ],
            ],
        ]);

        $referencedExample2 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Referenced Example 2',
                    'url' => '/mixed-ref-2',
                ],
            ],
        ]);

        static::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/examples?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'default-example-selection',
            'title' => 'Test Mixed References',
            'url' => '/mixed-references',
            'image' => [
                'id' => $media1->getId(),
            ],
            'examples' => [$referencedExample1->getId(), $referencedExample2->getId()],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line
        $this->assertHttpStatusCode(201, $response);

        $mediaReferenceCount = $this->referenceRepository->count([
            'resourceKey' => 'media',
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'live',
        ]);
        $this->assertSame(1, $mediaReferenceCount);

        $exampleReferenceCount = $this->referenceRepository->count([
            'resourceKey' => Example::RESOURCE_KEY,
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'live',
        ]);
        $this->assertSame(2, $exampleReferenceCount);

        $totalReferenceCount = $this->referenceRepository->count([
            'referenceResourceKey' => Example::RESOURCE_KEY,
            'referenceResourceId' => (string) $id,
            'referenceLocale' => 'en',
            'referenceContext' => 'live',
        ]);
        $this->assertSame(3, $totalReferenceCount);

        return $id;
    }

    public function testPublishPreservesDimensionContentTagRelations(): void
    {
        self::purgeDatabase();

        $category = $this->createCategory(['key' => 'test-category']);
        $this->createCategoryTranslation($category, ['title' => 'Test Category', 'locale' => 'en']);
        $tag1 = self::createTag(['name' => 'Test Tag 1']);
        $tag2 = self::createTag(['name' => 'Test Tag 2']);
        self::getEntityManager()->flush();

        /** @var ExampleRepository $exampleRepository */
        $exampleRepository = self::getContainer()->get('example_test.example_repository');
        $this->client->request('POST', '/admin/api/examples?locale=en', [], [], [], \json_encode([
            'template' => 'default',
            'title' => 'Tag Test Example',
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [$category->getId()],
            'url' => '/test-example',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response);
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('id', $content);
        $id = $content['id'];
        $this->assertIsInt($id);

        $example = $exampleRepository->findOneBy(['uuid' => $id]);
        $this->assertNotNull($example);

        $localizedDraft = null;
        foreach ($example->getDimensionContents() as $dimensionContent) {
            if ('draft' === $dimensionContent->getStage() && 'en' === $dimensionContent->getLocale() && 0 === $dimensionContent->getVersion()) {
                $localizedDraft = $dimensionContent;
                break;
            }
        }

        $this->assertNotNull($localizedDraft, 'Localized draft should exist before publish');
        $this->assertCount(2, $localizedDraft->getExcerptTags(), 'Localized draft should have 2 tags before publish');
        $this->assertCount(1, $localizedDraft->getExcerptCategories(), 'Localized draft should have 1 category before publish');

        self::getEntityManager()->clear();
        $this->client->request('PUT', '/admin/api/examples/' . $id . '?locale=en&action=publish', [], [], [], \json_encode($content) ?: null);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $example = $exampleRepository->findOneBy(['uuid' => $id]);
        $this->assertNotNull($example);

        $draftVersion0 = null;
        $draftVersioned = null;
        $liveVersion0 = null;

        foreach ($example->getDimensionContents() as $dimensionContent) {
            if ('en' !== $dimensionContent->getLocale()) {
                continue;
            }

            if ('draft' === $dimensionContent->getStage() && 0 === $dimensionContent->getVersion()) {
                $draftVersion0 = $dimensionContent;
            } elseif ('draft' === $dimensionContent->getStage() && $dimensionContent->getVersion() > 0) {
                $draftVersioned = $dimensionContent;
            } elseif ('live' === $dimensionContent->getStage() && 0 === $dimensionContent->getVersion()) {
                $liveVersion0 = $dimensionContent;
            }
        }

        $this->assertNotNull($draftVersion0, 'Draft version=0 dimension content should exist');
        $this->assertNotNull($draftVersioned, 'Draft versioned dimension content should exist');
        $this->assertNotNull($liveVersion0, 'Live version=0 dimension content should exist');

        $this->assertCount(2, $draftVersion0->getExcerptTags(), 'Draft version=0 should have 2 tags');
        $this->assertCount(2, $draftVersioned->getExcerptTags(), 'Draft versioned should have 2 tags');
        $this->assertCount(2, $liveVersion0->getExcerptTags(), 'Live version=0 should have 2 tags');

        $this->assertCount(1, $draftVersion0->getExcerptCategories(), 'Draft version=0 should have 1 category');
        $this->assertCount(1, $draftVersioned->getExcerptCategories(), 'Draft versioned should have 1 category');
        $this->assertCount(1, $liveVersion0->getExcerptCategories(), 'Live version=0 should have 1 category');
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

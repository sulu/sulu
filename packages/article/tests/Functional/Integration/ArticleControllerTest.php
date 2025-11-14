<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class ArticleControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    /**
     * @var KernelBrowser
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        // TODO this should not be necessary
        $requestContext = self::getContainer()->get('router.request_context');
        $requestContext->setParameter(RequestAttributeEnum::SITE->value, 'sulu-io');
        // TODO this should not be necessary
    }

    public function testPostPublish(): string
    {
        self::purgeDatabase();

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Test Article',
            'url' => '/my-article',
            'published' => '2020-05-08T00:00:00+00:00', // Should be ignored
            'description' => null,
            'image' => null,
            'lastModified' => '2022-05-08T00:00:00+00:00',
            'lastModifiedEnabled' => true,
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoCanonicalUrl' => 'https://sulu.io/',
            'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
            'seoNoIndex' => true,
            'seoNoFollow' => true,
            'seoHideInSitemap' => true,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'excerptSegment' => 'test-segment',
            'excerptAudienceTargetGroups' => [],
            'author' => null,
            'authored' => '2020-05-08T00:00:00+00:00',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);
        /** @var string $id */
        $id = $content['id'] ?? null;

        $this->assertResponseSnapshot('article_post_publish.json', $response, 201);
        $this->assertNotSame('2020-05-08T00:00:00+00:00', $content['published']);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', 'http://sulu.io/en/my-article');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Test Article', $content);

        return $id;
    }

    #[Depends('testPostPublish')]
    public function testVersionListAfterPublish(string $id): string
    {
        $this->client->request('GET', '/admin/api/articles/' . $id . '/versions?page=1&locale=en&fields=title,version,changer,id');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('article_get_versions.json', $response, 200);

        return $id;
    }

    #[Depends('testVersionListAfterPublish')]
    public function testVersionListAfterPostModifyAndPublish(string $id): string
    {
        \sleep(1); // Ensure that the version timestamp is different from the previous version

        $this->client->request(
            'PUT',
            '/admin/api/articles/' . $id . '?locale=en&action=publish',
            [],
            [],
            [],
            \json_encode(
                [
                    'template' => 'article',
                    'title' => 'Test modified version article',
                    'url' => '/my-article',
                    'description' => 'modified version',
                    'image' => null,
                    'seoTitle' => 'Modified Seo Title',
                    'seoDescription' => 'Modified Seo Description',
                    'seoCanonicalUrl' => 'https://modified-sulu.io/',
                    'seoKeywords' => 'Modified Seo Keyword 1, Modified Seo Keyword 2',
                    'seoNoIndex' => true,
                    'seoNoFollow' => true,
                    'seoHideInSitemap' => true,
                    'excerptTitle' => 'Modified Excerpt Title',
                    'excerptDescription' => 'Modified Excerpt Description',
                    'excerptMore' => 'Modified Excerpt More',
                    'excerptTags' => ['Modified Tag 1', 'Modified Tag 2'],
                    'excerptCategories' => [],
                    'excerptIcon' => null,
                    'excerptMedia' => null,
                    'excerptSegment' => 'modified-segment',
                    'excerptAudienceTargetGroups' => [],
                    'navigationContexts' => ['main'],
                ],
            ) ?: null,
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->client->request('GET', '/admin/api/articles/' . $id . '/versions?page=1&locale=en&fields=title,version,changer,id');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('article_get_versions_after_modify_and_publish.json', $response, 200);
        $content = \json_decode((string) $response->getContent(), true);

        /** @var string $version */
        $version = $content['_embedded']['articles_versions'][1]['version'] ?? null; // @phpstan-ignore-line
        $this->assertNotEmpty($version, 'Version should not be empty after publish');

        return $id . '::' . $version;
    }

    #[Depends('testVersionListAfterPostModifyAndPublish')]
    public function testRestoreVersion(string $idVersion): string
    {
        [$id, $version] = \explode('::', $idVersion, 2);

        $this->client->request('POST', '/admin/api/articles/' . $id . '?locale=en&action=restore&version=' . $version);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->client->request('GET', '/admin/api/articles/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('article_get_after_restore.json', $response, 200);

        return $id;
    }

    #[Depends('testPostPublish')]
    public function testPostTriggerUnpublish(string $id): void
    {
        $this->client->request('POST', '/admin/api/articles/' . $id . '?locale=en&action=unpublish');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('article_post_trigger_unpublish.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', 'http://sulu.io/en/my-article');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    public function testPost(): string
    {
        self::purgeDatabase();

        $this->client->request('POST', '/admin/api/articles?locale=en', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Test Article',
            'url' => '/my-article',
            'images' => null,
            'lastModified' => '2022-05-08T00:00:00+00:00',
            'lastModifiedEnabled' => true,
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoCanonicalUrl' => 'https://sulu.io/',
            'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
            'seoNoIndex' => true,
            'seoNoFollow' => true,
            'seoHideInSitemap' => true,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'excerptSegment' => 'draft-segment',
            'excerptAudienceTargetGroups' => [],
            'mainWebspace' => 'sulu-io',
            'authored' => '2020-05-08T00:00:00+00:00',
        ]) ?: null);

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('article_post.json', $response, 201);

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(1, $routeRepository->findBy([]));

        $responseData = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($responseData);
        /** @var string $id */
        $id = $responseData['id'] ?? null;

        return $id;
    }

    #[Depends('testPost')]
    public function testGet(string $id): void
    {
        $this->client->request('GET', '/admin/api/articles/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('article_get.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', 'http://sulu.io/en/my-article');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    #[Depends('testPost')]
    public function testGetGhostLocale(string $id): void
    {
        $this->client->request('GET', '/admin/api/articles/' . $id . '?locale=de');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('article_get_ghost_locale.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', 'http://sulu.io/de/my-article');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    #[Depends('testPost')]
    public function testPostTriggerCopyLocale(string $id): void
    {
        $this->client->request('POST', '/admin/api/articles/' . $id . '?locale=de&action=copy_locale&src=en&dest=de');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('article_post_trigger_copy_locale.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testGet')]
    public function testPut(string $id): void
    {
        $this->client->request('PUT', '/admin/api/articles/' . $id . '?locale=en', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Test Article 2',
            'url' => '/my-article-2',
            'description' => '<p>Test Article 2</p>',
            'seoTitle' => 'Seo Title 2',
            'seoDescription' => 'Seo Description 2',
            'seoCanonicalUrl' => 'https://sulu.io/2',
            'seoKeywords' => 'Seo Keyword 3, Seo Keyword 4',
            'seoNoIndex' => false,
            'seoNoFollow' => false,
            'seoHideInSitemap' => false,
            'excerptTitle' => 'Excerpt Title 2',
            'excerptDescription' => 'Excerpt Description 2',
            'excerptMore' => 'Excerpt More 2',
            'excerptTags' => ['Tag 3', 'Tag 4'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'excerptSegment' => 'updated-segment',
            'excerptAudienceTargetGroups' => [],
            'authored' => '2020-06-09T00:00:00+00:00',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(3, $routeRepository->findBy([]));

        $this->assertResponseSnapshot('article_put.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testPut')]
    public function testGetList(): void
    {
        $this->client->request('GET', '/admin/api/articles?locale=en');
        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('article_cget.json', $response, 200);
    }

    #[Depends('testPost')]
    public function testDeleteSingleLocale(string $id): string
    {
        $this->client->request('GET', '/admin/api/articles/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{template: string, title: string, url: string} $articleData */
        $articleData = \json_decode((string) $response->getContent(), true);

        $this->client->request('PUT', '/admin/api/articles/' . $id . '?locale=de', [], [], [], \json_encode([
            'template' => $articleData['template'],
            'title' => $articleData['title'] . ' (DE)',
            'url' => '/de' . $articleData['url'],
        ]) ?: null);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        /** @var array<int, string> $contentLocales */
        $contentLocales = $content['contentLocales'];
        $this->assertContains('en', $availableLocales);
        $this->assertContains('de', $availableLocales);
        $this->assertContains('en', $contentLocales);
        $this->assertContains('de', $contentLocales);

        $this->client->request('DELETE', '/admin/api/articles/' . $id . '?locale=de&deleteLocale=true');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->client->request('GET', '/admin/api/articles/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        /** @var array<int, string> $contentLocales */
        $contentLocales = $content['contentLocales'];
        $this->assertNotContains('de', $availableLocales);
        $this->assertContains('en', $availableLocales);
        $this->assertNotContains('de', $contentLocales);
        $this->assertContains('en', $contentLocales);

        $this->client->request('GET', '/admin/api/articles/' . $id . '?locale=de');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertEquals('en', $content['ghostLocale']);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        $this->assertContains('en', $availableLocales);
        $this->assertNotContains('de', $availableLocales);

        return $id;
    }

    #[Depends('testDeleteSingleLocale')]
    public function testRecreateDeletedLocale(string $id): string
    {
        $this->client->request('PUT', '/admin/api/articles/' . $id . '?locale=de', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Recreated Test Article (DE)',
            'url' => '/de/recreated-my-article',
        ]) ?: null);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        /** @var array<int, string> $contentLocales */
        $contentLocales = $content['contentLocales'];
        $this->assertContains('en', $availableLocales);
        $this->assertContains('de', $availableLocales);
        $this->assertContains('en', $contentLocales);
        $this->assertContains('de', $contentLocales);

        return $id;
    }

    #[Depends('testPost')]
    #[Depends('testGetList')]
    public function testDelete(string $id): int
    {
        $this->client->request('DELETE', '/admin/api/articles/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(0, $routeRepository->findBy([]));

        $trashRepository = self::getContainer()->get(TrashItemRepositoryInterface::class);
        $trashItem = $trashRepository->findOneBy([
            'resourceKey' => ArticleInterface::RESOURCE_KEY,
            'resourceId' => $id,
        ]);
        $this->assertNotNull($trashItem);
        $id = $trashItem->getId();
        $this->assertNotEmpty($id);

        return $id;
    }

    #[Depends('testDelete')]
    public function testRestore(int $trashItemId): void
    {
        $trashItem = $this->getContainer()->get(TrashItemRepositoryInterface::class)->findOneBy(['id' => $trashItemId]);
        $this->assertNotNull($trashItem);

        $this->client->request('POST', '/admin/api/trash-items/' . $trashItemId . '?action=restore');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->client->request('GET', '/admin/api/articles/' . $trashItem->getResourceId() . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('article_post_restore.json', $response, 200);
    }

    public function testGetListWithTemplateFiltering(): void
    {
        self::purgeDatabase();

        // Create articles with different templates
        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Article Template Test',
            'url' => '/article-template',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response1 = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response1);
        $content1 = \json_decode((string) $response1->getContent(), true);
        $this->assertIsArray($content1);
        $articleId = $content1['id'];

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'blog',
            'title' => 'Blog Template Test',
            'url' => '/blog-template',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response2 = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response2);

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'news',
            'title' => 'News Template Test',
            'url' => '/news-template',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response3 = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response3);

        // Test single template filter
        $this->client->request('GET', '/admin/api/articles?locale=en&templates=article');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);

        $this->assertSame(1, $content['total']);
        $this->assertIsArray($content['_embedded']);
        $this->assertIsArray($content['_embedded']['articles']);
        $this->assertIsArray($content['_embedded']['articles'][0]);
        $this->assertSame('Article Template Test', $content['_embedded']['articles'][0]['title']);

        // Test multiple template filter
        $this->client->request('GET', '/admin/api/articles?locale=en&templates=article,blog');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);

        $this->assertSame(2, $content['total']);
        $this->assertIsArray($content['_embedded']);
        $this->assertIsArray($content['_embedded']['articles']);
        $titles = \array_column($content['_embedded']['articles'], 'title');
        $this->assertContains('Article Template Test', $titles);
        $this->assertContains('Blog Template Test', $titles);

        // Test template filter with empty values (should be filtered out)
        $this->client->request('GET', '/admin/api/articles?locale=en&templates=article,,blog,');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);

        $this->assertSame(2, $content['total']);
        $this->assertIsArray($content['_embedded']);
        $this->assertIsArray($content['_embedded']['articles']);
        $titles = \array_column($content['_embedded']['articles'], 'title');
        $this->assertContains('Article Template Test', $titles);
        $this->assertContains('Blog Template Test', $titles);

        // Test all templates (no filter)
        $this->client->request('GET', '/admin/api/articles?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);

        $this->assertSame(3, $content['total']);

        // Test empty template filter (should return all)
        $this->client->request('GET', '/admin/api/articles?locale=en&templates=');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);

        $this->assertSame(3, $content['total']);

        // Test non-existent template (should return no results)
        $this->client->request('GET', '/admin/api/articles?locale=en&templates=nonexistent');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);

        $this->assertSame(0, $content['total']);
    }

    public function testGetListWithGroupFiltering(): void
    {
        self::purgeDatabase();

        // Create articles with different templates
        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Article Template Test',
            'url' => '/article-template',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response1 = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response1);
        $content1 = \json_decode((string) $response1->getContent(), true);
        $this->assertIsArray($content1);
        $articleId = $content1['id'];

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'blog',
            'title' => 'Blog Template Test',
            'url' => '/blog-template',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response2 = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response2);

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'news',
            'title' => 'News Template Test',
            'url' => '/news-template',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response3 = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response3);

        // Test type filter
        $this->client->request('GET', '/admin/api/articles?locale=en&types=blog-group');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);

        $this->assertSame(1, $content['total']);
        $this->assertIsArray($content['_embedded']);
        $this->assertIsArray($content['_embedded']['articles']);
        $this->assertIsArray($content['_embedded']['articles'][0]);
        $this->assertSame('Blog Template Test', $content['_embedded']['articles'][0]['title']);
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

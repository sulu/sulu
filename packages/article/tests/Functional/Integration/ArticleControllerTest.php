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
use Sulu\Component\Rest\Exception\RestExceptionInterface;
use Sulu\Content\Tests\Traits\CreateTagTrait;
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
    use CreateTagTrait;

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
        $requestContext->setParameter(RequestAttributeEnum::WEBSPACE->value, 'sulu-io');
        // TODO this should not be necessary
    }

    public function testInvalidIdGet(): void
    {
        $this->client->request('GET', '/admin/api/articles/invalid-id?locale=en');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(404, $response);
    }

    public function testPostPublish(): string
    {
        self::purgeDatabase();

        $tag1 = self::createTag(['name' => 'Tag 1']);
        $tag2 = self::createTag(['name' => 'Tag 2']);
        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Test Article',
            'url' => '/my-article',
            'published' => '2020-05-08T00:00:00+00:00', // Should be ignored
            'description' => null,
            'image' => null,
            'lastModified' => '2022-05-08T00:00:00+00:00',
            'lastModifiedEnabled' => true,
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
                'icon' => null,
                'image' => null,
            ],
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [],
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
    public function testGetListPublished(): void
    {
        $this->client->request('GET', '/admin/api/articles?locale=en');
        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('article_cget_published.json', $response, 200);
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

        $modTag1 = self::createTag(['name' => 'Modified Tag 1']);
        $modTag2 = self::createTag(['name' => 'Modified Tag 2']);
        self::getEntityManager()->flush();

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
                        'icon' => null,
                        'image' => null,
                    ],
                    'excerptTags' => [$modTag1->getId(), $modTag2->getId()],
                    'excerptCategories' => [],
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

        $tag1 = self::createTag(['name' => 'Tag 1']);
        $tag2 = self::createTag(['name' => 'Tag 2']);
        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/articles?locale=en', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Test Article',
            'url' => '/my-article',
            'images' => null,
            'lastModified' => '2022-05-08T00:00:00+00:00',
            'lastModifiedEnabled' => true,
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
                'icon' => null,
                'image' => null,
            ],
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [],
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
    public function testPutShadowLocale(string $id): string
    {
        $this->client->request('PUT', '/admin/api/articles/' . $id . '?locale=de', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Test Article (DE)',
            'url' => '/mein-artikel',
            'shadowOn' => true,
            'shadowLocale' => 'en',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        return $id;
    }

    #[Depends('testPutShadowLocale')]
    public function testGetShadowLocale(string $id): void
    {
        $this->client->request('GET', '/admin/api/articles/' . $id . '?locale=de');
        $response = $this->client->getResponse();

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertTrue($content['shadowOn']);
        $this->assertSame('en', $content['shadowLocale']);
        $this->assertSame(['en', 'de'], $content['availableLocales']);
        $this->assertSame(['en', 'de'], $content['contentLocales']);

        $this->assertResponseSnapshot('article_get_shadow_locale.json', $response, 200);
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
    #[Depends('testPutShadowLocale')]
    public function testPut(string $id): void
    {
        $tag3 = self::createTag(['name' => 'Tag 3']);
        $tag4 = self::createTag(['name' => 'Tag 4']);
        self::getEntityManager()->flush();

        $this->client->request('PUT', '/admin/api/articles/' . $id . '?locale=en', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Test Article 2',
            'url' => '/my-article-2',
            'description' => '<p>Test Article 2</p>',
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
                'icon' => null,
                'image' => null,
            ],
            'excerptTags' => [$tag3->getId(), $tag4->getId()],
            'excerptCategories' => [],
            'excerptSegment' => 'updated-segment',
            'excerptAudienceTargetGroups' => [],
            'authored' => '2020-06-09T00:00:00+00:00',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(4, $routeRepository->findBy([]));

        $this->assertResponseSnapshot('article_put.json', $response, 200);
    }

    #[Depends('testPost')]
    public function testPutWithInvalidHashReturnsConflict(string $id): void
    {
        $this->client->request('PUT', '/admin/api/articles/' . $id . '?locale=en', [], [], [], \json_encode([
            '_hash' => 'invalid-hash',
            'template' => 'article',
            'title' => 'Test Article 2',
            'url' => '/my-article-2',
        ]) ?: null);

        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(409, $response);

        /** @var array{code: int, message: string} $responseData */
        $responseData = \json_decode((string) $response->getContent(), true);

        $this->assertEquals(RestExceptionInterface::EXCEPTION_CODE_INVALID_HASH, $responseData['code']);
    }

    #[Depends('testPost')]
    public function testPutWithForceIgnoresHash(string $id): void
    {
        $this->client->request('PUT', '/admin/api/articles/' . $id . '?locale=en&force=true', [], [], [], \json_encode([
            '_hash' => 'invalid-hash',
            'template' => 'article',
            'title' => 'Test Article 2',
            'url' => '/my-article-2',
        ]) ?: null);

        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
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
            'restoreType' => null,
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

    public function testGetListWithTitleSearch(): void
    {
        self::purgeDatabase();

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Needle Article',
            'url' => '/needle-article',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Haystack Article',
            'url' => '/haystack-article',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        $this->client->request('GET', '/admin/api/articles?locale=en&search=Needle&searchFields=title&fields=title,id');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{total: int, _embedded: array{articles: array<int, array{title: string, id: string}>}} $content */
        $content = \json_decode((string) $response->getContent(), true);

        $this->assertSame(1, $content['total']);
        $this->assertCount(1, $content['_embedded']['articles']);
        $this->assertSame('Needle Article', $content['_embedded']['articles'][0]['title']);
    }

    public function testGetListWithTagFiltering(): void
    {
        self::purgeDatabase();

        $tagMatch = self::createTag(['name' => 'Tag Filter Match']);
        $tagOther = self::createTag(['name' => 'Tag Filter Other']);
        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Tagged Match Article',
            'url' => '/tagged-match-article',
            'excerptTags' => [$tagMatch->getId()],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Tagged Other Article',
            'url' => '/tagged-other-article',
            'excerptTags' => [$tagOther->getId()],
            'mainWebspace' => 'sulu-io',
        ]) ?: null);
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        $this->client->request('GET', '/admin/api/articles?locale=en&filter[tagId]=' . $tagMatch->getId() . '&fields=title,id');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{total: int, _embedded: array{articles: array<int, array{title: string, id: string}>}} $content */
        $content = \json_decode((string) $response->getContent(), true);

        $this->assertSame(1, $content['total']);
        $this->assertCount(1, $content['_embedded']['articles']);
        $this->assertSame('Tagged Match Article', $content['_embedded']['articles'][0]['title']);
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

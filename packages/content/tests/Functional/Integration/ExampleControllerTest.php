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

use PHPUnit\Framework\Attributes\Depends;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
class ExampleControllerTest extends SuluTestCase
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
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );
    }

    public function testPostPublish(): int
    {
        self::purgeDatabase();

        $this->client->request('POST', '/admin/api/examples?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test Example',
            'url' => '/my-example',
            'published' => '2020-05-08T00:00:00+00:00', // Should be ignored
            'images' => null,
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
        $websiteClient->request('GET', '/en/my-example');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('EXAMPLE 2 TEMPLATE', $content);

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

        $this->client->request(
            'PUT', '/admin/api/examples/' . $id . '?locale=en&action=publish', [], [], [],
            \json_encode(
                [
                    'template' => 'example-2',
                    'title' => 'Test modified version example',
                    'url' => '/my-example',
                    'images' => null,
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

        $this->client->request('POST', '/admin/api/examples?locale=en', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test Example',
            'url' => '/my-example',
            'images' => null,
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
        $this->client->request('POST', '/admin/api/examples/' . $id . '?locale=de&action=copy-locale&src=en&dest=de');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('example_post_trigger_copy_locale.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testGet')]
    public function testPut(int $id): void
    {
        $this->client->request('PUT', '/admin/api/examples/' . $id . '?locale=en', [], [], [], \json_encode([
            'template' => 'default',
            'title' => 'Test Example 2',
            'url' => '/my-example-2',
            'article' => '<p>Test Article 2</p>',
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
            'authored' => '2020-06-09T00:00:00+00:00',
            'mainWebspace' => 'sulu-io2',
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
        $this->client->request('DELETE', '/admin/api/examples/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(3, $routeRepository->findBy([])); // TODO we need tackle this
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

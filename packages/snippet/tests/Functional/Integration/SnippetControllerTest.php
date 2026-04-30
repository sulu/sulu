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

namespace Sulu\Snippet\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Content\Tests\Traits\CreateTagTrait;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\UserInterface\Controller\Admin\SnippetController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class SnippetControllerTest extends SuluTestCase
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
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );
    }

    public function testInvalidIdGet(): void
    {
        $this->client->request('GET', '/admin/api/snippets/invalid-id?locale=en');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(404, $response);
    }

    public function testPostPublish(): string
    {
        self::purgeDatabase();

        $tag1 = self::createTag(['name' => 'Tag 1']);
        $tag2 = self::createTag(['name' => 'Tag 2']);
        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/snippets?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'snippet',
            'title' => 'Test Snippet',
            'published' => '2020-05-08T00:00:00+00:00', // Should be ignored
            'description' => null,
            'image' => null,
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [],
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var string $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $this->assertResponseSnapshot('snippet_post_publish.json', $response, 201);
        $this->assertNotSame('2020-05-08T00:00:00+00:00', $content['published']); // @phpstan-ignore-line

        self::ensureKernelShutdown();

        return $id;
    }

    #[Depends('testPostPublish')]
    public function testVersionListAfterPublish(string $id): string
    {
        $this->client->request('GET', '/admin/api/snippets/' . $id . '/versions?page=1&locale=en&fields=title,version,changer,id');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_get_versions.json', $response, 200);

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
            'PUT', '/admin/api/snippets/' . $id . '?locale=en&action=publish', [], [], [],
            \json_encode(
                [
                    'template' => 'snippet',
                    'title' => 'Test modified version snippet',
                    'description' => 'modified version',
                    'excerptTags' => [$modTag1->getId(), $modTag2->getId()],
                    'excerptCategories' => [],
                ],
            ) ?: null,
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->client->request('GET', '/admin/api/snippets/' . $id . '/versions?page=1&locale=en&fields=title,version,changer,id');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_get_versions_after_modify_and_publish.json', $response, 200);
        $content = \json_decode((string) $response->getContent(), true);

        /** @var string $version */
        $version = $content['_embedded']['snippets_versions'][1]['version'] ?? null; // @phpstan-ignore-line
        $this->assertNotEmpty($version, 'Version should not be empty after publish');

        return $id . '::' . $version;
    }

    #[Depends('testVersionListAfterPostModifyAndPublish')]
    public function testRestoreVersion(string $idVersion): string
    {
        [$id, $version] = \explode('::', $idVersion, 2);

        $this->client->request('POST', '/admin/api/snippets/' . $id . '?locale=en&action=restore&version=' . $version);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->client->request('GET', '/admin/api/snippets/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_get_after_restore.json', $response, 200);

        return $id;
    }

    #[Depends('testPostPublish')]
    public function testPostTriggerUnpublish(string $id): void
    {
        $this->client->request('POST', '/admin/api/snippets/' . $id . '?locale=en&action=unpublish');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('snippet_post_trigger_unpublish.json', $response, 200);
    }

    public function testPost(): string
    {
        self::purgeDatabase();

        $tag1 = self::createTag(['name' => 'Tag 1']);
        $tag2 = self::createTag(['name' => 'Tag 2']);
        self::getEntityManager()->flush();

        $this->client->request('POST', '/admin/api/snippets?locale=en', [], [], [], \json_encode([
            'template' => 'snippet',
            'title' => 'Test Snippet',
            'images' => null,
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [],
        ]) ?: null);

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('snippet_post.json', $response, 201);

        /** @var string $id */
        $id = \json_decode((string) $response->getContent(), true)['id'] ?? null; // @phpstan-ignore-line

        return $id;
    }

    #[Depends('testPost')]
    public function testGet(string $id): void
    {
        $this->client->request('GET', '/admin/api/snippets/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_get.json', $response, 200);
    }

    #[Depends('testPost')]
    public function testGetGhostLocale(string $id): void
    {
        $this->client->request('GET', '/admin/api/snippets/' . $id . '?locale=de');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_get_ghost_locale.json', $response, 200);
    }

    #[Depends('testPost')]
    public function testPostTriggerCopyLocale(string $id): void
    {
        $this->client->request('POST', '/admin/api/snippets/' . $id . '?locale=de&action=copy_locale&src=en&dest=de');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('snippet_post_trigger_copy_locale.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testGet')]
    public function testPut(string $id): void
    {
        $tag3 = self::createTag(['name' => 'Tag 3']);
        $tag4 = self::createTag(['name' => 'Tag 4']);
        self::getEntityManager()->flush();

        $this->client->request('PUT', '/admin/api/snippets/' . $id . '?locale=en', [], [], [], \json_encode([
            'template' => 'snippet',
            'title' => 'Test Snippet 2',
            'description' => '<p>Test Snippet 2</p>',
            'excerptTags' => [$tag3->getId(), $tag4->getId()],
            'excerptCategories' => [],
        ]) ?: null);

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('snippet_put.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testPut')]
    public function testGetList(): void
    {
        $this->client->request('GET', '/admin/api/snippets?locale=en');
        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('snippet_cget.json', $response, 200);
    }

    #[Depends('testPost')]
    public function testGetListWithTypesFilter(): void
    {
        // Test filtering by existing type - should return the snippet
        $this->client->request('GET', '/admin/api/snippets?locale=en&types=snippet');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_cget_types_filter_snippet.json', $response, 200);

        // Test filtering by non-existent type - should return no results
        $this->client->request('GET', '/admin/api/snippets?locale=en&types=non-existent-type');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_cget_types_filter_nonexistent.json', $response, 200);

        // Test filtering by multiple types - should return snippets of the existing type
        $this->client->request('GET', '/admin/api/snippets?locale=en&types=snippet,other-template');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_cget_types_filter_multiple.json', $response, 200);
    }

    #[Depends('testPost')]
    public function testDeleteSingleLocale(string $id): string
    {
        $this->client->request('GET', '/admin/api/snippets/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{template: string, title: string} $snippetData */
        $snippetData = \json_decode((string) $response->getContent(), true);

        $this->client->request('PUT', '/admin/api/snippets/' . $id . '?locale=de', [], [], [], \json_encode([
            'template' => $snippetData['template'],
            'title' => $snippetData['title'] . ' (DE)',
        ]) ?: null);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        $this->assertContains('en', $availableLocales);
        $this->assertContains('de', $availableLocales);

        $this->client->request('DELETE', '/admin/api/snippets/' . $id . '?locale=de&deleteLocale=true');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->client->request('GET', '/admin/api/snippets/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        $this->assertNotContains('de', $availableLocales);
        $this->assertContains('en', $availableLocales);

        $this->client->request('GET', '/admin/api/snippets/' . $id . '?locale=de');
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
        $this->client->request('GET', '/admin/api/snippets/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{template: string, title: string} $snippetData */
        $snippetData = \json_decode((string) $response->getContent(), true);

        $this->client->request('PUT', '/admin/api/snippets/' . $id . '?locale=de', [], [], [], \json_encode([
            'template' => $snippetData['template'],
            'title' => 'Recreated Test Snippet (DE)',
        ]) ?: null);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        $this->assertContains('en', $availableLocales);
        $this->assertContains('de', $availableLocales);

        return $id;
    }

    #[Depends('testPost')]
    #[Depends('testGetList')]
    public function testDelete(string $id): int
    {
        $this->client->request('DELETE', '/admin/api/snippets/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $trashRepository = self::getContainer()->get(TrashItemRepositoryInterface::class);
        $trashItem = $trashRepository->findOneBy([
            'resourceKey' => SnippetInterface::RESOURCE_KEY,
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

        $this->client->request('GET', '/admin/api/snippets/' . $trashItem->getResourceId() . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_post_restore.json', $response, 200);
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }

    #[DataProvider('getLocaleProvider')]
    public function testGetLocale(string $locale): void
    {
        /** @var SnippetController $reflectionClass */
        $reflectionClass = (new \ReflectionClass(SnippetController::class))
            ->newInstanceWithoutConstructor();

        $request = new Request();
        $request->query->set('locale', $locale);

        $this->assertSame($locale, $reflectionClass->getLocale($request));
    }

    public static function getLocaleProvider(): \Generator
    {
        yield [
            'de',
        ];

        yield [
            'de_ch',
        ];
    }
}

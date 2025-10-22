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

namespace Sulu\CustomUrl\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class CustomUrlControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\KernelBrowser
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );
    }

    /**
     * @param array<string> $domainParts
     *
     * @return array<string,mixed>
     */
    private static function createCustomUrlData(
        string $title,
        string $baseDomain = '*.sulu.io/*',
        array $domainParts = ['test-11', 'test-21'],
        bool $published = true,
        ?Uuid $targetDocument = null,
        string $targetLocale = 'en',
        bool $redirect = true,
    ): array {
        return [
            'title' => $title,
            'published' => $published,
            'baseDomain' => $baseDomain,
            'domainParts' => $domainParts,
            'targetDocument' => $targetDocument ?? Uuid::v4(),
            'targetLocale' => $targetLocale,
            'canonical' => true,
            'redirect' => $redirect,
            'noFollow' => true,
            'noIndex' => true,
        ];
    }

    public function testPost(): string
    {
        self::purgeDatabase();

        $data = self::createCustomUrlData(
            title: 'Test',
            baseDomain: '*.sulu.io/*',
            domainParts: ['test-1', 'test-2'],
            targetDocument: Uuid::fromString('5cc1f411-e1ee-4dcc-8f07-6af4aa1d24cf'),
        );

        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_post.json', $response, Response::HTTP_OK);

        /** @var string $id */
        $id = \json_decode((string) $response->getContent(), true)['id'] ?? null; // @phpstan-ignore-line

        return $id;
    }

    #[Depends('testPost')]
    public function testGet(string $id): string
    {
        $this->client->jsonRequest('GET', '/admin/api/webspaces/sulu_io/custom-urls/' . $id);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_get.json', $response, Response::HTTP_OK);

        return $id;
    }

    #[Depends('testGet')]
    public function testCGet(string $id): string
    {
        $this->client->jsonRequest('GET', '/admin/api/webspaces/sulu_io/custom-urls');

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_cget.json', $response, Response::HTTP_OK);

        return $id;
    }

    #[Depends('testCGet')]
    public function testDelete(string $id): void
    {
        $this->client->jsonRequest('DELETE', '/admin/api/webspaces/sulu_io/custom-urls/' . $id);
        $this->assertHttpStatusCode(Response::HTTP_NO_CONTENT, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/admin/api/webspaces/sulu_io/custom-urls/' . $id);
        $this->assertHttpStatusCode(Response::HTTP_NOT_FOUND, $this->client->getResponse());
    }

    public function testDeleteWithTrashStore(): string
    {
        self::purgeDatabase();

        $data = self::createCustomUrlData(
            title: 'Trash Test',
            baseDomain: '*.sulu.io/*',
            domainParts: ['trash', 'test'],
            targetDocument: Uuid::fromString('5cc1f411-e1ee-4dcc-8f07-6af4aa1d24cf'),
        );

        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_OK, $response);

        /** @var string $id */
        $id = \json_decode((string) $response->getContent(), true)['id'] ?? null; // @phpstan-ignore-line

        // Delete the custom URL (should create trash item)
        $this->client->jsonRequest('DELETE', '/admin/api/webspaces/sulu_io/custom-urls/' . $id);
        $this->assertHttpStatusCode(Response::HTTP_NO_CONTENT, $this->client->getResponse());

        // Verify it's deleted
        $this->client->jsonRequest('GET', '/admin/api/webspaces/sulu_io/custom-urls/' . $id);
        $this->assertHttpStatusCode(Response::HTTP_NOT_FOUND, $this->client->getResponse());

        return $id;
    }

    #[Depends('testDeleteWithTrashStore')]
    public function testTrashRestore(string $deletedId): void
    {
        // Get the trash item for the deleted custom URL
        $trashItemRepository = self::getContainer()->get('sulu_trash.trash_item_repository');
        $customUrlTrashItem = $trashItemRepository->findOneBy([
            'resourceKey' => 'custom_urls',
            'resourceId' => $deletedId,
        ]);

        $this->assertNotNull($customUrlTrashItem, 'Trash item for custom URL should be created');
        $this->assertSame('Trash Test', $customUrlTrashItem->getResourceTitle());

        // Restore from trash using the handler directly
        $trashItemHandler = self::getContainer()->get('sulu_custom_urls.custom_url_trash_item_handler');
        $restoredCustomUrl = $trashItemHandler->restore($customUrlTrashItem, []);
        $this->assertInstanceOf(CustomUrl::class, $restoredCustomUrl);

        $this->assertSame('Trash Test', $restoredCustomUrl->getTitle());
        $this->assertSame($deletedId, $restoredCustomUrl->getUuid());
        $this->assertSame('*.sulu.io/*', $restoredCustomUrl->getBaseDomain());
        $this->assertSame(['trash', 'test'], $restoredCustomUrl->getDomainParts());

        // Verify the custom URL can be retrieved via API
        $this->client->jsonRequest('GET', '/admin/api/webspaces/sulu_io/custom-urls/' . $deletedId);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_OK, $response);

        /** @var array{title: string} $restoredContent */
        $restoredContent = \json_decode((string) $response->getContent(), true); // @phpstan-ignore-line
        $this->assertSame('Trash Test', $restoredContent['title']);
    }

    public function testPostWithoutTargetDocument(): void
    {
        self::purgeDatabase();

        $data = self::createCustomUrlData(
            title: 'Without target document',
            baseDomain: '*.sulu.io/*',
            domainParts: ['foo', 'bar'],
        );
        unset($data['targetDocument']);

        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_post_without_target_document.json', $response, Response::HTTP_OK);
    }

    public function testPostConflictingPaths(): void
    {
        self::purgeDatabase();

        $data = self::createCustomUrlData(
            title: 'Test',
            baseDomain: '*.sulu.io/*',
            domainParts: ['test-1', 'test-2'],
        );
        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $data = self::createCustomUrlData(
            title: 'Conflicting paths',
            baseDomain: '*.sulu.io/*',
            domainParts: ['test-1', 'test-2'],
        );
        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_post_conflicting_paths.json', $response, Response::HTTP_CONFLICT);
    }

    public function testPostWithTooLittleDomainParts(): void
    {
        self::purgeDatabase();

        $data = self::createCustomUrlData(
            title: 'Too little domain parts',
            baseDomain: '*.sulu.io/*',
            domainParts: ['test-1'],
        );

        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_post_with_too_little_domain_parts.json', $response, Response::HTTP_BAD_REQUEST);
    }

    public function testPostWithAlreadyExistingTitle(): void
    {
        self::purgeDatabase();

        $data = self::createCustomUrlData(title: 'Test');
        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);
        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot(
            'custom_url_post_with_already_existing_title.json',
            $response,
            Response::HTTP_BAD_REQUEST,
        );
    }

    /**
     * @return \Generator<string,array{array<string,mixed>, int}>
     */
    public static function putProvider(): \Generator
    {
        yield 'Normal document update' => [
            self::createCustomUrlData(
                title: 'Updated Custom URL',
                baseDomain: '*.sulu.io/*',
                domainParts: ['one', 'two'],
            ),
            Response::HTTP_OK,
            'custom_url_put_update.json',
        ];

        yield 'too few domain parts' => [
            self::createCustomUrlData(
                title: 'Too little domain parts',
                baseDomain: '*.sulu.io/*',
                domainParts: ['test-1'],
            ),
            Response::HTTP_BAD_REQUEST,
            'custom_url_put_update_with_too_little_domain_parts.json',
        ];

        yield 'Already existing title' => [
            self::createCustomUrlData(
                title: 'Test',
                published: true,
                baseDomain: '*.sulu.io/*',
                domainParts: ['foo', 'bar'],
            ),
            Response::HTTP_BAD_REQUEST,
            'custom_url_put_update_with_already_existing_title.json',
        ];

        yield 'Already existing path' => [
            self::createCustomUrlData(title: 'Test-1'),
            Response::HTTP_CONFLICT,
            'custom_url_put_update_with_conflicting_path.json',
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    #[DataProvider('putProvider')]
    public function testPut(array $data, int $statusCode, string $snapshotFile): void
    {
        self::purgeDatabase();

        // Creating a default custom url to test update conflicts
        $this->createCustomUrl();

        // Creating a custom URL to modify
        $id = $this->createCustomUrl(
            self::createCustomUrlData(
                title: 'Update me',
                published: true,
                baseDomain: '*.sulu.io/*',
                domainParts: ['a', 'b'],
            ),
        );

        $this->client->jsonRequest('PUT', '/admin/api/webspaces/sulu_io/custom-urls/' . $id, $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot($snapshotFile, $response, $statusCode);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function createCustomUrl(array $data = []): string
    {
        if ([] === $data) {
            $data = self::createCustomUrlData(title: 'Test');
        }
        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);
        $response = $this->client->getResponse();

        /** @var array{id: string} $responseData */
        $responseData = \json_decode((string) $response->getContent(), true);

        return $responseData['id'];
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

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

use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Traits\CreateTagTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
class SnippetAreaControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;
    use CreateTagTrait;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        self::purgeDatabase();
    }

    public function testGetList(): void
    {
        $this->client->jsonRequest('GET', '/admin/api/snippet-areas?webspaceKey=sulu-io');

        $this->assertResponseSnapshot('snippet_area_cget.json', $this->client->getResponse(), 200);
    }

    public function testPost(): void
    {
        $tag1 = self::createTag(['name' => 'Tag 1']);
        $tag2 = self::createTag(['name' => 'Tag 2']);
        self::getEntityManager()->flush();

        $this->client->jsonRequest('POST', '/admin/api/snippets?locale=en&action=publish', [
            'template' => 'snippet',
            'title' => 'Test Snippet',
            'images' => null,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
        ]);

        $response = $this->client->getResponse();

        $responseContent = \json_decode((string) $response->getContent(), true) ?? [];
        /** @var array{id: string} $responseContent */
        $id = $responseContent['id'];

        $this->client->jsonRequest('PUT', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io', [
            'snippetUuid' => (string) $id,
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->client->jsonRequest('GET', '/admin/api/snippet-areas?webspaceKey=sulu-io');
        $this->assertResponseSnapshot('snippet_area_cget_partially_filled.json', $this->client->getResponse(), 200);
    }

    public function testPostWithInvalidSnippetUuid(): void
    {
        $this->client->jsonRequest('PUT', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io', [
            'snippetUuid' => 'invalid-uuid',
        ]);

        $this->assertResponseStatusCodeSame(500);
        $response = $this->client->getResponse();
        $this->assertStringContainsString('invalid-uuid', (string) $response->getContent());
        $this->assertStringContainsString('not found', (string) $response->getContent());
    }

    public function testPostWithoutSnippetUuid(): void
    {
        $this->client->jsonRequest('PUT', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io', []);

        $this->assertResponseStatusCodeSame(500);
        $response = $this->client->getResponse();
        $this->assertStringContainsString('snippetUuid must be a string', (string) $response->getContent());
    }

    public function testPostWithNonStringSnippetUuid(): void
    {
        $this->client->jsonRequest('PUT', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io', [
            'snippetUuid' => 123,
        ]);

        $this->assertResponseStatusCodeSame(500);
        $response = $this->client->getResponse();
        $this->assertStringContainsString('snippetUuid must be a string', (string) $response->getContent());
    }

    public function testPostWithNonExistentAreaKey(): void
    {
        $this->client->jsonRequest('PUT', '/admin/api/snippet-areas/nonexistent?webspaceKey=sulu-io', [
            'snippetUuid' => '01234567-1234-1234-1234-123456789abc',
        ]);

        $this->assertResponseStatusCodeSame(500);
        $response = $this->client->getResponse();
        $this->assertStringContainsString('not found', (string) $response->getContent());
    }

    public function testSnippetAreaParametersIncludeCacheSettings(): void
    {
        $snippetAreas = self::getContainer()->getParameter('sulu_snippet.areas');
        /** @var array<string, array{cache-invalidation: bool}> $snippetAreas */
        $this->assertArrayHasKey('with-cache', $snippetAreas);
        $this->assertArrayHasKey('hotel', $snippetAreas);

        $this->assertTrue($snippetAreas['with-cache']['cache-invalidation'], 'with-cache area should have cache-invalidation = true');
        $this->assertFalse($snippetAreas['hotel']['cache-invalidation'], 'hotel area should have cache-invalidation = false');
        $this->assertFalse($snippetAreas['test']['cache-invalidation'], 'test area should have cache-invalidation = false');
    }

    public function testPutWithoutEditPermission(): void
    {
        $tag1 = self::createTag(['name' => 'Tag 1']);
        $tag2 = self::createTag(['name' => 'Tag 2']);
        self::getEntityManager()->flush();

        $this->client->jsonRequest('POST', '/admin/api/snippets?locale=en&action=publish', [
            'template' => 'snippet',
            'title' => 'Test Snippet',
            'images' => null,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
        ]);

        $response = $this->client->getResponse();
        $responseContent = \json_decode((string) $response->getContent(), true) ?? [];
        /** @var array{id: string} $responseContent */
        $id = $responseContent['id'];

        self::ensureKernelShutdown();

        // Create a client without permissions
        $clientWithoutPermissions = $this->createClient();

        $clientWithoutPermissions->jsonRequest('PUT', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io', [
            'snippetUuid' => (string) $id,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteWithoutEditPermission(): void
    {
        $tag1 = self::createTag(['name' => 'Tag 1']);
        $tag2 = self::createTag(['name' => 'Tag 2']);
        self::getEntityManager()->flush();

        $this->client->jsonRequest('POST', '/admin/api/snippets?locale=en&action=publish', [
            'template' => 'snippet',
            'title' => 'Test Snippet',
            'images' => null,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
        ]);

        $response = $this->client->getResponse();
        $responseContent = \json_decode((string) $response->getContent(), true) ?? [];
        /** @var array{id: string} $responseContent */
        $id = $responseContent['id'];

        $this->client->jsonRequest('PUT', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io', [
            'snippetUuid' => (string) $id,
        ]);

        self::ensureKernelShutdown();

        // Create a client without permissions
        $clientWithoutPermissions = $this->createClient();

        $clientWithoutPermissions->jsonRequest('DELETE', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDelete(): void
    {
        $tag1 = self::createTag(['name' => 'Tag 1']);
        $tag2 = self::createTag(['name' => 'Tag 2']);
        self::getEntityManager()->flush();

        $this->client->jsonRequest('POST', '/admin/api/snippets?locale=en&action=publish', [
            'template' => 'snippet',
            'title' => 'Test Snippet',
            'images' => null,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => [$tag1->getId(), $tag2->getId()],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
        ]);

        $response = $this->client->getResponse();
        $responseContent = \json_decode((string) $response->getContent(), true) ?? [];
        /** @var array{id: string} $responseContent */
        $id = $responseContent['id'];

        $this->client->jsonRequest('PUT', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io', [
            'snippetUuid' => (string) $id,
        ]);
        $this->assertResponseStatusCodeSame(200);

        // Now delete the snippet area assignment
        $this->client->jsonRequest('DELETE', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io');
        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();

        $responseContent = \json_decode((string) $response->getContent(), true) ?? [];
        $this->assertIsArray($responseContent);

        // Verify the snippet area is cleared
        $this->assertArrayHasKey('snippetUuid', $responseContent);
        $this->assertNull($responseContent['snippetUuid']);
        $this->assertArrayHasKey('snippetTitle', $responseContent);
        $this->assertNull($responseContent['snippetTitle']);
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

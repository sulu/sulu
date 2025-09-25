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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
class SnippetAreaControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema($classes, false);
    }

    public function testGetList(): void
    {
        self::purgeDatabase();

        $this->client->jsonRequest('GET', '/admin/api/snippet-areas?webspaceKey=sulu-io');

        $this->assertResponseSnapshot('snippet_area_cget.json', $this->client->getResponse(), 200);

        self::ensureKernelShutdown();
    }

    public function testPost(): void
    {
        self::purgeDatabase();

        $this->client->jsonRequest('POST', '/admin/api/snippets?locale=en&action=publish', [
            'template' => 'snippet',
            'title' => 'Test Snippet',
            'images' => null,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
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

        self::ensureKernelShutdown();
    }

    public function testPostWithInvalidSnippetUuid(): void
    {
        self::purgeDatabase();

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
        self::purgeDatabase();

        $this->client->jsonRequest('PUT', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io', []);

        $this->assertResponseStatusCodeSame(500);
        $response = $this->client->getResponse();
        $this->assertStringContainsString('snippetUuid must be a string', (string) $response->getContent());
    }

    public function testPostWithNonStringSnippetUuid(): void
    {
        self::purgeDatabase();

        $this->client->jsonRequest('PUT', '/admin/api/snippet-areas/hotel?webspaceKey=sulu-io', [
            'snippetUuid' => 123,
        ]);

        $this->assertResponseStatusCodeSame(500);
        $response = $this->client->getResponse();
        $this->assertStringContainsString('snippetUuid must be a string', (string) $response->getContent());
    }

    public function testPostWithNonExistentAreaKey(): void
    {
        self::purgeDatabase();

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

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

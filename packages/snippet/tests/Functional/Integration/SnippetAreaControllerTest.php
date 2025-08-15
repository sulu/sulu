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

        $this->client->jsonRequest('GET', '/admin/api/snippet-areas?webspace=sulu-io');

        $this->assertResponseSnapshot('snippet_area_cget.json', $this->client->getResponse(), 200);

        self::ensureKernelShutdown();
    }

    public function testPost(): void
    {
        self::purgeDatabase();

        // Creating the snippet
        $this->client->jsonRequest('POST', '/admin/api/snippets?locale=en', [
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
        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey($responseContent, 'id');
        $id = $responseContent['id'];

        $this->assertIsString($id, 'Expecting snippet area id to be a string');

        // Setting the snippet into the snippet area
        $this->client->jsonRequest('PUT', '/admin/api/snippet-areas/car', [
            'snippet' => ['uuid' => (string) $id],
            'webspace' => 'sulu-io',
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->client->jsonRequest('GET', '/admin/api/snippet-areas?webspace=sulu-io');
        $this->assertResponseSnapshot('snippet_area_cget_partially_filled.json', $this->client->getResponse(), 200);

        self::ensureKernelShutdown();
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

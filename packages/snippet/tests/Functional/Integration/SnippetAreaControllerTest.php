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
use Sulu\Snippet\Domain\Model\SnippetArea;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
class SnippetAreaControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    private EntityManagerInterface $entityManager;

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

        $this->entityManager = $entityManager;
    }

    public function testGetList(): string
    {
        self::purgeDatabase();

        $this->client->jsonRequest('GET', '/admin/api/snippet-areas/sulu-io');

        $this->assertResponseSnapshot('snippet_area_cget.json', $this->client->getResponse(), 200);

        self::ensureKernelShutdown();
    }

    public function testPut(): void
    {
        self::purgeDatabase();

        $snippetArea = new SnippetArea();
        $snippetArea->setWebspaceKey('sulu-io');
        $snippetArea->setAreaKey('car');

        $this->entityManager->persist($snippetArea);
        $this->entityManager->flush();

        $this->client->jsonRequest('GET', '/admin/api/snippet-areas/sulu-io');

        $this->assertResponseSnapshot('snippet_area_cget.json', $this->client->getResponse(), 200);

        self::ensureKernelShutdown();
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

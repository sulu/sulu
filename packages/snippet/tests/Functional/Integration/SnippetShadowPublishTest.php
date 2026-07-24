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
use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversNothing]
class SnippetShadowPublishTest extends SuluTestCase
{
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
    }

    public function testPublishShadowLocaleSucceeds(): void
    {
        self::purgeDatabase();

        $this->client->request(
            'POST',
            '/admin/api/snippets?locale=en&action=publish',
            [], [], [],
            \json_encode([
                'template' => 'snippet',
                'title' => 'Source EN',
            ]) ?: null,
        );
        self::assertSame(201, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        $this->client->request(
            'PUT',
            '/admin/api/snippets/' . $id . '?locale=de',
            [], [], [],
            \json_encode([
                'template' => 'snippet',
                'title' => 'Source EN',
                'shadowOn' => true,
                'shadowLocale' => 'en',
            ]) ?: null,
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());

        // Publishing the shadow used to fail because the source locale's content was not loaded.
        $this->client->request(
            'PUT',
            '/admin/api/snippets/' . $id . '?locale=de&action=publish',
            [], [], [],
            \json_encode([
                'template' => 'snippet',
                'title' => 'Source EN',
                'shadowOn' => true,
                'shadowLocale' => 'en',
            ]) ?: null,
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());

        $liveDe = $this->getLiveDimensionContent($id, 'de');
        self::assertNotNull($liveDe, 'Expected a published (live) DE dimension content.');
        self::assertSame('en', $liveDe['shadowLocale']);
        self::assertSame('snippet', $liveDe['templateKey']);
        self::assertNotNull($liveDe['workflowPublished']);
        /** @var array<string, mixed> $templateData */
        $templateData = $liveDe['templateData'];
        self::assertSame('Source EN', $templateData['title'] ?? null);
    }

    public function testRepublishingSourceUpdatesLiveShadowDependent(): void
    {
        self::purgeDatabase();

        $this->client->request(
            'POST',
            '/admin/api/snippets?locale=en&action=publish',
            [], [], [],
            \json_encode(['template' => 'snippet', 'title' => 'Source EN']) ?: null,
        );
        self::assertSame(201, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        $shadowData = ['template' => 'snippet', 'title' => 'Source EN', 'shadowOn' => true, 'shadowLocale' => 'en'];
        $this->client->request('PUT', '/admin/api/snippets/' . $id . '?locale=de', [], [], [], \json_encode($shadowData) ?: null);
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        $this->client->request('PUT', '/admin/api/snippets/' . $id . '?locale=de&action=publish', [], [], [], \json_encode($shadowData) ?: null);
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());

        $liveDe = $this->getLiveDimensionContent($id, 'de');
        self::assertNotNull($liveDe);
        /** @var array<string, mixed> $templateData */
        $templateData = $liveDe['templateData'];
        self::assertSame('Source EN', $templateData['title'] ?? null);

        // Republishing the source must update the shadow dependent's live content, not duplicate it.
        $this->client->request(
            'PUT',
            '/admin/api/snippets/' . $id . '?locale=en&action=publish',
            [], [], [],
            \json_encode(['template' => 'snippet', 'title' => 'Source EN Updated']) ?: null,
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());

        $liveDe = $this->getLiveDimensionContent($id, 'de');
        self::assertNotNull($liveDe);
        self::assertSame('en', $liveDe['shadowLocale']);
        /** @var array<string, mixed> $templateData */
        $templateData = $liveDe['templateData'];
        self::assertSame('Source EN Updated', $templateData['title'] ?? null);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getLiveDimensionContent(string $snippetId, string $locale): ?array
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        /** @var array<string, mixed>|null $row */
        $row = $entityManager->createQueryBuilder()
            ->from(SnippetDimensionContent::class, 'dimensionContent')
            ->select(
                'dimensionContent.stage',
                'dimensionContent.locale',
                'dimensionContent.templateKey',
                'dimensionContent.workflowPublished',
                'dimensionContent.shadowLocale',
                'dimensionContent.templateData',
            )
            ->where('IDENTITY(dimensionContent.snippet) = :snippetId')
            ->andWhere('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.locale = :locale')
            ->andWhere('dimensionContent.version = 0')
            ->setParameter('snippetId', $snippetId)
            ->setParameter('stage', 'live')
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getOneOrNullResult();

        return $row;
    }
}

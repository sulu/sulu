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

namespace Sulu\Page\Tests\Functional\Integration;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversNothing]
class PageShadowPublishTest extends SuluTestCase
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

    private function createHomepage(string $uuid, string $webspaceKey): PageInterface
    {
        $homepage = new Page($uuid);
        $homepage->setLft(0);
        $homepage->setRgt(1);
        $homepage->setDepth(0);
        $homepage->setWebspaceKey($webspaceKey);
        self::getEntityManager()->persist($homepage);
        self::getEntityManager()->flush();

        return $homepage;
    }

    public function testPublishShadowLocaleSucceeds(): void
    {
        self::purgeDatabase();
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&action=publish&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [], [], [],
            \json_encode([
                'template' => 'default',
                'title' => 'Source EN',
                'url' => '/source-en',
            ]) ?: null,
        );
        self::assertSame(201, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        $this->client->request(
            'PUT',
            '/admin/api/pages/' . $id . '?locale=de&webspace=sulu-io',
            [], [], [],
            \json_encode([
                'template' => 'default',
                'title' => 'Source EN',
                'url' => '/source-en',
                'shadowOn' => true,
                'shadowLocale' => 'en',
            ]) ?: null,
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());

        // Publishing the shadow used to fail because the source locale's content was not loaded.
        $this->client->request(
            'PUT',
            '/admin/api/pages/' . $id . '?locale=de&action=publish&webspace=sulu-io',
            [], [], [],
            \json_encode([
                'template' => 'default',
                'title' => 'Source EN',
                'url' => '/source-en',
                'shadowOn' => true,
                'shadowLocale' => 'en',
            ]) ?: null,
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());

        $liveDe = $this->getLiveDimensionContent($id, 'de');
        self::assertNotNull($liveDe, 'Expected a published (live) DE dimension content.');
        self::assertSame('en', $liveDe['shadowLocale']);
        self::assertSame('default', $liveDe['templateKey']);
        self::assertNotNull($liveDe['workflowPublished']);
        /** @var array<string, mixed> $templateData */
        $templateData = $liveDe['templateData'];
        self::assertSame('Source EN', $templateData['title'] ?? null);
    }

    public function testRepublishingSourceUpdatesLiveShadowDependent(): void
    {
        self::purgeDatabase();
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&action=publish&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [], [], [],
            \json_encode(['template' => 'default', 'title' => 'Source EN', 'url' => '/source-en']) ?: null,
        );
        self::assertSame(201, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        $shadowData = ['template' => 'default', 'title' => 'Source EN', 'url' => '/source-en', 'shadowOn' => true, 'shadowLocale' => 'en'];
        $this->client->request('PUT', '/admin/api/pages/' . $id . '?locale=de&webspace=sulu-io', [], [], [], \json_encode($shadowData) ?: null);
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        $this->client->request('PUT', '/admin/api/pages/' . $id . '?locale=de&action=publish&webspace=sulu-io', [], [], [], \json_encode($shadowData) ?: null);
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());

        $liveDe = $this->getLiveDimensionContent($id, 'de');
        self::assertNotNull($liveDe);
        /** @var array<string, mixed> $templateData */
        $templateData = $liveDe['templateData'];
        self::assertSame('Source EN', $templateData['title'] ?? null);

        // Republishing the source must update the shadow dependent's live content, not duplicate it.
        $this->client->request(
            'PUT',
            '/admin/api/pages/' . $id . '?locale=en&action=publish&webspace=sulu-io',
            [], [], [],
            \json_encode(['template' => 'default', 'title' => 'Source EN Updated', 'url' => '/source-en']) ?: null,
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
    private function getLiveDimensionContent(string $pageId, string $locale): ?array
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        /** @var array<string, mixed>|null $row */
        $row = $entityManager->createQueryBuilder()
            ->from(PageDimensionContent::class, 'dimensionContent')
            ->select(
                'dimensionContent.stage',
                'dimensionContent.locale',
                'dimensionContent.templateKey',
                'dimensionContent.workflowPublished',
                'dimensionContent.shadowLocale',
                'dimensionContent.templateData',
            )
            ->where('IDENTITY(dimensionContent.page) = :pageId')
            ->andWhere('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.locale = :locale')
            ->andWhere('dimensionContent.version = 0')
            ->setParameter('pageId', $pageId)
            ->setParameter('stage', 'live')
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getOneOrNullResult();

        return $row;
    }
}

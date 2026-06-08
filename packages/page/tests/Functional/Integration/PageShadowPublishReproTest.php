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

/**
 * Regression test for https://github.com/sulu/sulu/issues/8883
 * "Shadow Locale Fails to Publish".
 *
 * Publishing a shadow locale must copy the live content of its source locale. The workflow
 * handler therefore has to load the source locale's dimension contents, not only the locale
 * being published.
 */
#[CoversNothing]
class PageShadowPublishReproTest extends SuluTestCase
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

        // 1. Create and publish the source page in EN.
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

        // 2. Save DE as a shadow of EN.
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

        // 3. Publish the DE shadow locale - this used to fail with
        //    "TypedFormMetadata::getDefaultType() null returned" and then
        //    "Expected \"published\" to be set in the data array.".
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

        // The DE live dimension content exists, shadows EN and carries EN's published template/content.
        $liveDe = $this->getLiveDimensionContent($id, 'de');
        self::assertNotNull($liveDe, 'Expected a published (live) DE dimension content.');
        self::assertSame('en', $liveDe['shadowLocale']);
        self::assertSame('default', $liveDe['templateKey']);
        self::assertNotNull($liveDe['workflowPublished']);
        self::assertSame('Source EN', $liveDe['templateData']['title'] ?? null);
    }

    /**
     * Republishing a source locale must update (not duplicate) the live content of locales shadowing it.
     */
    public function testRepublishingSourceUpdatesLiveShadowDependent(): void
    {
        self::purgeDatabase();
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        // Create and publish EN.
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

        // Save DE as a shadow of EN and publish it.
        $shadowData = ['template' => 'default', 'title' => 'Source EN', 'url' => '/source-en', 'shadowOn' => true, 'shadowLocale' => 'en'];
        $this->client->request('PUT', '/admin/api/pages/' . $id . '?locale=de&webspace=sulu-io', [], [], [], \json_encode($shadowData) ?: null);
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        $this->client->request('PUT', '/admin/api/pages/' . $id . '?locale=de&action=publish&webspace=sulu-io', [], [], [], \json_encode($shadowData) ?: null);
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());

        self::assertSame('Source EN', $this->getLiveDimensionContent($id, 'de')['templateData']['title'] ?? null);

        // Republish EN with new content; the DE shadow dependent's live content must update too.
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
        self::assertSame('Source EN Updated', $liveDe['templateData']['title'] ?? null);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getLiveDimensionContent(string $pageId, string $locale): ?array
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        /** @var array<string, mixed>|null $row */
        $row = $em->createQueryBuilder()
            ->from(PageDimensionContent::class, 'd')
            ->select('d.stage', 'd.locale', 'd.templateKey', 'd.workflowPublished', 'd.shadowLocale', 'd.templateData')
            ->where('IDENTITY(d.page) = :id')
            ->andWhere('d.stage = :stage')
            ->andWhere('d.locale = :locale')
            ->andWhere('d.version = 0')
            ->setParameter('id', $pageId)
            ->setParameter('stage', 'live')
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getOneOrNullResult();

        return $row;
    }
}

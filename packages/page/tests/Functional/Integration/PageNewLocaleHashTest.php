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

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Saving a brand-new locale (a ghost of an existing one) must not fail with an optimistic-locking
 * conflict. The form loads the new locale as a ghost and receives an "_hash" generated from the
 * ghost source's content; that hash can never match the (non-existent) target locale, so the hash
 * check must be skipped when the targeted locale has no content of its own yet.
 */
#[CoversNothing]
class PageNewLocaleHashTest extends SuluTestCase
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

    public function testSaveNewGhostLocaleWithHashSucceeds(): void
    {
        self::purgeDatabase();
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        // 1. Create the page in EN.
        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [], [], [],
            \json_encode(['template' => 'default', 'title' => 'Source EN', 'url' => '/source-en']) ?: null,
        );
        self::assertSame(201, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        // 2. Open DE - a ghost of EN. The response carries an "_hash" built from the ghost source.
        $this->client->request('GET', '/admin/api/pages/' . $id . '?locale=de&webspace=sulu-io');
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        /** @var array{_hash?: string, ghostLocale?: string} $ghost */
        $ghost = \json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('en', $ghost['ghostLocale'] ?? null, 'DE is expected to be a ghost of EN.');
        $hash = $ghost['_hash'] ?? null;
        self::assertIsString($hash, 'The ghost response must carry an "_hash".');

        // 3. Save DE for the first time, sending back the ghost hash. This used to fail with
        //    409 "InvalidHashException" because DE has no own content to lock against yet.
        $this->client->request(
            'PUT',
            '/admin/api/pages/' . $id . '?locale=de&action=draft&webspace=sulu-io',
            [], [], [],
            \json_encode([
                '_hash' => $hash,
                'template' => 'default',
                'title' => 'No copy DE',
                'url' => '/no-copy-de',
            ]) ?: null,
        );

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        /** @var array{title?: string, availableLocales?: array<int, string>} $saved */
        $saved = \json_decode((string) $response->getContent(), true);
        self::assertSame('No copy DE', $saved['title'] ?? null);
        self::assertContains('de', $saved['availableLocales'] ?? []);
    }
}

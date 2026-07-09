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
 * Loading a not-yet-translated ("ghost") page locale must return the webspace's default template
 * instead of null, so the admin form is pre-filled and sends a valid template on save. This mirrors
 * the Sulu 2.x read behaviour and is provided by the DefaultTemplateNormalizer.
 */
#[CoversNothing]
class PageDefaultTemplateTest extends SuluTestCase
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

    public function testGhostLocaleReturnsWebspaceDefaultTemplate(): void
    {
        self::purgeDatabase();
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        // 1. Create the page in EN with the "default" template.
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

        // 2. Open DE - a ghost of EN that has no own content yet.
        $this->client->request('GET', '/admin/api/pages/' . $id . '?locale=de&webspace=sulu-io');
        self::assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());

        /** @var array{template?: string|null, ghostLocale?: string|null} $data */
        $data = \json_decode((string) $this->client->getResponse()->getContent(), true);

        // It is a ghost of EN and carries the webspace's default page template ("default" for sulu-io),
        // instead of the null it would otherwise have for a not-yet-translated locale.
        self::assertSame('en', $data['ghostLocale'] ?? null, 'DE is expected to be a ghost of EN.');
        self::assertSame('default', $data['template'] ?? null, 'Expected the webspace default template, not null.');
    }
}

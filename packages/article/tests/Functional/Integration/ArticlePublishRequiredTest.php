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

namespace Sulu\Article\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * An article can be drafted before it has a parent page, but not published.
 */
#[CoversNothing]
class ArticlePublishRequiredTest extends SuluTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        self::purgeDatabase();
    }

    public function testDraftSavesWithoutAParentPage(): void
    {
        $this->client->request('POST', '/admin/api/articles?locale=en', [], [], [], $this->payload());

        $this->assertSame(201, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
    }

    public function testPublishWithoutAParentPageIsRefusedAndNamesTheRoute(): void
    {
        $this->client->request('POST', '/admin/api/articles?locale=en', [], [], [], $this->payload());

        /** @var array{id: string} $created */
        $created = \json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->client->request('PUT', \sprintf('/admin/api/articles/%s?locale=en&action=publish', $created['id']), [], [], [], $this->payload());

        $response = $this->client->getResponse();
        $this->assertSame(422, $response->getStatusCode(), (string) $response->getContent());

        /** @var array{preValidationResults: list<array{key: string, passed: bool, messages: list<array{parameters: array<string, string>}>}>} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame('publish_required', $content['preValidationResults'][0]['key']);
        $this->assertSame(['fields' => 'url'], $content['preValidationResults'][0]['messages'][0]['parameters']);
    }

    private function payload(): string
    {
        return (string) \json_encode([
            'template' => 'publish-required-parent',
            'title' => 'Article without parent',
            'url' => ['page' => [], 'suffix' => '/article-without-parent'],
            'mainWebspace' => 'sulu-io',
        ]);
    }
}

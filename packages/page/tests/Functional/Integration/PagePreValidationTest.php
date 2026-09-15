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
 * The controller persists the form before it applies the action, so a pre-validator that refuses the
 * transition leaves the content written. These tests pin what the admin needs to recover from that:
 * the content really is saved, and a refused create still hands back the id it created.
 */
#[CoversNothing]
class PagePreValidationTest extends SuluTestCase
{
    private const TEMPLATE = 'review-seo';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        self::purgeDatabase();
    }

    public function testCreateRefusedByPreValidationReturnsTheCreatedId(): void
    {
        $homepage = $this->createHomepage();

        $this->client->request(
            'POST',
            \sprintf(
                '/admin/api/pages?locale=en&webspace=sulu-io&parentId=%s&action=request_for_review',
                $homepage->getId(),
            ),
            [],
            [],
            [],
            $this->payload('Created Without Seo'),
        );

        $response = $this->client->getResponse();
        $this->assertSame(422, $response->getStatusCode(), (string) $response->getContent());

        /** @var array{id?: string, preValidationResults?: array<mixed>} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('preValidationResults', $content);
        $this->assertArrayHasKey(
            'id',
            $content,
            'Without the id the admin cannot switch to the edit view and would create a second page.',
        );

        $this->client->request('GET', \sprintf('/admin/api/pages/%s?locale=en', $content['id']));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{title: string} $page */
        $page = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Created Without Seo', $page['title']);
    }

    public function testSaveRefusedByPreValidationStillPersistsTheDraft(): void
    {
        $id = $this->createDraft();

        $this->client->request(
            'PUT',
            \sprintf('/admin/api/pages/%s?locale=en&webspace=sulu-io&action=request_for_review', $id),
            [],
            [],
            [],
            $this->payload('Edited Without Seo'),
        );

        $this->assertSame(422, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', \sprintf('/admin/api/pages/%s?locale=en', $id));

        /** @var array{title: string, workflowPlace: string} $page */
        $page = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame(
            'Edited Without Seo',
            $page['title'],
            'The save runs before the transition, so a refused transition leaves the edit written.',
        );
        $this->assertSame('unpublished', $page['workflowPlace']);
    }

    private function createDraft(): string
    {
        $homepage = $this->createHomepage();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&webspace=sulu-io&parentId=%s', $homepage->getId()),
            [],
            [],
            [],
            $this->payload('Draft Without Seo'),
        );
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);

        return $content['id'];
    }

    private function payload(string $title): string
    {
        return (string) \json_encode([
            'template' => self::TEMPLATE,
            'title' => $title,
            'url' => '/page-without-seo',
        ]);
    }

    private function createHomepage(): PageInterface
    {
        $homepage = new Page('0199ee04-c220-784e-a6fa-ac985870f2d5');
        $homepage->setLft(0);
        $homepage->setRgt(1);
        $homepage->setDepth(0);
        $homepage->setWebspaceKey('sulu-io');
        self::getEntityManager()->persist($homepage);
        self::getEntityManager()->flush();

        return $homepage;
    }
}

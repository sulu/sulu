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
 * The template has no request workflow, so only the publish required check stands between the
 * draft and the live page.
 */
#[CoversNothing]
class PagePublishRequiredTest extends SuluTestCase
{
    private const TEMPLATE = 'publish-required';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        self::purgeDatabase();
    }

    public function testDraftSavesWithoutThePropertyRequiredToPublish(): void
    {
        $this->createDraft();

        $this->assertSame(201, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
    }

    public function testPublishWithoutThePropertyIsRefusedAndNamesIt(): void
    {
        $id = $this->createDraft();

        $this->client->request(
            'PUT',
            \sprintf('/admin/api/pages/%s?locale=en&webspace=sulu-io&action=publish', $id),
            [],
            [],
            [],
            $this->payload(null),
        );

        $response = $this->client->getResponse();
        $this->assertSame(422, $response->getStatusCode(), (string) $response->getContent());

        /** @var array{preValidationResults: list<array{key: string, passed: bool, messages: list<array{parameters: array<string, string>}>}>} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertCount(1, $content['preValidationResults']);
        $this->assertSame('publish_required', $content['preValidationResults'][0]['key']);
        $this->assertFalse($content['preValidationResults'][0]['passed']);
        $this->assertSame(['fields' => 'description'], $content['preValidationResults'][0]['messages'][0]['parameters']);

        $this->client->request('GET', \sprintf('/admin/api/pages/%s?locale=en', $id));

        /** @var array{workflowPlace: string} $page */
        $page = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('unpublished', $page['workflowPlace']);
    }

    public function testPublishWithTheProperty(): void
    {
        $id = $this->createDraft();

        $this->client->request(
            'PUT',
            \sprintf('/admin/api/pages/%s?locale=en&webspace=sulu-io&action=publish', $id),
            [],
            [],
            [],
            $this->payload('Now described'),
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', \sprintf('/admin/api/pages/%s?locale=en', $id));

        /** @var array{workflowPlace: string} $page */
        $page = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('published', $page['workflowPlace']);
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
            $this->payload(null),
        );

        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);

        return $content['id'];
    }

    private function payload(?string $description): string
    {
        return (string) \json_encode([
            'template' => self::TEMPLATE,
            'title' => 'Publish required',
            'url' => '/publish-required',
            'description' => $description,
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

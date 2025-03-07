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

use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
class PageControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    /**
     * @var KernelBrowser
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );
    }

    private function createHomepage(): PageInterface
    {
        $homepage = new Page('123-123-123');
        $homepage->setLft(0);
        $homepage->setRgt(1);
        $homepage->setDepth(0);
        $homepage->setWebspaceKey('sulu-io');
        self::getEntityManager()->persist($homepage);
        self::getEntityManager()->flush();

        return $homepage;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createPage(
        string $parentId,
        array $data = []
    ): PageInterface {
        $data = \array_merge(
            [
                'title' => 'Test Page',
                'locale' => 'en',
                'url' => '/test-page-' . \uniqid(),
                'template' => 'default',
            ],
            $data
        );
        $message = new CreatePageMessage('sulu-io', $parentId, $data);

        /** @var CreatePageMessageHandler $messageHandler */
        $messageHandler = self::getContainer()->get('sulu_page.create_page_handler');
        $page = $messageHandler->__invoke($message);
        self::getEntityManager()->flush();

        return $page;
    }

    public function testPostPublish(): string
    {
        self::purgeDatabase();
        self::initPhpcr();

        $homepage = $this->createHomepage();
        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&action=publish&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [],
            [],
            [],
            \json_encode(
                [
                    'template' => 'default',
                    'title' => 'Test Page',
                    'url' => '/my-page',
                    'published' => '2020-05-08T00:00:00+00:00', // Should be ignored
                    'description' => null,
                    'image' => null,
                    'lastModified' => '2022-05-08T00:00:00+00:00',
                    'lastModifiedEnabled' => true,
                    'seoTitle' => 'Seo Title',
                    'seoDescription' => 'Seo Description',
                    'seoCanonicalUrl' => 'https://sulu.io/',
                    'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
                    'seoNoIndex' => true,
                    'seoNoFollow' => true,
                    'seoHideInSitemap' => true,
                    'excerptTitle' => 'Excerpt Title',
                    'excerptDescription' => 'Excerpt Description',
                    'excerptMore' => 'Excerpt More',
                    'excerptTags' => ['Tag 1', 'Tag 2'],
                    'excerptCategories' => [],
                    'excerptIcon' => null,
                    'excerptMedia' => null,
                    'author' => null,
                    'authored' => '2020-05-08T00:00:00+00:00',
                    'mainWebspace' => 'sulu-io',
                    'navigationContexts' => ['main'],
                ]
            ) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var string $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $this->assertResponseSnapshot('page_post_publish.json', $response, 201);
        $this->assertNotSame('2020-05-08T00:00:00+00:00', $content['published']); // @phpstan-ignore-line

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/en/my-page');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Test Page', $content);

        return $id;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPostPublish')]
    public function testPostTriggerUnpublish(string $id): void
    {
        $this->client->request('POST', '/admin/api/pages/' . $id . '?locale=en&action=unpublish');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_post_trigger_unpublish.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/en/my-page');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    public function testPost(): string
    {
        self::purgeDatabase();

        $homepage = $this->createHomepage();
        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [],
            [],
            [],
            \json_encode([
                'template' => 'default',
                'title' => 'Test Page',
                'url' => '/my-page',
                'images' => null,
                'lastModified' => '2022-05-08T00:00:00+00:00',
                'lastModifiedEnabled' => true,
                'seoTitle' => 'Seo Title',
                'seoDescription' => 'Seo Description',
                'seoCanonicalUrl' => 'https://sulu.io/',
                'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
                'seoNoIndex' => true,
                'seoNoFollow' => true,
                'seoHideInSitemap' => true,
                'excerptTitle' => 'Excerpt Title',
                'excerptDescription' => 'Excerpt Description',
                'excerptMore' => 'Excerpt More',
                'excerptTags' => ['Tag 1', 'Tag 2'],
                'excerptCategories' => [],
                'excerptIcon' => null,
                'excerptMedia' => null,
                'mainWebspace' => 'sulu-io',
                'authored' => '2020-05-08T00:00:00+00:00',
            ]) ?: null);

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_post.json', $response, 201);

        $routeRepository = $this->getContainer()->get('sulu.repository.route');
        $this->assertCount(0, $routeRepository->findAll());

        /** @var string $id */
        $id = \json_decode((string) $response->getContent(), true)['id'] ?? null; // @phpstan-ignore-line

        return $id;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPost')]
    public function testGet(string $id): void
    {
        $this->client->request('GET', '/admin/api/pages/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_get.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/en/my-page');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPost')]
    public function testGetGhostLocale(string $id): void
    {
        $this->client->request('GET', '/admin/api/pages/' . $id . '?locale=de');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_get_ghost_locale.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/de/my-page');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPost')]
    public function testPostTriggerCopyLocale(string $id): void
    {
        $this->client->request('POST', '/admin/api/pages/' . $id . '?locale=de&action=copy-locale&src=en&dest=de');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_post_trigger_copy_locale.json', $response, 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPost')]
    #[\PHPUnit\Framework\Attributes\Depends('testGet')]
    public function testPut(string $id): void
    {
        $this->client->request('PUT', '/admin/api/pages/' . $id . '?locale=en', [], [], [], \json_encode([
            'template' => 'default',
            'title' => 'Test Page 2',
            'url' => '/my-page-2',
            'description' => '<p>Test Page 2</p>',
            'seoTitle' => 'Seo Title 2',
            'seoDescription' => 'Seo Description 2',
            'seoCanonicalUrl' => 'https://sulu.io/2',
            'seoKeywords' => 'Seo Keyword 3, Seo Keyword 4',
            'seoNoIndex' => false,
            'seoNoFollow' => false,
            'seoHideInSitemap' => false,
            'excerptTitle' => 'Excerpt Title 2',
            'excerptDescription' => 'Excerpt Description 2',
            'excerptMore' => 'Excerpt More 2',
            'excerptTags' => ['Tag 3', 'Tag 4'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'authored' => '2020-06-09T00:00:00+00:00',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();

        $routeRepository = $this->getContainer()->get('sulu.repository.route');
        $this->assertCount(0, $routeRepository->findAll());

        $this->assertResponseSnapshot('page_put.json', $response, 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPost')]
    #[\PHPUnit\Framework\Attributes\Depends('testPut')]
    public function testGetList(string $id): void
    {
        $this->client->request('GET', '/admin/api/pages?locale=en&webspace=sulu-io&expandedIds=' . $id);
        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_cget.json', $response, 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPost')]
    #[\PHPUnit\Framework\Attributes\Depends('testGetList')]
    public function testOrderPages(string $id): void
    {
        $page1 = $this->createPage($id);
        $page2 = $this->createPage($id);
        $page3 = $this->createPage($id);

        $this->client->request('GET', '/admin/api/pages?locale=en&webspace=sulu-io&expandedIds=' . $id);
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_post_before_order_list.json', $response, 200);

        $this->client->request(
            method: 'POST',
            uri: \sprintf('/admin/api/pages/%s?webspace=sulu-io&locale=en&action=order', $page1->getId()),
            content: (string) \json_encode(['position' => 3]),
        );
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_post_order.json', $response, 200);

        // test if the order is still correct
        $this->client->request('GET', '/admin/api/pages?locale=en&webspace=sulu-io&expandedIds=' . $id);
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_post_before_order_list.json', $response, 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPost')]
    #[\PHPUnit\Framework\Attributes\Depends('testGetList')]
    public function testDelete(string $id): void
    {
        $this->client->request('DELETE', '/admin/api/pages/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $routeRepository = $this->getContainer()->get('sulu.repository.route');
        $this->assertCount(0, $routeRepository->findAll());
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

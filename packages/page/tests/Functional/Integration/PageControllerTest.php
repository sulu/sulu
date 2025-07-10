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
use PHPUnit\Framework\Attributes\Depends;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Application\MessageHandler\ModifyPageMessageHandler;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
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

    /**
     * @param array<string, mixed> $data
     */
    private function createPage(
        string $parentId,
        array $data = [],
    ): PageInterface {
        $data = \array_merge(
            [
                'title' => 'Test Page',
                'locale' => 'en',
                'url' => '/test-page-' . \uniqid(),
                'template' => 'default',
            ],
            $data,
        );
        $message = new CreatePageMessage('sulu-io', $parentId, $data);

        /** @var CreatePageMessageHandler $messageHandler */
        $messageHandler = self::getContainer()->get('sulu_page.create_page_handler');
        $page = $messageHandler->__invoke($message);
        self::getEntityManager()->flush();

        return $page;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function modifyPage(
        string $id,
        array $data = [],
    ): PageInterface {
        $data = \array_merge(
            [
                'title' => 'Test Page',
                'locale' => 'en',
                'url' => '/test-page-' . \uniqid(),
                'template' => 'default',
            ],
            $data,
        );

        $message = new ModifyPageMessage(['uuid' => $id], $data);

        /** @var ModifyPageMessageHandler $messageHandler */
        $messageHandler = self::getContainer()->get('sulu_page.modify_page_handler');
        $page = $messageHandler->__invoke($message);
        self::getEntityManager()->flush();

        return $page;
    }

    public function testPostPublish(): string
    {
        self::purgeDatabase();

        $homepage = $this->createHomepage('123-123-123', 'sulu-io');
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
                    'navigationContexts' => ['main'],
                ],
            ) ?: null,
        );

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

    #[Depends('testPostPublish')]
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

        $homepage = $this->createHomepage('123-123-123', 'sulu-io');
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
                'authored' => '2020-05-08T00:00:00+00:00',
            ]) ?: null,
        );

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_post.json', $response, 201);

        $routeRepository = $this->getContainer()->get('sulu.repository.route');
        $this->assertCount(0, $routeRepository->findAll());

        /** @var string $id */
        $id = \json_decode((string) $response->getContent(), true)['id'] ?? null; // @phpstan-ignore-line

        return $id;
    }

    #[Depends('testPost')]
    public function testPostPublishBlogWebspace(): void
    {
        $homepage = $this->createHomepage('321-321-321', 'blog');
        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&action=publish&parentId=%s&webspace=blog', $homepage->getId()),
            [],
            [],
            [],
            \json_encode(
                [
                    'template' => 'default',
                    'title' => 'Test Blog',
                    'url' => '/my-blog',
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
                    'navigationContexts' => ['main'],
                ],
            ) ?: null,
        );

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var string $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $this->assertResponseSnapshot('page_post_publish_blog.json', $response, 201);
        $this->assertNotSame('2020-05-08T00:00:00+00:00', $content['published']); // @phpstan-ignore-line

        self::ensureKernelShutdown();

        // TODO enable this when the routing for other webspaces is working
        //        $websiteClient = $this->createWebsiteClient();
        //        $websiteClient->request('GET', '/en/my-blog');
        //
        //        $response = $websiteClient->getResponse();
        //        $this->assertHttpStatusCode(200, $response);
        //        $content = $response->getContent();
        //        $this->assertIsString($content);
        //        $this->assertStringContainsString('Test Blog', $content);
    }

    #[Depends('testPost')]
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

    #[Depends('testPost')]
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

    #[Depends('testPost')]
    public function testPostTriggerCopyLocale(string $id): void
    {
        $this->client->request('POST', '/admin/api/pages/' . $id . '?locale=de&action=copy-locale&src=en&dest=de');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_post_trigger_copy_locale.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testGet')]
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
        ]) ?: null);

        $response = $this->client->getResponse();

        $routeRepository = $this->getContainer()->get('sulu.repository.route');
        $this->assertCount(0, $routeRepository->findAll());

        $this->assertResponseSnapshot('page_put.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testPut')]
    public function testGetList(string $id): void
    {
        $this->client->request('GET', '/admin/api/pages?locale=en&webspace=sulu-io&expandedIds=' . $id);
        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_cget.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testGetList')]
    public function testOrderPages(string $id): string
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

        return $id;
    }

    #[Depends('testOrderPages')]
    public function testGetListIncludeGhostShadow(string $id): string
    {
        $shadowPage = $this->createPage($id, [
            'title' => 'Test Page Shadow EN',
        ]);

        $this->modifyPage($shadowPage->getUuid(), [
            'locale' => 'de',
            'shadowOn' => true,
            'shadowLocale' => 'en',
        ]);

        $this->client->request('GET', '/admin/api/pages?locale=de&webspace=sulu-io&expandedIds=' . $id . '&exclude-ghosts=false&exclude-shadows=false');
        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_cget_ghost_shadow.json', $response, 200);

        return $id;
    }

    #[Depends('testGetListIncludeGhostShadow')]
    public function testGetListExcludeGhostShadow(string $id): void
    {
        $this->client->request('GET', '/admin/api/pages?locale=de&webspace=sulu-io&expandedIds=' . $id . '&exclude-ghosts=true&exclude-shadows=true');
        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_cget_exclude_ghost_shadow.json', $response, 200);
    }

    #[Depends('testGetListIncludeGhostShadow')]
    public function testCopy(): string
    {
        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&action=publish&parentId=%s&webspace=sulu-io', '123-123-123'),
            [], [], [],
            \json_encode(
                [
                    'template' => 'default',
                    'title' => 'Test page for copy',
                    'url' => '/test-page-for-copy',
                    'navigationContexts' => ['main'],
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
                    'authored' => '2020-05-08T00:00:00+00:00',
                ],
            ) ?: null,
        );

        $response = $this->client->getResponse();
        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var string $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $this->client->request('POST', \sprintf('/admin/api/pages/%s?locale=en&action=copy&destination=%s', $id, $id));
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_post_copy.json', $response);

        $this->client->request('GET', '/admin/api/pages?locale=en&webspace=sulu-io&expandedIds=' . $id);
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_cget_after_copy.json', $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var string $copiedPageId */
        $copiedPageId = $content['_embedded']['pages'][0]['_embedded']['pages'][1]['_embedded']['pages'][0]['id']; // @phpstan-ignore-line

        return $copiedPageId;
    }

    #[Depends('testCopy')]
    public function testMove(string $id): void
    {
        $this->client->request('POST', '/admin/api/pages/' . $id . '?locale=en&action=move&destination=123-123-123');

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_post_move.json', $response);

        $this->client->request('GET', '/admin/api/pages?locale=en&webspace=sulu-io&expandedIds=' . $id);
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_cget_after_move.json', $response);
    }

    #[Depends('testPost')]
    #[Depends('testOrderPages')]
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

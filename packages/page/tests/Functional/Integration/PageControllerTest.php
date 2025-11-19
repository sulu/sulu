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
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Application\MessageHandler\ModifyPageMessageHandler;
use Sulu\Page\Domain\Exception\RemovePageDependantResourcesFoundException;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
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
     * @return int[]
     */
    private function createTargetGroups(): array
    {
        /** @var array<string, mixed> $bundles */
        $bundles = self::getContainer()->getParameter('kernel.bundles');
        // Only create target groups if the bundle is available
        if (!\array_key_exists('SuluAudienceTargetingBundle', $bundles)) {
            return [];
        }

        $targetGroup1 = new TargetGroup();
        $targetGroup1->setTitle('Marketing Segment');
        $targetGroup1->setPriority(1);
        self::getEntityManager()->persist($targetGroup1);

        $targetGroup2 = new TargetGroup();
        $targetGroup2->setTitle('Tech Segment');
        $targetGroup2->setPriority(2);
        self::getEntityManager()->persist($targetGroup2);

        self::getEntityManager()->flush();

        return [$targetGroup1->getId(), $targetGroup2->getId()];
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

    public function testInvalidIdGet(): void
    {
        $this->client->request('GET', '/admin/api/pages/invalid-id?locale=en');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(404, $response);
    }

    public function testPostPublish(): string
    {
        self::purgeDatabase();

        $targetGroupIds = $this->createTargetGroups();
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        $excerptData = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'excerptSegment' => 'premium-segment',
        ];

        // Add audience target groups if available
        if (!empty($targetGroupIds)) {
            $excerptData['excerptAudienceTargetGroups'] = $targetGroupIds;
        }

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&action=publish&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [],
            [],
            [],
            \json_encode(
                \array_merge([
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
                    'author' => null,
                    'authored' => '2020-05-08T00:00:00+00:00',
                    'navigationContexts' => ['main'],
                ], $excerptData),
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
        $websiteClient->request('GET', 'http://sulu.io/en/my-page');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Test Page', $content);

        return $id;
    }

    #[Depends('testPostPublish')]
    public function testVersionListAfterPublish(string $id): string
    {
        $this->client->request('GET', '/admin/api/pages/' . $id . '/versions?page=1&locale=en&webspace=sulu-io&fields=title,version,changer,id');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_get_versions.json', $response, 200);

        return $id;
    }

    #[Depends('testVersionListAfterPublish')]
    public function testVersionListAfterPostModifyAndPublish(string $id): string
    {
        \sleep(1); // Ensure that the version timestamp is different from the previous version

        $targetGroupIds = $this->createTargetGroups();

        $excerptData = [
            'excerptTitle' => 'Modified Excerpt Title',
            'excerptDescription' => 'Modified Excerpt Description',
            'excerptMore' => 'Modified Excerpt More',
            'excerptTags' => ['Modified Tag 1', 'Modified Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'excerptSegment' => 'enterprise-segment',
        ];

        // Add audience target groups if available
        if (!empty($targetGroupIds)) {
            $excerptData['excerptAudienceTargetGroups'] = $targetGroupIds;
        }

        $this->client->request(
            'PUT',
            '/admin/api/pages/' . $id . '?locale=en&action=publish',
            [],
            [],
            [],
            \json_encode(
                \array_merge([
                    'template' => 'default',
                    'title' => 'Test modified version page',
                    'url' => '/my-page',
                    'description' => 'modified version',
                    'image' => null,
                    'seoTitle' => 'Modified Seo Title',
                    'seoDescription' => 'Modified Seo Description',
                    'seoCanonicalUrl' => 'https://modified-sulu.io/',
                    'seoKeywords' => 'Modified Seo Keyword 1, Modified Seo Keyword 2',
                    'seoNoIndex' => true,
                    'seoNoFollow' => true,
                    'seoHideInSitemap' => true,
                    'navigationContexts' => ['main'],
                ], $excerptData),
            ) ?: null,
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->client->request('GET', '/admin/api/pages/' . $id . '/versions?page=1&locale=en&webspace=sulu-io&fields=title,version,changer,id');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_get_versions_after_modify_and_publish.json', $response, 200);
        $content = \json_decode((string) $response->getContent(), true);

        /** @var string $version */
        $version = $content['_embedded']['pages_versions'][1]['version'] ?? null; // @phpstan-ignore-line
        $this->assertNotEmpty($version, 'Version should not be empty after publish');

        return $id . '::' . $version;
    }

    #[Depends('testVersionListAfterPostModifyAndPublish')]
    public function testRestoreVersion(string $idVersion): string
    {
        [$id, $version] = \explode('::', $idVersion, 2);

        $this->client->request('POST', '/admin/api/pages/' . $id . '?locale=en&action=restore&version=' . $version);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $this->client->request('GET', '/admin/api/pages/' . $id . '?locale=en&webspace=sulu-io');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_get_after_restore.json', $response, 200);

        return $id;
    }

    #[Depends('testRestoreVersion')]
    public function testPostTriggerUnpublish(string $id): void
    {
        $this->client->request('POST', '/admin/api/pages/' . $id . '?locale=en&action=unpublish');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_post_trigger_unpublish.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', 'http://sulu.io/en/my-page');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    public function testPost(): string
    {
        self::purgeDatabase();

        $targetGroupIds = $this->createTargetGroups();
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        $excerptData = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'excerptSegment' => 'standard-segment',
        ];

        // Add audience target groups if available
        if (!empty($targetGroupIds)) {
            $excerptData['excerptAudienceTargetGroups'] = $targetGroupIds;
        }

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [],
            [],
            [],
            \json_encode(\array_merge([
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
                'authored' => '2020-05-08T00:00:00+00:00',
            ], $excerptData)) ?: null,
        );

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_post.json', $response, 201);

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(1, $routeRepository->findBy([]));

        /** @var string $id */
        $id = \json_decode((string) $response->getContent(), true)['id'] ?? null; // @phpstan-ignore-line

        return $id;
    }

    #[Depends('testPost')]
    public function testPostPublishBlogWebspace(): void
    {
        $targetGroupIds = $this->createTargetGroups();
        $homepage = $this->createHomepage('0199ee13-6eae-7e0e-b559-ed579da52916', 'blog');

        $excerptData = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'excerptSegment' => 'blog-segment',
        ];

        // Add audience target groups if available
        if (!empty($targetGroupIds)) {
            $excerptData['excerptAudienceTargetGroups'] = $targetGroupIds;
        }

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&action=publish&parentId=%s&webspace=blog', $homepage->getId()),
            [],
            [],
            [],
            \json_encode(
                \array_merge([
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
                    'author' => null,
                    'authored' => '2020-05-08T00:00:00+00:00',
                    'navigationContexts' => ['main'],
                ], $excerptData),
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
        $websiteClient->request('GET', 'http://sulu.io/en/my-page');

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
        $websiteClient->request('GET', 'http://sulu.io/de/my-page');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    #[Depends('testPost')]
    public function testPostTriggerCopyLocale(string $id): void
    {
        $this->client->request('POST', '/admin/api/pages/' . $id . '?locale=de&action=copy_locale&src=en&dest=de');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('page_post_trigger_copy_locale.json', $response, 200);
    }

    #[Depends('testPost')]
    #[Depends('testGet')]
    public function testPut(string $id): void
    {
        $targetGroupIds = $this->createTargetGroups();

        $excerptData = [
            'excerptTitle' => 'Excerpt Title 2',
            'excerptDescription' => 'Excerpt Description 2',
            'excerptMore' => 'Excerpt More 2',
            'excerptTags' => ['Tag 3', 'Tag 4'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'excerptSegment' => 'updated-segment',
        ];

        // Add audience target groups if available
        if (!empty($targetGroupIds)) {
            $excerptData['excerptAudienceTargetGroups'] = $targetGroupIds;
        }

        $this->client->request('PUT', '/admin/api/pages/' . $id . '?locale=en', [], [], [], \json_encode(\array_merge([
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
            'authored' => '2020-06-09T00:00:00+00:00',
        ], $excerptData)) ?: null);

        $response = $this->client->getResponse();

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(4, $routeRepository->findBy([]));

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

        // test if the routes count still correct
        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(7, $routeRepository->findBy([]));

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
        $targetGroupIds = $this->createTargetGroups();

        $excerptData = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'excerptSegment' => 'copy-segment',
        ];

        // Add audience target groups if available
        if (!empty($targetGroupIds)) {
            $excerptData['excerptAudienceTargetGroups'] = $targetGroupIds;
        }

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&action=publish&parentId=%s&webspace=sulu-io', '0199ee04-c220-784e-a6fa-ac985870f2d5'),
            [],
            [],
            [],
            \json_encode(
                \array_merge([
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
                    'authored' => '2020-05-08T00:00:00+00:00',
                ], $excerptData),
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

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(10, $routeRepository->findBy([]));

        return $copiedPageId;
    }

    #[Depends('testCopy')]
    public function testMove(string $id): string
    {
        $this->client->request('POST', '/admin/api/pages/' . $id . '?locale=en&action=move&destination=0199ee04-c220-784e-a6fa-ac985870f2d5');

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_post_move.json', $response);

        $this->client->request('GET', '/admin/api/pages?locale=en&webspace=sulu-io&expandedIds=' . $id);
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_cget_after_move.json', $response);

        return $id;
    }

    #[Depends('testMove')]
    public function testDeleteSingleLocale(string $id): string
    {
        $this->client->request('GET', '/admin/api/pages/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{template: string, title: string, url: string} $pageData */
        $pageData = \json_decode((string) $response->getContent(), true);

        $this->client->request('PUT', '/admin/api/pages/' . $id . '?locale=de', [], [], [], \json_encode([
            'template' => $pageData['template'],
            'title' => $pageData['title'] . ' (DE)',
            'url' => '/de' . $pageData['url'],
        ]) ?: null);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        /** @var array<int, string> $contentLocales */
        $contentLocales = $content['contentLocales'];
        $this->assertContains('en', $availableLocales);
        $this->assertContains('de', $availableLocales);
        $this->assertContains('en', $contentLocales);
        $this->assertContains('de', $contentLocales);

        $this->client->request('DELETE', '/admin/api/pages/' . $id . '?locale=en&deleteLocale=true');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->client->request('GET', '/admin/api/pages/' . $id . '?locale=de');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        /** @var array<int, string> $contentLocales */
        $contentLocales = $content['contentLocales'];
        $this->assertNotContains('en', $availableLocales);
        $this->assertContains('de', $availableLocales);
        $this->assertNotContains('en', $contentLocales);
        $this->assertContains('de', $contentLocales);

        $this->client->request('GET', '/admin/api/pages/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertEquals('de', $content['ghostLocale']);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        $this->assertContains('de', $availableLocales);
        $this->assertNotContains('en', $availableLocales);

        return $id;
    }

    #[Depends('testDeleteSingleLocale')]
    public function testRecreateDeletedLocale(string $id): string
    {
        $this->client->request('PUT', '/admin/api/pages/' . $id . '?locale=en', [], [], [], \json_encode([
            'template' => 'default',
            'title' => 'Recreated Test Page (EN)',
            'url' => '/recreated-my-page',
        ]) ?: null);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        /** @var array<int, string> $contentLocales */
        $contentLocales = $content['contentLocales'];
        $this->assertContains('en', $availableLocales);
        $this->assertContains('de', $availableLocales);
        $this->assertContains('en', $contentLocales);
        $this->assertContains('de', $contentLocales);

        return $id;
    }

    #[Depends('testPost')]
    #[Depends('testOrderPages')]
    public function testDelete(string $id): int
    {
        // Add German translation before deleting to test multi-locale restore
        $this->client->request('GET', '/admin/api/pages/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{template: string, title: string, url: string} $pageData */
        $pageData = \json_decode((string) $response->getContent(), true);

        $this->client->request('PUT', '/admin/api/pages/' . $id . '?locale=de', [], [], [], \json_encode([
            'template' => $pageData['template'],
            'title' => $pageData['title'] . ' (DE)',
            'url' => '/de' . $pageData['url'],
        ]) ?: null);
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, string> $availableLocales */
        $availableLocales = $content['availableLocales'];
        $this->assertContains('en', $availableLocales);
        $this->assertContains('de', $availableLocales);

        $this->client->request('DELETE', '/admin/api/pages/' . $id . '?locale=en&force=true');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $routeRepository = $this->getContainer()->get(RouteRepositoryInterface::class);
        $this->assertCount(9, $routeRepository->findBy([]));

        $trashRepository = self::getContainer()->get(TrashItemRepositoryInterface::class);
        $trashItem = $trashRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $id,
        ]);
        $this->assertNotNull($trashItem);
        $id = $trashItem->getId();
        $this->assertNotEmpty($id);

        return $id;
    }

    #[Depends('testDelete')]
    public function testRestore(int $trashItemId): string
    {
        $trashItem = $this->getContainer()->get(TrashItemRepositoryInterface::class)->findOneBy(['id' => $trashItemId]);
        $this->assertNotNull($trashItem);

        $this->client->request('POST', '/admin/api/trash-items/' . $trashItemId . '?action=restore');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $pageId = $trashItem->getResourceId();
        $pageRepository = $this->getContainer()->get('sulu_page.page_repository');
        $restoredPage = $pageRepository->findOneBy(['uuid' => $pageId]);
        $this->assertNotNull($restoredPage);

        $dimensionContents = self::getEntityManager()
            ->getRepository(PageDimensionContent::class)
            ->findBy(['page' => $restoredPage]);

        $unlocalizedDraftCount = 0;
        $localizedDraftCount = 0;
        foreach ($dimensionContents as $dimensionContent) {
            if ('draft' === $dimensionContent->getStage()) {
                if (null === $dimensionContent->getLocale()) {
                    ++$unlocalizedDraftCount;
                } else {
                    ++$localizedDraftCount;
                }
            }
        }

        $this->assertSame(
            1,
            $unlocalizedDraftCount,
            'Should have exactly 1 unlocalized dimension content after restoring multi-locale page, but found: ' . $unlocalizedDraftCount
        );

        $this->assertSame(
            2,
            $localizedDraftCount,
            'Should have exactly 2 localized dimension contents (en, de) after restore, but found: ' . $localizedDraftCount
        );

        $this->client->request('GET', '/admin/api/pages/' . $pageId . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('page_post_restore.json', $response, 200);

        return $trashItem->getResourceId();
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }

    /* --- The following tests are independent tests and purge the database on their own --- */
    public function testDeletePageWithDescendantsWithoutForceReturnsConflict(): void
    {
        $this->purgeDatabase();
        $homepage = $this->createHomepage('homepage-uuid', 'sulu_io');

        $parentPage = $this->createPage('homepage-uuid', [
            'title' => 'Parent Page',
            'template' => 'default',
            'url' => '/parent',
        ]);

        $childPage = $this->createPage($parentPage->getUuid(), [
            'title' => 'Child Page',
            'template' => 'default',
            'url' => '/parent/child',
        ]);

        $parentUuid = $parentPage->getUuid();
        self::getEntityManager()->clear();

        $this->client->request('DELETE', '/admin/api/pages/' . $parentUuid . '?locale=en');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(409, $response);

        /** @var array{code: int, message: string} $responseData */
        $responseData = \json_decode((string) $response->getContent(), true);

        $this->assertEquals(RemovePageDependantResourcesFoundException::EXCEPTION_CODE_DEPENDANT_RESOURCES_FOUND, $responseData['code']);

        $this->client->request('DELETE', '/admin/api/pages/' . $parentUuid . '?locale=en&force=true');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);
    }

    public function testDeletePageWithDescendantsWithForceSucceeds(): void
    {
        $this->purgeDatabase();
        $homepage = $this->createHomepage('homepage-uuid-2', 'sulu_io');

        $parentPage = $this->createPage('homepage-uuid-2', [
            'title' => 'Parent Page',
            'template' => 'default',
            'url' => '/parent',
        ]);

        $child = $this->createPage($parentPage->getUuid(), [
            'title' => 'Child Page',
            'template' => 'default',
            'url' => '/parent/child',
        ]);

        $grandchild = $this->createPage($child->getUuid(), [
            'title' => 'Grandchild Page',
            'template' => 'default',
            'url' => '/parent/child/grandchild',
        ]);

        $parentUuid = $parentPage->getUuid();
        $childUuid = $child->getUuid();
        $grandchildUuid = $grandchild->getUuid();

        self::getEntityManager()->clear();

        $this->client->request('DELETE', '/admin/api/pages/' . $parentUuid . '?locale=en&force=true');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $pageRepository = $this->getContainer()->get('sulu_page.page_repository');
        $this->assertNull($pageRepository->findOneBy(['uuid' => $parentUuid]));
        $this->assertNull($pageRepository->findOneBy(['uuid' => $childUuid]));
        $this->assertNull($pageRepository->findOneBy(['uuid' => $grandchildUuid]));
    }
}

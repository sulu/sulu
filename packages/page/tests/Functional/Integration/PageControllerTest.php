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
            'excerpt' => [
                'title' => 'Excerpt Title',
                'description' => 'Excerpt Description',
                'more' => 'Excerpt More',
            ],
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
                    'seo' => [
                        'title' => 'Seo Title',
                        'description' => 'Seo Description',
                        'canonicalUrl' => 'https://sulu.io/',
                        'keywords' => 'Seo Keyword 1, Seo Keyword 2',
                    ],
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
            'excerpt' => [
                'title' => 'Modified Excerpt Title',
                'description' => 'Modified Excerpt Description',
                'more' => 'Modified Excerpt More',
                'icon' => null,
                'media' => null,
            ],
            'excerptTags' => ['Modified Tag 1', 'Modified Tag 2'],
            'excerptCategories' => [],
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
                    'seo' => [
                        'title' => 'Modified Seo Title',
                        'description' => 'Modified Seo Description',
                        'canonicalUrl' => 'https://modified-sulu.io/',
                        'keywords' => 'Modified Seo Keyword 1, Modified Seo Keyword 2',
                    ],
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
            'excerpt' => [
                'title' => 'Excerpt Title',
                'description' => 'Excerpt Description',
                'more' => 'Excerpt More',
            ],
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
                'seo' => [
                    'title' => 'Seo Title',
                    'description' => 'Seo Description',
                    'canonicalUrl' => 'https://sulu.io/',
                    'keywords' => 'Seo Keyword 1, Seo Keyword 2',
                ],
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
            'excerpt' => [
                'title' => 'Excerpt Title',
                'description' => 'Excerpt Description',
                'more' => 'Excerpt More',
            ],
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
                    'seo' => [
                        'title' => 'Seo Title',
                        'description' => 'Seo Description',
                        'canonicalUrl' => 'https://sulu.io/',
                        'keywords' => 'Seo Keyword 1, Seo Keyword 2',
                    ],
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
    public function testPostTriggerCopyLocaleWithLocaleFallbackAsSrc(string $id): void
    {
        $this->client->request('POST', '/admin/api/pages/' . $id . '?locale=en&action=copy_locale&dest=de');

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);

        $this->assertHttpStatusCode(200, $response);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('contentLocales', $content);
        $this->assertIsArray($content['contentLocales']);
        $this->assertContains('de', $content['contentLocales']);
        $this->assertContains('en', $content['contentLocales']);
    }

    #[Depends('testPost')]
    #[Depends('testGet')]
    public function testPut(string $id): void
    {
        $targetGroupIds = $this->createTargetGroups();

        $excerptData = [
            'excerpt' => [
                'title' => 'Excerpt Title 2',
                'description' => 'Excerpt Description 2',
                'more' => 'Excerpt More 2',
            ],
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
            'seo' => [
                'title' => 'Seo Title 2',
                'description' => 'Seo Description 2',
                'canonicalUrl' => 'https://sulu.io/2',
                'keywords' => 'Seo Keyword 3, Seo Keyword 4',
            ],
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
            'excerpt' => [
                'title' => 'Excerpt Title',
                'description' => 'Excerpt Description',
                'more' => 'Excerpt More',
                'icon' => null,
                'media' => null,
            ],
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
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
                    'seo' => [
                        'title' => 'Seo Title',
                        'description' => 'Seo Description',
                        'canonicalUrl' => 'https://sulu.io/',
                        'keywords' => 'Seo Keyword 1, Seo Keyword 2',
                    ],
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

    public function testGetListWithExpandedIdsFlatAndSelectedJoinedFields(): void
    {
        self::purgeDatabase();

        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&action=publish&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [],
            [],
            [],
            \json_encode([
                'template' => 'default',
                'title' => 'Expanded Child Page',
                'url' => '/expanded-child-page',
                'author' => null,
                'authored' => '2020-05-08T00:00:00+00:00',
            ]) ?: null,
        );
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        self::ensureKernelShutdown();

        $this->client->request(
            'GET',
            \sprintf(
                '/admin/api/pages?exclude-ghosts=false&exclude-shadows=false&locale=en&webspace=sulu-io&expandedIds=%s&fields=title,author,creator,id&flat=true',
                $homepage->getId(),
            ),
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded: array{pages: array<int, array<string, mixed>>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pages = $content['_embedded']['pages'];

        $this->assertCount(1, $pages);
        $this->assertSame($homepage->getId(), $pages[0]['id']);
        $this->assertArrayHasKey('_embedded', $pages[0]);

        /** @var array{pages: array<int, array<string, mixed>>} $embedded */
        $embedded = $pages[0]['_embedded'];
        $this->assertCount(1, $embedded['pages']);

        /** @var array<string, mixed> $childPage */
        $childPage = $embedded['pages'][0];

        $this->assertSame('Expanded Child Page', $childPage['title']);
        $this->assertArrayHasKey('author', $childPage);
        $this->assertArrayHasKey('creator', $childPage);
        $this->assertIsString($childPage['creator']);
        $this->assertNotSame('', $childPage['creator']);
    }

    public function testNestedTemplatePropertyWithSlash(): void
    {
        self::purgeDatabase();

        $homepage = $this->createHomepage('homepage-nested-property-uuid', 'sulu-io');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [],
            [],
            [],
            \json_encode([
                'template' => 'landing_page',
                'title' => 'Nested Property Page',
                'url' => '/nested-property-page',
                'nestedProperty' => [
                    'value_1' => 'Nested Value 1',
                    'value_2' => 'Nested Value 2',
                ],
            ]) ?: null,
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response);

        /** @var array{id: string, nestedProperty: array{value_1: string, value_2: string}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pageId = $content['id'];
        $this->assertSame(
            ['value_1' => 'Nested Value 1', 'value_2' => 'Nested Value 2'],
            $content['nestedProperty'],
        );
        $this->assertArrayNotHasKey('nestedProperty/value_1', $content);
        $this->assertArrayNotHasKey('nestedProperty/value_2', $content);

        $this->client->request(
            'PUT',
            '/admin/api/pages/' . $pageId . '?locale=en',
            [],
            [],
            [],
            \json_encode([
                'template' => 'landing_page',
                'title' => 'Nested Property Page',
                'url' => '/nested-property-page',
                'nestedProperty' => [
                    'value_1' => 'Updated Nested Value 1',
                    'value_2' => 'Updated Nested Value 2',
                ],
            ]) ?: null,
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{nestedProperty: array{value_1: string, value_2: string}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame(
            ['value_1' => 'Updated Nested Value 1', 'value_2' => 'Updated Nested Value 2'],
            $content['nestedProperty'],
        );
        $this->assertArrayNotHasKey('nestedProperty/value_1', $content);
        $this->assertArrayNotHasKey('nestedProperty/value_2', $content);

        $this->client->request('GET', '/admin/api/pages/' . $pageId . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{nestedProperty: array{value_1: string, value_2: string}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame(
            ['value_1' => 'Updated Nested Value 1', 'value_2' => 'Updated Nested Value 2'],
            $content['nestedProperty'],
        );
        $this->assertArrayNotHasKey('nestedProperty/value_1', $content);
        $this->assertArrayNotHasKey('nestedProperty/value_2', $content);
    }

    public function testNestedPropertyWithContentAndViewNames(): void
    {
        self::purgeDatabase();

        $homepage = $this->createHomepage('homepage-metadata-nested-uuid', 'sulu-io');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [],
            [],
            [],
            \json_encode([
                'template' => 'landing_page',
                'title' => 'Metadata Nested Page',
                'url' => '/metadata-nested-page',
                'metadata' => [
                    'content' => 'Content Value',
                    'view' => 'View Value',
                ],
                'nestedProperty' => [
                    'value_1' => 'Value 1',
                    'value_2' => 'Value 2',
                ],
            ]) ?: null,
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response);

        /** @var array{id: string, metadata: array{content: string, view: string}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pageId = $content['id'];
        $this->assertSame('Content Value', $content['metadata']['content']);
        $this->assertSame('View Value', $content['metadata']['view']);
        $this->assertArrayNotHasKey('metadata/content', $content);
        $this->assertArrayNotHasKey('metadata/view', $content);

        $this->client->request(
            'PUT',
            '/admin/api/pages/' . $pageId . '?locale=en',
            [],
            [],
            [],
            \json_encode([
                'template' => 'landing_page',
                'title' => 'Metadata Nested Page',
                'url' => '/metadata-nested-page',
                'metadata' => [
                    'content' => 'Updated Content Value',
                    'view' => 'Updated View Value',
                ],
                'nestedProperty' => [
                    'value_1' => 'Updated Value 1',
                    'value_2' => 'Updated Value 2',
                ],
            ]) ?: null,
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{metadata: array{content: string, view: string}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame('Updated Content Value', $content['metadata']['content']);
        $this->assertSame('Updated View Value', $content['metadata']['view']);

        $this->client->request('GET', '/admin/api/pages/' . $pageId . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{metadata: array{content: string, view: string}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame('Updated Content Value', $content['metadata']['content']);
        $this->assertSame('Updated View Value', $content['metadata']['view']);
        $this->assertArrayNotHasKey('metadata/content', $content);
        $this->assertArrayNotHasKey('metadata/view', $content);
    }

    public function testNestedUnlocalizedProperty(): void
    {
        self::purgeDatabase();

        $homepage = $this->createHomepage('homepage-unlocalized-nested-uuid', 'sulu-io');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [],
            [],
            [],
            \json_encode([
                'template' => 'landing_page',
                'title' => 'Unlocalized Nested Page',
                'url' => '/unlocalized-nested-page',
                'nestedProperty' => [
                    'value_1' => 'Localized Value 1',
                    'value_2' => 'Localized Value 2',
                ],
                'unlocalizedNested' => [
                    'value' => 'Unlocalized Nested Value',
                ],
            ]) ?: null,
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response);

        /** @var array{id: string, unlocalizedNested: array{value: string}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pageId = $content['id'];
        $this->assertSame(
            ['value' => 'Unlocalized Nested Value'],
            $content['unlocalizedNested'],
        );
        $this->assertArrayNotHasKey('unlocalizedNested/value', $content);

        $this->client->request(
            'PUT',
            '/admin/api/pages/' . $pageId . '?locale=de',
            [],
            [],
            [],
            \json_encode([
                'template' => 'landing_page',
                'title' => 'German Title',
                'url' => '/unlocalized-nested-page-de',
                'nestedProperty' => [
                    'value_1' => 'German Value 1',
                    'value_2' => 'German Value 2',
                ],
                'unlocalizedNested' => [
                    'value' => 'Updated Unlocalized Value',
                ],
            ]) ?: null,
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{unlocalizedNested: array{value: string}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame(
            ['value' => 'Updated Unlocalized Value'],
            $content['unlocalizedNested'],
        );

        $this->client->request('GET', '/admin/api/pages/' . $pageId . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{unlocalizedNested: array{value: string}, nestedProperty: array{value_1: string, value_2: string}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame(
            ['value' => 'Updated Unlocalized Value'],
            $content['unlocalizedNested'],
            'Unlocalized nested property should reflect the update from any locale',
        );
        $this->assertSame(
            ['value_1' => 'Localized Value 1', 'value_2' => 'Localized Value 2'],
            $content['nestedProperty'],
            'Localized nested property should retain the original English values',
        );
    }

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

    public function testOrderingPagesWhenSiblingHasChildren(): void
    {
        self::purgeDatabase();

        $homepage = $this->createHomepage('homepage-order-with-children-uuid', 'sulu-io');

        $pageWithChildren = $this->createPage($homepage->getId(), [
            'title' => 'Page with Children',
            'template' => 'default',
            'url' => '/page-with-children',
        ]);

        $this->createPage($pageWithChildren->getId(), [
            'title' => 'Child 1',
            'template' => 'default',
            'url' => '/page-with-children/child-1',
        ]);

        $this->createPage($pageWithChildren->getId(), [
            'title' => 'Child 2',
            'template' => 'default',
            'url' => '/page-with-children/child-2',
        ]);

        $this->createPage($pageWithChildren->getId(), [
            'title' => 'Child 3',
            'template' => 'default',
            'url' => '/page-with-children/child-3',
        ]);

        $this->createPage($homepage->getId(), [
            'title' => 'Page 2',
            'template' => 'default',
            'url' => '/page-2',
        ]);

        $this->createPage($homepage->getId(), [
            'title' => 'Page 3',
            'template' => 'default',
            'url' => '/page-3',
        ]);

        $this->createPage($homepage->getId(), [
            'title' => 'Page 4',
            'template' => 'default',
            'url' => '/page-4',
        ]);

        self::ensureKernelShutdown();

        $this->client->request(
            'GET',
            \sprintf('/admin/api/pages?exclude-ghosts=false&exclude-shadows=false&locale=en&webspace=sulu-io&parentId=%s&fields=title,id&flat=true', $homepage->getId())
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        /** @var array{_embedded: array{pages: array<int, array{title: string, id: string}>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pages = $content['_embedded']['pages'];

        $this->assertCount(4, $pages, 'Should have 4 top-level pages');
        $this->assertEquals('Page with Children', $pages[0]['title'], 'Page with children should be at index 0');
        $this->assertEquals('Page 2', $pages[1]['title'], 'Page 2 should be at index 1');
        $this->assertEquals('Page 3', $pages[2]['title'], 'Page 3 should be at index 2');
        $this->assertEquals('Page 4', $pages[3]['title'], 'Page 4 should be at index 3');

        $page2Id = $pages[1]['id'];

        self::ensureKernelShutdown();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages/%s?webspace=sulu-io&locale=en&action=order', $page2Id),
            [],
            [],
            [],
            \json_encode(['position' => 4]) ?: null
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        self::ensureKernelShutdown();

        $this->client->request(
            'GET',
            \sprintf('/admin/api/pages?exclude-ghosts=false&exclude-shadows=false&locale=en&webspace=sulu-io&parentId=%s&fields=title,id&flat=true', $homepage->getId())
        );
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        /** @var array{_embedded: array{pages: array<int, array{title: string, id: string}>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pagesAfter = $content['_embedded']['pages'];

        $this->assertCount(4, $pagesAfter, 'Should still have 4 top-level pages');
        $this->assertEquals('Page with Children', $pagesAfter[0]['title'], 'After moving Page 2 past page with children: Page with Children should remain at index 0');
        $this->assertEquals('Page 3', $pagesAfter[1]['title'], 'After moving Page 2 to position 4: Page 3 should be at index 1');
        $this->assertEquals('Page 4', $pagesAfter[2]['title'], 'After moving Page 2 to position 4: Page 4 should be at index 2');
        $this->assertEquals('Page 2', $pagesAfter[3]['title'], 'After moving Page 2 to position 4: Page 2 should be at index 3 (position 4)');
    }

    public function testGetListByIdsIncludesNestedPages(): void
    {
        self::purgeDatabase();
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        $rootPage = $this->createPage($homepage->getUuid(), [
            'template' => 'default',
            'title' => 'Root Page',
            'url' => '/root',
            'locale' => 'en',
        ]);

        $childPage1 = $this->createPage($rootPage->getUuid(), [
            'template' => 'default',
            'title' => 'Child Page 1',
            'url' => '/root/child1',
            'locale' => 'en',
        ]);

        $grandchildPage = $this->createPage($childPage1->getUuid(), [
            'template' => 'default',
            'title' => 'Grandchild Page',
            'url' => '/root/child1/grandchild',
            'locale' => 'en',
        ]);

        $childPage2 = $this->createPage($rootPage->getUuid(), [
            'template' => 'default',
            'title' => 'Child Page 2',
            'url' => '/root/child2',
            'locale' => 'en',
        ]);

        self::getEntityManager()->clear();

        // Request pages by IDs - this simulates what the page_selection does
        $ids = \sprintf('%s,%s', $grandchildPage->getUuid(), $childPage2->getUuid());
        $this->client->request(
            'GET',
            \sprintf('/admin/api/pages?locale=en&ids=%s&page=1&flat=true', $ids)
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded: array{pages?: array<int, array{title: string, id: string}>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pages = $content['_embedded']['pages'] ?? [];

        $this->assertCount(2, $pages, 'Should load both nested pages');

        $pageIds = \array_column($pages, 'id');
        $this->assertContains($grandchildPage->getUuid(), $pageIds, 'Should include grandchild page');
        $this->assertContains($childPage2->getUuid(), $pageIds, 'Should include child page 2');

        $titles = \array_column($pages, 'title');
        $this->assertContains('Grandchild Page', $titles);
        $this->assertContains('Child Page 2', $titles);
    }

    public function testFlatListIncludesPermissions(): void
    {
        self::purgeDatabase();

        $homepage = $this->createHomepage('test-flat-permissions', 'sulu-io');
        $this->createPage($homepage->getId(), [
            'title' => 'Test Page 1',
            'template' => 'default',
            'url' => '/test1',
        ]);
        $this->createPage($homepage->getId(), [
            'title' => 'Test Page 2',
            'template' => 'default',
            'url' => '/test2',
        ]);

        self::ensureKernelShutdown();

        $this->client->request(
            'GET',
            \sprintf('/admin/api/pages?locale=en&webspace=sulu-io&parentId=%s', $homepage->getId())
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded?: array{pages?: list<array<string, mixed>>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pages = $content['_embedded']['pages'] ?? [];

        $this->assertNotEmpty($pages, 'Should have pages in result');
        foreach ($pages as $page) {
            $this->assertArrayHasKey('_hasPermissions', $page, 'Each page should have _hasPermissions field');
            $this->assertIsBool($page['_hasPermissions'], '_hasPermissions should be boolean');

            if ($page['_hasPermissions']) {
                $this->assertArrayHasKey('_permissions', $page, 'Pages with permissions should have _permissions array');
                $this->assertIsArray($page['_permissions'], '_permissions should be array');
            }
        }
    }

    public function testNestedListIncludesPermissions(): void
    {
        self::purgeDatabase();

        $homepage = $this->createHomepage('test-nested-permissions', 'sulu-io');
        $parentPage = $this->createPage($homepage->getId(), [
            'title' => 'Parent Page',
            'template' => 'default',
            'url' => '/parent',
        ]);
        $this->createPage($parentPage->getId(), [
            'title' => 'Child Page',
            'template' => 'default',
            'url' => '/parent/child',
        ]);

        self::ensureKernelShutdown();

        $this->client->request(
            'GET',
            \sprintf('/admin/api/pages?locale=en&webspace=sulu-io&expandedIds=%s', $homepage->getId())
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded?: array{pages?: list<array{_hasPermissions?: bool, _embedded?: array{pages?: list<array<string, mixed>>}}>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pages = $content['_embedded']['pages'] ?? [];

        $this->assertNotEmpty($pages, 'Should have pages in result');
        $this->assertArrayHasKey('_hasPermissions', $pages[0], 'Root page should have _hasPermissions field');

        $nestedPages = $pages[0]['_embedded']['pages'] ?? [];
        foreach ($nestedPages as $child) {
            $this->assertArrayHasKey('_hasPermissions', $child, 'Child pages should have _hasPermissions field');
        }
    }

    public function testEnhanceRowsAddsHasChildrenProperty(): void
    {
        self::purgeDatabase();

        $homepage = $this->createHomepage('test-has-children', 'sulu-io');
        $parentPage = $this->createPage($homepage->getId(), [
            'title' => 'Parent Page',
            'template' => 'default',
            'url' => '/parent',
        ]);
        $this->createPage($parentPage->getId(), [
            'title' => 'Child Page',
            'template' => 'default',
            'url' => '/parent/child',
        ]);

        self::ensureKernelShutdown();

        $this->client->request(
            'GET',
            \sprintf('/admin/api/pages?locale=en&webspace=sulu-io&parentId=%s', $homepage->getId())
        );

        $response = $this->client->getResponse();
        /** @var array{_embedded: array{pages: array<int, array{hasChildren: bool}>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pages = $content['_embedded']['pages'];

        $this->assertCount(1, $pages);
        $this->assertTrue($pages[0]['hasChildren'], 'Parent page should have hasChildren=true');
    }
}

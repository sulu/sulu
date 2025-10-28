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

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Sitemap;

use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Application\Message\CopyLocalePageMessage;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Tests for the PageSmartContentProvider.
 *
 * @phpstan-type PageData array{
 *     title?: string,
 *     url?: string,
 *     template?: string,
 *     locale?: string,
 *     parent?: string|null,
 * }
 */
class PagesSitemapProviderTest extends WebsiteTestCase
{
    use CreateCategoryTrait;
    use CreateTagTrait;
    use AssertSnapshotTrait;

    /**
     * @var array<string, PageInterface>
     */
    private static array $pages = [];

    /**
     * @var array<string>
     */
    private static array $webspaces = ['sulu-io', 'blog'];

    /**
     * @var array<string, string>
     */
    private static array $parentPages = [];

    protected static KernelBrowser $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createWebsiteClient();
        parent::setUpBeforeClass();
        self::purgeDatabase();

        // Create parent pages for testing dataSource filter
        foreach (self::$webspaces as $webspaceKey) {
            // Create parent pages first
            $parentData = [
                'title' => 'Parent Page ' . $webspaceKey,
                'url' => '/',
                'template' => 'default',
                'locale' => 'en',
                'changed' => '2023-01-14T12:00:00+00:00',
            ];

            $parentPage = self::createPage($webspaceKey, CreatePageMessageHandler::HOMEPAGE_PARENT_ID, $parentData);
            self::$pages['homepage_' . $webspaceKey] = $parentPage;
            self::$parentPages[$webspaceKey] = $parentPage->getUuid();
        }

        // Create pages for blog webspace
        self::$pages['tech1'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Latest in Tech',
                'template' => 'default',
                'locale' => 'en',
                'url' => '/latest-in-tech',
            ],
        );

        self::copyLocalePage(
            self::$pages['tech1']->getUuid(),
            'en',
            'de',
        );

        self::modifyPage(
            self::$pages['tech1']->getUuid(),
            [
                'title' => 'Neueste Technologie',
                'template' => 'default',
                'locale' => 'de',
                'url' => '/neueste-technologie',
            ],
        );

        self::$pages['onlyDE'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Page Only in DE',
                'template' => 'default',
                'locale' => 'de',
                'url' => '/only-de',
            ],
        );

        self::$pages['onlyEN'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Page Only in EN',
                'template' => 'default',
                'locale' => 'en',
                'url' => '/only-en',
            ],
        );

        self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Page Hide in Sitemap',
                'template' => 'default',
                'locale' => 'en',
                'url' => '/hide-in-sitemap',
                'seoHideInSitemap' => true,
            ],
        );

        self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Last modified page',
                'template' => 'default',
                'locale' => 'en',
                'lastModifiedEnabled' => true,
                'lastModified' => '1995-11-29T12:00:00+00:00',
                'url' => '/last-modified-page',
            ],
        );

        self::$pages['link_target'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Link Target Page',
                'template' => 'default',
                'locale' => 'en',
                'url' => '/link-target',
            ],
        );

        self::$pages['link_internal'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Internal Link Page',
                'template' => 'default',
                'locale' => 'en',
                'url' => '/internal-link',
                'linkOn' => true,
                'linkData' => [
                    'href' => self::$pages['link_target']->getUuid(),
                    'provider' => 'page',
                ],
            ],
        );

        self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'External Link Page',
                'template' => 'default',
                'locale' => 'en',
                'url' => '/external-link',
                'linkOn' => true,
                'linkData' => [
                    'href' => 'https://example.com',
                    'provider' => 'external',
                ],
            ],
        );

        self::$pages['guide'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Guide to Latest Tech',
                'template' => 'default',
                'locale' => 'en',
                'url' => '/guides',
            ],
        );

        self::copyLocalePage(
            self::$pages['guide']->getUuid(),
            'en',
            'de',
        );
        self::modifyPage(
            self::$pages['guide']->getUuid(),
            [
                'title' => 'Should not be in Sitemap',
                'template' => 'default',
                'locale' => 'de',
                'url' => '/not-in-sitemap',
                'seoHideInSitemap' => true,
            ],
        );

        // Create pages for sulu.io webspace
        self::$pages['product'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Product',
                'template' => 'default',
                'locale' => 'en',
                'url' => '/product',
            ],
        );

        self::copyLocalePage(
            self::$pages['product']->getUuid(),
            'en',
            'de',
        );
        self::modifyPage(
            self::$pages['product']->getUuid(),
            [
                'title' => 'Produkt',
                'template' => 'default',
                'locale' => 'de',
                'url' => '/produkt',
            ],
        );

        self::$pages['not_in_sitemap'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Not in sitemap',
                'template' => 'default',
                'locale' => 'en',
                'url' => '/not-in-sitemap',
                'seoHideInSitemap' => true,
            ],
        );

        self::$pages['only_de'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Only de',
                'template' => 'default',
                'locale' => 'de',
                'url' => '/only-de',
            ],
        );

        self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Last modified',
                'template' => 'default',
                'lastModifiedEnabled' => true,
                'lastModified' => '1995-11-29T12:00:00+00:00',
                'locale' => 'en',
                'url' => '/last-modified',
            ],
        );

        self::updateChangedDate();

        self::getEntityManager()->clear();
    }

    public function testBlogSitemapXML(): void
    {
        self::$client->request('GET', 'http://blog.io/sitemaps/pages-1.xml');
        /** @var string $sitemap */
        $sitemap = self::$client->getResponse()->getContent();

        $this->assertSnapshot('blog-pages-sitemap.xml', $this->beautifySitemapContent($sitemap), '');
    }

    public function testSuluSitemapXML(): void
    {
        self::$client->request('GET', 'http://sulu.io/sitemaps/pages-1.xml');
        /** @var string $sitemap */
        $sitemap = self::$client->getResponse()->getContent();

        $this->assertSnapshot('sulu-pages-sitemap.xml', $this->beautifySitemapContent($sitemap), '');
    }

    private function beautifySitemapContent(string $sitemap): string
    {
        $replaces = [
            '<urlset ' => \PHP_EOL . '<urlset ',
            '<url>' => \PHP_EOL . '    <url>',
            '<loc>' => \PHP_EOL . '        <loc>',
            '<lastmod>' => \PHP_EOL . '        <lastmod>',
            '</url>' => \PHP_EOL . '    </url>',
            '</urlset>' => \PHP_EOL . '</urlset>',
            '<xhtml:link' => \PHP_EOL . '        <xhtml:link',
        ];

        foreach ($replaces as $search => $replace) {
            $sitemap = \str_replace($search, $replace, $sitemap);
        }

        return $sitemap;
    }

    private static function updateChangedDate(): void
    {
        $connection = self::getEntityManager()->getConnection();

        foreach (self::$pages as $page) {
            $sql = 'UPDATE pa_page_dimension_contents SET changed = :changed WHERE pageUuid = :dimensionId';

            $connection->executeStatement($sql, [
                'changed' => '2023-01-14 12:00:00',
                'dimensionId' => $page->getUuid(),
            ]);
        }
    }

    /**
     * @param PageData $data
     */
    private static function createPage(
        string $webspaceKey,
        string $parentId,
        array $data = [],
    ): PageInterface {
        $data = \array_merge([
            'title' => 'Example Page',
            'url' => '/example-page-' . \uniqid(),
            'template' => 'default',
            'locale' => 'en',
        ], $data);

        $messageBus = self::getContainer()->get('sulu_message_bus');

        $envelope = $messageBus->dispatch(new Envelope(new CreatePageMessage(webspaceKey: $webspaceKey, parentId: $parentId, data: $data), [new EnableFlushStamp()]));
        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        /** @var PageInterface $page */
        $page = $handledStamps[0]->getResult();
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionPageMessage(
                    identifier: ['uuid' => $page->getUuid()],
                    locale: $data['locale'],
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                ),
                [new EnableFlushStamp()],
            ),
        );

        return $page;
    }

    private static function copyLocalePage(
        string $identifier,
        string $sourceLocale,
        string $targetLocale,
    ): void {
        $messageBus = self::getContainer()->get('sulu_message_bus');

        $envelope = $messageBus->dispatch(new Envelope(new CopyLocalePageMessage(identifier: ['uuid' => $identifier], sourceLocale: $sourceLocale, targetLocale: $targetLocale), [new EnableFlushStamp()]));
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionPageMessage(
                    identifier: ['uuid' => $identifier],
                    locale: $targetLocale,
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                ),
                [new EnableFlushStamp()],
            ),
        );
    }

    /**
     * @param PageData $data
     */
    private static function modifyPage(
        string $identifier,
        array $data,
    ): PageInterface {
        $data = \array_merge([
            'title' => 'Example Page',
            'url' => '/example-page-' . \uniqid(),
            'template' => 'default',
            'locale' => 'en',
        ], $data);

        $messageBus = self::getContainer()->get('sulu_message_bus');

        $envelope = $messageBus->dispatch(new Envelope(new ModifyPageMessage(identifier: ['uuid' => $identifier], data: $data), [new EnableFlushStamp()]));
        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        /** @var PageInterface $page */
        $page = $handledStamps[0]->getResult();
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionPageMessage(
                    identifier: ['uuid' => $page->getUuid()],
                    locale: $data['locale'],
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                ),
                [new EnableFlushStamp()],
            ),
        );

        return $page;
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

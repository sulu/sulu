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

namespace Sulu\Article\Tests\Functional\Infrastructure\Sulu\Sitemap;

use Sulu\Article\Application\Message\ApplyWorkflowTransitionArticleMessage;
use Sulu\Article\Application\Message\CopyLocaleArticleMessage;
use Sulu\Article\Application\Message\CreateArticleMessage;
use Sulu\Article\Application\Message\ModifyArticleMessage;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Tests for the ArticleSmartContentProvider.
 *
 * @phpstan-type ArticleData array{
 *     title?: string,
 *     url?: string,
 *     template?: string,
 *     locale?: string,
 *     parent?: string|null,
 *     mainWebspace?: string,
 * }
 */
class ArticlesSitemapProviderTest extends WebsiteTestCase
{
    use CreateCategoryTrait;
    use CreateTagTrait;
    use AssertSnapshotTrait;

    /**
     * @var array<string, ArticleInterface>
     */
    private static array $articles = [];

    protected static KernelBrowser $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createWebsiteClient();
        parent::setUpBeforeClass();
        self::purgeDatabase();

        // Create articles for blog webspace
        self::$articles['tech1'] = self::createArticle(
            [
                'title' => 'Latest in Tech',
                'template' => 'article',
                'locale' => 'en',
                'url' => '/latest-in-tech',
                'mainWebspace' => 'blog',
            ],
        );

        self::copyLocaleArticle(
            self::$articles['tech1']->getUuid(),
            'en',
            'de',
        );

        self::modifyArticle(
            self::$articles['tech1']->getUuid(),
            [
                'title' => 'Neueste Technologie',
                'template' => 'article',
                'locale' => 'de',
                'url' => '/neueste-technologie',
                'mainWebspace' => 'blog',
            ],
        );

        self::$articles['onlyDE'] = self::createArticle(
            [
                'title' => 'Article Only in DE',
                'template' => 'article',
                'locale' => 'de',
                'url' => '/only-de',
                'mainWebspace' => 'blog',
            ],
        );

        self::$articles['onlyEN'] = self::createArticle(
            [
                'title' => 'Article Only in EN',
                'template' => 'article',
                'locale' => 'en',
                'url' => '/only-en',
                'mainWebspace' => 'blog',
            ],
        );

        self::createArticle(
            [
                'title' => 'Article Hide in Sitemap',
                'template' => 'article',
                'locale' => 'en',
                'url' => '/hide-in-sitemap',
                'seoHideInSitemap' => true,
                'mainWebspace' => 'blog',
            ],
        );

        self::createArticle(
            [
                'title' => 'Last modified Article',
                'template' => 'article',
                'locale' => 'en',
                'lastModifiedEnabled' => true,
                'lastModified' => '1995-11-29T12:00:00+00:00',
                'url' => '/last-modified-article',
                'mainWebspace' => 'blog',
            ],
        );

        self::$articles['guide'] = self::createArticle(
            [
                'title' => 'Guide to Latest Tech',
                'template' => 'article',
                'locale' => 'en',
                'url' => '/guides',
                'mainWebspace' => 'blog',
            ],
        );

        self::copyLocaleArticle(
            self::$articles['guide']->getUuid(),
            'en',
            'de',
        );
        self::modifyArticle(
            self::$articles['guide']->getUuid(),
            [
                'title' => 'Should not be in Sitemap',
                'template' => 'article',
                'locale' => 'de',
                'url' => '/not-in-sitemap',
                'seoHideInSitemap' => true,
            ],
        );

        // Create articles for sulu.io webspace
        self::$articles['product'] = self::createArticle(
            [
                'title' => 'Product',
                'template' => 'article',
                'locale' => 'en',
                'url' => '/product',
                'mainWebspace' => 'sulu-io',
                'additionalWebspaces' => ['blog'],
            ],
        );

        self::copyLocaleArticle(
            self::$articles['product']->getUuid(),
            'en',
            'de',
        );
        self::modifyArticle(
            self::$articles['product']->getUuid(),
            [
                'title' => 'Produkt',
                'template' => 'article',
                'locale' => 'de',
                'url' => '/produkt',
            ],
        );

        self::$articles['not_in_sitemap'] = self::createArticle(
            [
                'title' => 'Not in sitemap',
                'template' => 'article',
                'locale' => 'en',
                'url' => '/not-in-sitemap',
                'seoHideInSitemap' => true,
                'mainWebspace' => 'sulu-io',
            ],
        );

        self::$articles['only_de'] = self::createArticle(
            [
                'title' => 'Only de',
                'template' => 'article',
                'locale' => 'de',
                'url' => '/only-de',
                'mainWebspace' => 'sulu-io',
            ],
        );

        self::createArticle(
            [
                'title' => 'Last modified',
                'template' => 'article',
                'lastModifiedEnabled' => true,
                'lastModified' => '1995-11-29T12:00:00+00:00',
                'locale' => 'en',
                'url' => '/last-modified',
                'mainWebspace' => 'sulu-io',
            ],
        );

        self::updateChangedDate();

        self::getEntityManager()->clear();
    }

    public function testBlogSitemapXML(): void
    {
        self::$client->request('GET', 'http://blog.io/sitemaps/articles-1.xml');
        /** @var string $sitemap */
        $sitemap = self::$client->getResponse()->getContent();

        $this->assertSnapshot('blog-articles-sitemap.xml', $sitemap);
    }

    public function testSuluSitemapXML(): void
    {
        self::$client->request('GET', 'http://sulu.io/sitemaps/articles-1.xml');
        /** @var string $sitemap */
        $sitemap = self::$client->getResponse()->getContent();

        $this->assertSnapshot('sulu-articles-sitemap.xml', $sitemap);
    }

    private static function updateChangedDate(): void
    {
        $connection = self::getEntityManager()->getConnection();

        foreach (self::$articles as $article) {
            $sql = 'UPDATE ar_article_dimension_contents SET changed = :changed WHERE articleUuid = :dimensionId';

            $connection->executeStatement($sql, [
                'changed' => '2023-01-14 12:00:00',
                'dimensionId' => $article->getUuid(),
            ]);
        }
    }

    /**
     * @param ArticleData $data
     */
    private static function createArticle(
        array $data = [],
    ): ArticleInterface {
        $data = \array_merge([
            'title' => 'Example Article',
            'url' => '/example-article-' . \uniqid(),
            'template' => 'article',
            'locale' => 'en',
            'mainWebspace' => 'sulu-io',
            'customizeWebspaceSettings' => true,
        ], $data);

        $messageBus = self::getContainer()->get('sulu_message_bus');

        $envelope = $messageBus->dispatch(new Envelope(new CreateArticleMessage($data), [new EnableFlushStamp()]));
        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        /** @var ArticleInterface $article */
        $article = $handledStamps[0]->getResult();
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionArticleMessage(
                    identifier: ['uuid' => $article->getUuid()],
                    locale: $data['locale'],
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                ),
                [new EnableFlushStamp()],
            ),
        );

        return $article;
    }

    private static function copyLocaleArticle(
        string $identifier,
        string $sourceLocale,
        string $targetLocale,
    ): void {
        $messageBus = self::getContainer()->get('sulu_message_bus');

        $envelope = $messageBus->dispatch(new Envelope(new CopyLocaleArticleMessage(identifier: ['uuid' => $identifier], sourceLocale: $sourceLocale, targetLocale: $targetLocale), [new EnableFlushStamp()]));
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionArticleMessage(
                    identifier: ['uuid' => $identifier],
                    locale: $targetLocale,
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                ),
                [new EnableFlushStamp()],
            ),
        );
    }

    /**
     * @param ArticleData $data
     */
    private static function modifyArticle(
        string $identifier,
        array $data,
    ): ArticleInterface {
        $data = \array_merge([
            'title' => 'Example Article',
            'url' => '/example-article-' . \uniqid(),
            'template' => 'article',
            'locale' => 'en',
            'mainWebspace' => 'blog',
        ], $data);

        $messageBus = self::getContainer()->get('sulu_message_bus');

        $envelope = $messageBus->dispatch(new Envelope(new ModifyArticleMessage(identifier: ['uuid' => $identifier], data: $data), [new EnableFlushStamp()]));
        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        /** @var ArticleInterface $article */
        $article = $handledStamps[0]->getResult();
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionArticleMessage(
                    identifier: ['uuid' => $article->getUuid()],
                    locale: $data['locale'],
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                ),
                [new EnableFlushStamp()],
            ),
        );

        return $article;
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}

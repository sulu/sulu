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

namespace Sulu\Article\Tests\Functional\Infrastructure\Sulu\Content;

use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Article\Application\Message\ApplyWorkflowTransitionArticleMessage;
use Sulu\Article\Application\Message\CreateArticleMessage;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Sulu\Content\Application\SmartResolver\Resolver\SmartContentSmartResolver;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * The default limit must not cap the total of a paginated smart content.
 */
class ArticleSmartContentPaginationTest extends SuluTestCase
{
    private const ARTICLE_COUNT = 105;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();

        for ($i = 0; $i < self::ARTICLE_COUNT; ++$i) {
            self::createArticle(['title' => 'Article ' . $i]);
        }
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: bool}>
     */
    public static function pageProvider(): array
    {
        return [
            'page_10' => [10, 10, true],
            'page_11' => [11, 5, false],
        ];
    }

    #[DataProvider('pageProvider')]
    public function testPaginationBeyondDefaultLimit(int $page, int $expectedCount, bool $expectedHasNextPage): void
    {
        /** @var SmartContentSmartResolver $smartResolver */
        $smartResolver = $this->getContainer()->get('sulu_content.smart_content_smart_resolver');

        $result = $smartResolver->resolve(new SmartResolvable(
            data: [
                'value' => [],
                'filters' => [
                    'categories' => [],
                    'categoryOperator' => 'OR',
                    'websiteCategories' => [],
                    'websiteCategoryOperator' => 'OR',
                    'tags' => [],
                    'tagOperator' => 'OR',
                    'websiteTags' => [],
                    'websiteTagOperator' => 'OR',
                    'types' => [],
                    'typesOperator' => 'OR',
                    'locale' => 'en',
                    'dataSource' => null,
                    'limit' => null,
                    'page' => $page,
                    'maxPerPage' => 10,
                    'includeSubFolders' => false,
                    'excludeDuplicates' => false,
                ],
                'sortBys' => [],
                'parameters' => ['provider' => ArticleInterface::RESOURCE_KEY],
            ],
            resourceLoaderKey: 'smart_content',
            priority: 0,
        ));

        /** @var array<int, ResolvableResource> $content */
        $content = $result->getContent();
        $this->assertCount($expectedCount, $content);

        /** @var array{total: int, hasNextPage: bool, maxPage: int} $view */
        $view = $result->getView();
        $this->assertSame(self::ARTICLE_COUNT, $view['total']);
        $this->assertSame(11, $view['maxPage']);
        $this->assertSame($expectedHasNextPage, $view['hasNextPage']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function createArticle(array $data): void
    {
        $data = \array_merge([
            'url' => 'example-article-' . \uniqid(),
            'template' => 'article',
            'locale' => 'en',
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
                    locale: 'en',
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                ),
                [new EnableFlushStamp()],
            ),
        );
    }
}

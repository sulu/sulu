<?php

declare(strict_types=1);

namespace Sulu\Article\Tests\Functional\Infrastructure\Sulu\Content;

use Sulu\Article\Application\Message\ApplyWorkflowTransitionArticleMessage;
use Sulu\Article\Application\Message\CreateArticleMessage;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

class ArticleSmartContentProviderTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateTagTrait;
    use HandleTrait;

    private readonly SmartContentProviderInterface $smartContentProvider;

    protected function setUp(): void
    {
        parent::setUp();
        self::purgeDatabase();

        $this->messageBus = $this->getContainer()->get('sulu_message_bus');
        $this->smartContentProvider = $this->getContainer()->get('sulu_article.article_smart_content_provider');
    }

    public function testFindFlatByNoParameters(): void
    {
        $article = $this->createArticle(['title' => 'Example Article']);

        $result = $this->smartContentProvider->findFlatBy(['locale' => 'en'], []);

        $this->assertCount(1, $result);
        $this->assertSame($article->getUuid(), $result[0]['id']);
        $this->assertSame('Example Article', $result[0]['title']);

        $count = $this->smartContentProvider->countBy(['locale' => 'en']);
        $this->assertSame(1, $count);
    }

    public function testFindFlatByCategoryFilters(): void
    {
        $category1 = $this->createCategory(['en' => ['title' => 'Category 1']]);
        $category2 = $this->createCategory(['en' => ['title' => 'Category 2']]);
        $this->getEntityManager()->flush();

        $article1 = $this->createArticle(['title' => 'Cat1', 'excerptCategories' => [$category1->getId()]]);
        $article2 = $this->createArticle(['title' => 'Cat2', 'excerptCategories' => [$category2->getId()]]);
        $article3 = $this->createArticle(['title' => 'Cat3', 'excerptCategories' => [$category1->getId(), $category2->getId()]]);

        // OR
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'categoryIds' => [$category2->getId()],
            'categoryOperator' => 'OR',
        ], []);
        $this->assertCount(2, $result);
        $this->assertSame($article2->getUuid(), $result[0]['id']);
        $this->assertSame($article3->getUuid(), $result[1]['id']);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'categoryIds' => [$category2->getId()],
                'categoryOperator' => 'OR',
            ]),
        );

        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'categoryIds' => [$category1->getId(), $category2->getId()],
            'categoryOperator' => 'OR',
        ], ['title' => 'asc']);

        $this->assertCount(3, $result);
        $this->assertSame($article1->getUuid(), $result[0]['id']);
        $this->assertSame($article2->getUuid(), $result[1]['id']);
        $this->assertSame($article3->getUuid(), $result[2]['id']);
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'categoryIds' => [$category1->getId(), $category2->getId()],
                'categoryOperator' => 'OR',
            ]),
        );

        // AND
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'categoryIds' => [$category2->getId()],
            'categoryOperator' => 'AND',
        ], []);
        $this->assertCount(2, $result);
        $this->assertSame($article2->getUuid(), $result[0]['id']);
        $this->assertSame($article3->getUuid(), $result[1]['id']);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'categoryIds' => [$category2->getId()],
                'categoryOperator' => 'AND',
            ]),
        );

        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'categoryIds' => [$category1->getId(), $category2->getId()],
            'categoryOperator' => 'AND',
        ], []);

        $this->assertCount(1, $result);
        $this->assertSame($article3->getUuid(), $result[0]['id']);
        $this->assertSame(
            1,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'categoryIds' => [$category1->getId(), $category2->getId()],
                'categoryOperator' => 'AND',
            ]),
        );
    }

    /**
     * Test filtering by tagIds and tagNames.
     */
    public function testFindFlatByTagFilters(): void
    {
        $article1 = $this->createArticle(['title' => 'Tag1', 'excerptTags' => ['tag1']]);
        $article2 = $this->createArticle(['title' => 'Tag2', 'excerptTags' => ['tag2']]);
        $article3 = $this->createArticle(['title' => 'Tag3', 'excerptTags' => ['tag1', 'tag2']]);

        // OR
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'tagNames' => ['tag1'],
            'tagOperator' => 'OR',
        ], []);
        $this->assertCount(2, $result);
        $this->assertSame($article1->getUuid(), $result[0]['id']);
        $this->assertSame($article3->getUuid(), $result[1]['id']);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'tagNames' => ['tag1'],
                'tagOperator' => 'OR',
            ]),
        );

        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'tagNames' => ['tag1', 'tag2'],
            'tagOperator' => 'OR',
        ], ['title' => 'asc']);

        $this->assertCount(3, $result);
        $this->assertSame($article1->getUuid(), $result[0]['id']);
        $this->assertSame($article2->getUuid(), $result[1]['id']);
        $this->assertSame($article3->getUuid(), $result[2]['id']);
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'tagNames' => ['tag1', 'tag2'],
                'tagOperator' => 'OR',
            ]),
        );

        // AND
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'tagNames' => ['tag2'],
            'tagOperator' => 'AND',
        ], []);
        $this->assertCount(2, $result);
        $this->assertSame($article2->getUuid(), $result[0]['id']);
        $this->assertSame($article3->getUuid(), $result[1]['id']);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'tagNames' => ['tag2'],
                'tagOperator' => 'AND',
            ]),
        );

        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'tagNames' => ['tag1', 'tag2'],
            'tagOperator' => 'AND',
        ], []);

        $this->assertCount(1, $result);
        $this->assertSame($article3->getUuid(), $result[0]['id']);
        $this->assertSame(
            1,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'tagNames' => ['tag1', 'tag2'],
                'tagOperator' => 'AND',
            ]),
        );
    }

    public function testFindFlatByCategoryAndTag(): void
    {
        $category1 = $this->createCategory(['en' => ['title' => 'Category 1']]);
        $this->getEntityManager()->flush();

        $article1 = $this->createArticle(['title' => 'A', 'excerptCategories' => [$category1->getId()], 'excerptTags' => ['tag1']]);
        $article2 = $this->createArticle(['title' => 'B', 'excerptCategories' => [$category1->getId()]]);
        $article3 = $this->createArticle(['title' => 'C', 'excerptTags' => ['tag1']]);

        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'categoryIds' => [$category1->getId()],
            'tagNames' => ['tag1'],
        ], []);
        $this->assertCount(1, $result);
        $this->assertSame($article1->getUuid(), $result[0]['id']);
        $this->assertSame(
            1,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'categoryIds' => [$category1->getId()],
                'tagNames' => ['tag1'],
            ]),
        );
    }

    public function testFindFlatByLimitAndPage(): void
    {
        $this->createArticle(['title' => 'A']);
        $this->createArticle(['title' => 'B']);
        $this->createArticle(['title' => 'C']);

        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'limit' => 2,
            'page' => 1,
        ], [
            'sortBy' => 'title',
            'sortMethod' => 'asc',
        ]);
        $this->assertCount(2, $result);
        $this->assertSame('A', $result[0]['title']);
        $this->assertSame('B', $result[1]['title']);
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'limit' => 2,
                'page' => 1,
            ]),
        );

        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'limit' => 2,
            'page' => 2,
        ], [
            'sortBy' => 'title',
            'sortMethod' => 'asc',
        ]);
        $this->assertCount(1, $result);
        $this->assertSame('C', $result[0]['title']);
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'limit' => 2,
                'page' => 2,
            ]),
        );
    }

    public function testSortByTitle(): void
    {
        $this->createArticle(['title' => 'B']);
        $this->createArticle(['title' => 'A']);
        $this->createArticle(['title' => 'C']);

        // ASC
        $result = $this->smartContentProvider->findFlatBy(['locale' => 'en'], [
            'sortBy' => 'title',
            'sortMethod' => 'asc',
        ]);
        $this->assertCount(3, $result);
        $this->assertSame('A', $result[0]['title']);
        $this->assertSame('B', $result[1]['title']);
        $this->assertSame('C', $result[2]['title']);

        $count = $this->smartContentProvider->countBy(['locale' => 'en']);
        $this->assertSame(3, $count);

        // DESC
        $result = $this->smartContentProvider->findFlatBy(['locale' => 'en'], [
            'sortBy' => 'title',
            'sortMethod' => 'desc',
        ]);
        $this->assertCount(3, $result);
        $this->assertSame('C', $result[0]['title']);
        $this->assertSame('B', $result[1]['title']);
        $this->assertSame('A', $result[2]['title']);

        $count = $this->smartContentProvider->countBy(['locale' => 'en']);
        $this->assertSame(3, $count);
    }

    public function testSortByAuthored(): void
    {
        $this->createArticle(['title' => 'B', 'locale' => 'en', 'authored' => '2023-10-01T12:00:00+00:00']);
        $this->createArticle(['title' => 'A', 'locale' => 'en', 'authored' => '2023-09-01T12:00:00+00:00']);
        $this->createArticle(['title' => 'C', 'locale' => 'en', 'authored' => '2023-11-01T12:00:00+00:00']);

        // ASC
        $result = $this->smartContentProvider->findFlatBy(['locale' => 'en'], [
            'sortBy' => 'authored',
            'sortMethod' => 'asc',
        ]);
        $this->assertCount(3, $result);
        $this->assertSame('A', $result[0]['title']);
        $this->assertSame('B', $result[1]['title']);
        $this->assertSame('C', $result[2]['title']);

        // DESC
        $result = $this->smartContentProvider->findFlatBy(['locale' => 'en'], [
            'sortBy' => 'authored',
            'sortMethod' => 'desc',
        ]);
        $this->assertCount(3, $result);
        $this->assertSame('C', $result[0]['title']);
        $this->assertSame('B', $result[1]['title']);
        $this->assertSame('A', $result[2]['title']);
    }


    /**
     * @param array{
     *     title?: string,
     *     url?: string,
     *     template?: string,
     *     locale?: string,
     *     excerptCategories?: int[],
     *     excerptTags?: string[],
     *     author?: int|null,
     *     authored?: string|null,
     * } $data
     */
    private function createArticle(
        array $data = [],
    ): ArticleInterface {
        $data = \array_merge([
            'title' => 'Example Article',
            'url' => 'example-article-' . \uniqid(),
            'template' => 'article',
            'locale' => 'en',
        ], $data);

        /** @var ArticleInterface $article */
        $article = $this->handle(new Envelope(new CreateArticleMessage($data), [new EnableFlushStamp()]));

        $this->handle(
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
}

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

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\Trash;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Application\Mapper\ArticleMapperInterface;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Infrastructure\Sulu\Trash\ArticleTrashItemHandler;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItem;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorGeneratorInterface;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorRequest;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

class ArticleTrashItemHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testRestoreMakesPageTreeRouteSuffixUnique(): void
    {
        $article = new Article('article-123');

        $articleRepository = $this->prophesize(ArticleRepositoryInterface::class);
        $articleRepository->findOneBy(['uuid' => 'article-123'])->willReturn($article);

        $resourceLocatorGenerator = $this->prophesize(ResourceLocatorGeneratorInterface::class);
        $resourceLocatorGenerator->generate(Argument::that(
            static fn (ResourceLocatorRequest $request): bool => 'en' === $request->locale
                && null === $request->webspace
                && 'article-123' === $request->resourceId
                && null === $request->parentResourceId
                && '/blog/my-article' === $request->routeSchema
                && !$request->relative,
        ))->willReturn('/blog/my-article-1');

        $routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $routeRepository->findOneBy(['locale' => 'en', 'resourceKey' => 'pages', 'resourceId' => 'page-123'])
            ->willReturn(new Route('pages', 'page-123', 'en', '/blog/', 'sulu-io'));

        $articleMapper = $this->prophesize(ArticleMapperInterface::class);
        $articleMapper->mapArticleData($article, [
            'locale' => 'en',
            'url' => [
                'page' => ['uuid' => 'page-123', 'path' => '/old-blog'],
                'suffix' => '/my-article-1',
            ],
        ])->shouldBeCalled();

        $handler = new ArticleTrashItemHandler(
            $this->prophesize(TrashItemRepositoryInterface::class)->reveal(),
            $articleRepository->reveal(),
            $this->prophesize(ContentNormalizerInterface::class)->reveal(),
            $this->prophesize(ContentMergerInterface::class)->reveal(),
            [$articleMapper->reveal()],
            $this->prophesize(DomainEventCollectorInterface::class)->reveal(),
            $resourceLocatorGenerator->reveal(),
            $routeRepository->reveal(),
        );

        $trashItem = new TrashItem();
        $trashItem->setResourceId('article-123');
        $trashItem->setRestoreData([
            'dimensionContents' => [
                [
                    'locale' => 'en',
                    'url' => [
                        'page' => ['uuid' => 'page-123', 'path' => '/old-blog'],
                        'suffix' => 'my-article',
                    ],
                ],
            ],
        ]);

        $handler->restore($trashItem);
    }
}

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

namespace Sulu\Article\Infrastructure\Sulu\Content\ResourceLoader;

use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderContentViewEnhancementInterface;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class ArticleResourceLoader implements ResourceLoaderContentViewEnhancementInterface
{
    public const RESOURCE_LOADER_KEY = 'article';

    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * @param string[] $ids
     */
    public function load(array $ids, ?string $locale, array $params = []): array
    {
        if (null === $locale) {
            return [];
        }

        $result = $this->articleRepository->findBy(
            [
                'uuids' => $ids,
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            [],
            [ArticleRepositoryInterface::GROUP_SELECT_ARTICLE_WEBSITE => true]
        );

        $mappedResult = [];
        foreach ($result as $article) {
            $mappedResult[$article->getId()] = $article;
        }

        return $mappedResult;
    }

    public function resolveContentViewEnhancement(mixed $resource): ContentView
    {
        $view = [];
        $content = [];

        if ($resource instanceof ArticleDimensionContentInterface) {
            $view = [
                'uuid' => $resource->getResource()->getUuid(),
                'template' => $resource->getTemplateKey(),
                'mainWebspace' => $resource->getMainWebspace(),
                'additionalWebspaces' => $resource->getAdditionalWebspaces(),
            ];
        }

        if ($resource instanceof AuthorInterface) {
            $content = [
                'authored' => $resource->getAuthored()?->format('c'),
                'lastModified' => $resource->getLastModified()?->format('c'),
            ];
        }

        return ContentView::create($content, $view);
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}

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

use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class ArticleResourceLoader implements ResourceLoaderInterface
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
        $result = $this->articleRepository->findBy(['uuids' => $ids]);

        $mappedResult = [];
        foreach ($result as $article) {
            $mappedResult[$article->getId()] = $article;
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}

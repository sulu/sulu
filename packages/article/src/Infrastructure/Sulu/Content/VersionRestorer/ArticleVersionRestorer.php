<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Infrastructure\Sulu\Content\VersionRestorer;

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Application\ContentRestorer\ContentVersionRestorerInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\VersionInterface;

class ArticleVersionRestorer implements ContentVersionRestorerInterface
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private ContentCopierInterface $contentCopier
    ) {
    }

    /**
     * @return ContentRichEntityInterface<ArticleInterface>
     */
    public function restore(array $contentRichEntityIdentifier, int $version, array $options): ContentRichEntityInterface
    {
        $article = $this->articleRepository->getOneBy($contentRichEntityIdentifier);

        $dimensionContent = $this->contentCopier->copy(
            $article,
            [
                'stage' => $options['stage'] ?? DimensionContentInterface::STAGE_DRAFT,
                'locale' => $options['locale'] ?? null,
                'version' => $version,
            ],
            $article,
            [
                'stage' => $options['stage'] ?? DimensionContentInterface::STAGE_DRAFT,
                'locale' => $options['locale'] ?? null,
                'version' => VersionInterface::DEFAULT_VERSION,
            ],
            [
                'ignoredAttributes' => ['url'],
            ]
        );

        return $dimensionContent->getResource();
    }

    public function getType(): string
    {
        return ArticleInterface::RESOURCE_KEY;
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\VersionRestorer;

use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Application\ContentRestorer\ContentVersionRestorerInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\VersionInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

class PageVersionRestorer implements ContentVersionRestorerInterface
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentCopierInterface $contentCopier
    ) {
    }

    public function restore(array $contentRichEntityIdentifier, int $version, array $options): ContentRichEntityInterface
    {
        $page = $this->pageRepository->getOneBy($contentRichEntityIdentifier);

        $dimensionContent = $this->contentCopier->copy(
            $page,
            [
                'stage' => $options['stage'] ?? DimensionContentInterface::STAGE_DRAFT,
                'locale' => $options['locale'] ?? null,
                'version' => $version,
            ],
            $page,
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
        return PageInterface::RESOURCE_KEY;
    }
}

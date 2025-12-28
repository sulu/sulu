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

namespace Sulu\Snippet\Infrastructure\Sulu\Content\ResourceLoader;

use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class SnippetResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'snippet';

    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private ContentAggregatorInterface $contentAggregator,
        private ContentResolverInterface $contentResolver,
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

        $result = $this->snippetRepository->findBy(
            [
                'uuids' => $ids,
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            [],
            [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true]
        );

        $mappedResult = [];
        foreach ($result as $snippet) {
            // Aggregate the snippet with STAGE_LIVE to get the DimensionContent
            $dimensionContent = $this->contentAggregator->aggregate($snippet, [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ]);

            // Fully resolve the snippet content and return the resolved data structure.
            // This ensures snippets are always resolved with STAGE_LIVE, regardless of
            // the parent's stage (e.g., draft in preview mode), preventing
            // ContentNotFoundException when previewing pages with snippet_selection.
            $mappedResult[$snippet->getId()] = $this->contentResolver->resolve($dimensionContent);
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}

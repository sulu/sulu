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
            $mappedResult[$snippet->getId()] = $snippet;
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}

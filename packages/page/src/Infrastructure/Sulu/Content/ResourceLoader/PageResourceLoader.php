<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\ResourceLoader;

use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class PageResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'page';

    public function __construct(
        private PageRepositoryInterface $pageRepository,
    ) {
    }

    /**
     * @param string[] $ids
     */
    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $result = $this->pageRepository->findBy(['uuids' => $ids]);

        $mappedResult = [];
        foreach ($result as $page) {
            $mappedResult[$page->getId()] = $page;
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}

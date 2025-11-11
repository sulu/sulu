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

namespace Sulu\Snippet\Infrastructure\Sulu\Content\SmartResolver;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Sulu\Content\Application\SmartResolver\Resolver\SmartResolverInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;
use Sulu\Snippet\Infrastructure\Sulu\Content\ResourceLoader\SnippetResourceLoader;

/**
 * Resolves default snippet from snippet area when snippet selection has no selection.
 * Supports both single area key (string) and multiple area keys (array).
 *
 * @internal if you need to override this service, create a new service based on SmartResolverInterface instead of extending this class
 *
 * @final
 */
class SnippetAreaSmartResolver implements SmartResolverInterface
{
    public function __construct(
        private SnippetAreaRepositoryInterface $snippetAreaRepository,
        private RequestAnalyzerInterface $requestAnalyzer,
    ) {
    }

    public function resolve(SmartResolvable $resolvable, ?string $locale = null): ContentView
    {
        /**
         * @var array{
         *     areaKey: string|array<string>,
         * } $data
         */
        $data = $resolvable->getData();
        $areaKey = $data['areaKey'];

        $webspace = $this->requestAnalyzer->getWebspace();
        if (null === $webspace) { // @phpstan-ignore identical.alwaysFalse
            return \is_array($areaKey)
                ? ContentView::create([], ['ids' => []])
                : ContentView::create(null, []);
        }

        return \is_array($areaKey)
            ? $this->resolveMultipleAreas($areaKey, $webspace->getKey())
            : $this->resolveSingleArea($areaKey, $webspace->getKey());
    }

    private function resolveSingleArea(string $areaKey, string $webspaceKey): ContentView
    {
        $snippetId = $this->fetchSnippetId($areaKey, $webspaceKey);

        if (null === $snippetId) {
            return ContentView::create(null, []);
        }

        return ContentView::createResolvableWithReferences(
            id: $snippetId,
            resourceLoaderKey: SnippetResourceLoader::getKey(),
            resourceKey: SnippetInterface::RESOURCE_KEY,
            view: ['id' => $snippetId],
            priority: 100,
        );
    }

    /**
     * @param array<string> $areaKeys
     */
    private function resolveMultipleAreas(array $areaKeys, string $webspaceKey): ContentView
    {
        $snippetIds = $this->fetchSnippetIds($areaKeys, $webspaceKey);

        if ([] === $snippetIds) {
            return ContentView::create([], ['ids' => []]);
        }

        return ContentView::createResolvablesWithReferences(
            ids: $snippetIds,
            resourceLoaderKey: SnippetResourceLoader::getKey(),
            resourceKey: SnippetInterface::RESOURCE_KEY,
            view: ['ids' => $snippetIds],
            priority: 100,
        );
    }

    private function fetchSnippetId(string $areaKey, string $webspaceKey): ?string
    {
        return $this->snippetAreaRepository->findOneUuidBy([
            'webspaceKey' => $webspaceKey,
            'areaKey' => $areaKey,
        ]);
    }

    /**
     * @param array<string> $areaKeys
     *
     * @return array<string>
     */
    private function fetchSnippetIds(array $areaKeys, string $webspaceKey): array
    {
        $snippetIds = [];

        foreach ($areaKeys as $key) {
            $snippetId = $this->fetchSnippetId($key, $webspaceKey);

            if (null !== $snippetId) {
                $snippetIds[] = $snippetId;
            }
        }

        return $snippetIds;
    }

    public static function getType(): string
    {
        return 'snippet_area_default';
    }
}

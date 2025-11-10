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
 * Resolves default snippet from snippet area when SingleSnippetSelection has no selection.
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
         *     areaKey: string,
         * } $data
         */
        $data = $resolvable->getData();

        $areaKey = $data['areaKey'];

        $webspace = $this->requestAnalyzer->getWebspace();
        if (null === $webspace) { // @phpstan-ignore identical.alwaysFalse
            return ContentView::create(null, []);
        }
        $webspaceKey = $webspace->getKey();

        $snippetId = $this->snippetAreaRepository->findOneUuidBy([
            'webspaceKey' => $webspaceKey,
            'areaKey' => $areaKey,
        ]);

        if (null === $snippetId) {
            return ContentView::create(null, []);
        }

        return ContentView::createResolvableWithReferences(
            id: $snippetId,
            resourceLoaderKey: SnippetResourceLoader::getKey(),
            resourceKey: SnippetInterface::RESOURCE_KEY,
            view: [
                'id' => $snippetId,
            ],
            priority: 100,
        );
    }

    public static function getType(): string
    {
        return 'snippet_area_default';
    }
}

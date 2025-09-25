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

namespace Sulu\Snippet\Infrastructure\Symfony\Twig;

use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SnippetAreaTwigExtension extends AbstractExtension
{
    public function __construct(
        private SnippetAreaRepositoryInterface $snippetAreaRepository,
        private ContentAggregatorInterface $contentAggregator,
        private RequestAnalyzerInterface $requestAnalyzer,
        private ReferenceStoreInterface $referenceStore,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_snippet_load_by_area', [$this, 'loadSnippetByArea']),
        ];
    }

    public function loadSnippetByArea(
        string $areaKey,
        ?string $webspaceKey = null,
        ?string $locale = null
    ): ?SnippetDimensionContentInterface {
        if (null === $webspaceKey) {
            $webspace = $this->requestAnalyzer->getWebspace();
            if (null === $webspace) { // @phpstan-ignore identical.alwaysFalse
                return null;
            }
            $webspaceKey = $webspace->getKey();
        }

        if (null === $locale) {
            $localization = $this->requestAnalyzer->getCurrentLocalization();
            if (null === $localization) { // @phpstan-ignore identical.alwaysFalse
                return null;
            }
            $locale = $localization->getLocale();
        }

        $snippetArea = $this->snippetAreaRepository->findOneBy([
            'webspaceKey' => $webspaceKey,
            'areaKey' => $areaKey,
        ]);

        if (null === $snippetArea || null === $snippetArea->getSnippet()) {
            return null;
        }

        $snippet = $snippetArea->getSnippet();
        /** @var SnippetDimensionContentInterface $content */
        $content = $this->contentAggregator->aggregate(
            $snippet,
            [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ]
        );

        $this->referenceStore->add($snippet->getUuid(), SnippetInterface::RESOURCE_KEY);

        return $content;
    }
}

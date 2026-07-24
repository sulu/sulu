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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SnippetAreaTwigExtension extends AbstractExtension
{
    public function __construct(
        private SnippetAreaRepositoryInterface $snippetAreaRepository,
        private SnippetRepositoryInterface $snippetRepository,
        private ContentAggregatorInterface $contentAggregator,
        private RequestAnalyzerInterface $requestAnalyzer,
        private ReferenceStoreInterface $referenceStore,
        private ContentResolverInterface $contentResolver,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_snippet_load_by_area', [$this, 'loadSnippetByArea']),
        ];
    }

    /**
     * @param array<string, string>|null $properties
     *
     * @return array<string, mixed>|null
     */
    public function loadSnippetByArea(
        string $areaKey,
        ?array $properties = null,
        ?string $webspaceKey = null,
        ?string $locale = null,
    ): ?array {
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

        return $this->loadSnippetByAreaForLocale(
            $areaKey,
            $properties,
            $webspaceKey,
            $locale,
            $locale,
            []
        );
    }

    /**
     * @param array<string, string>|null $properties
     * @param array<string, true> $visitedLocales
     *
     * @return array<string, mixed>|null
     */
    private function loadSnippetByAreaForLocale(
        string $areaKey,
        ?array $properties,
        string $webspaceKey,
        string $locale,
        string $requestedLocale,
        array $visitedLocales,
    ): ?array {
        // The only recursive call site below already guards against revisiting a locale,
        // so $locale here is always unvisited; no need to re-check on entry.
        $visitedLocales[$locale] = true;

        $snippetArea = $this->snippetAreaRepository->findOneBy([
            'webspaceKey' => $webspaceKey,
            'areaKey' => $areaKey,
        ]);

        if (null === $snippetArea || null === $snippetArea->getSnippet()) {
            return null;
        }

        $snippet = $this->snippetRepository->findOneBy(
            [
                'uuid' => $snippetArea->getSnippet()->getUuid(),
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ],
            [
                SnippetRepositoryInterface::SELECT_SNIPPET_CONTENT => [
                    DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
                ],
            ]
        );

        if (null === $snippet) {
            return null;
        }

        /** @var SnippetDimensionContentInterface $dimensionContent */
        $dimensionContent = $this->contentAggregator->aggregate(
            $snippet,
            [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ]
        );

        if (null === $dimensionContent->getLocale()) {
            return null;
        }

        if ($shadowLocale = $dimensionContent->getShadowLocale()) {
            if (isset($visitedLocales[$shadowLocale])) {
                return null;
            }

            // Detach to bypass identity map: recursive call must load a fresh Snippet whose
            // dimensionContents collection is for the source locale, not this shadow locale.
            $this->entityManager->detach($snippet);

            return $this->loadSnippetByAreaForLocale(
                $areaKey,
                $properties,
                $webspaceKey,
                $shadowLocale,
                $requestedLocale,
                $visitedLocales
            );
        }

        if ($locale !== $requestedLocale) {
            // The source content supplies the shadow area's data, but nested resources must
            // still be resolved in the locale requested by the caller. Keep the source locale
            // as the fallback for nested resources which do not exist in that locale.
            $dimensionContent->setLocale($requestedLocale);
            $dimensionContent->setShadowLocale($locale);
        }

        $resolvedContent = $this->contentResolver->resolve($dimensionContent, $properties);

        $this->referenceStore->add($snippet->getUuid(), SnippetInterface::RESOURCE_KEY);

        return $resolvedContent;
    }
}

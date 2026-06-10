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
            // Detach the entity so the recursive findOneBy() creates a fresh instance whose
            // dimensionContents collection is populated for the source locale, not the shadow
            // locale. Without detach the identity map would return this same entity and
            // aggregate() would never find the source locale's live dimension content.
            $this->entityManager->detach($snippet);

            return $this->loadSnippetByArea($areaKey, $properties, $webspaceKey, $shadowLocale);
        }

        $resolvedContent = $this->contentResolver->resolve($dimensionContent, $properties);

        $this->referenceStore->add($snippet->getUuid(), SnippetInterface::RESOURCE_KEY);

        return $resolvedContent;
    }
}

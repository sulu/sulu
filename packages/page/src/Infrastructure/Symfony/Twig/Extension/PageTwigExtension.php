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

namespace Sulu\Page\Infrastructure\Symfony\Twig\Extension;

use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageTwigExtension extends AbstractExtension
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentAggregatorInterface $contentAggregator,
        private RequestAnalyzerInterface $requestAnalyzer,
        private ReferenceStoreInterface $referenceStore,
        private ContentResolverInterface $contentResolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_page_load', [$this, 'loadPage']),
        ];
    }

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>|null
     */
    public function loadPage(
        string $uuid,
        array $properties,
        ?string $locale = null,
    ): ?array {
        if (null === $locale) {
            $localization = $this->requestAnalyzer->getCurrentLocalization();
            if (null === $localization) { // @phpstan-ignore identical.alwaysFalse
                return null;
            }
            $locale = $localization->getLocale();
        }

        $page = $this->pageRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        if (null === $page) {
            return null;
        }

        /** @var PageDimensionContentInterface $dimensionContent */
        $dimensionContent = $this->contentAggregator->aggregate(
            $page,
            [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ]
        );

        $resolvedContent = $this->contentResolver->resolve($dimensionContent, $properties);

        $this->referenceStore->add($page->getUuid(), PageInterface::RESOURCE_KEY);

        return $resolvedContent;
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Symfony\Twig\Extension;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NavigationTwigExtension extends AbstractExtension
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentAggregatorInterface $contentAggregator,
        private ContentResolverInterface $contentResolver,
        private RequestAnalyzerInterface $requestAnalyzer
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_navigation_root_flat', [$this, 'flatRootNavigationFunction']),
            new TwigFunction('sulu_navigation_root_tree', [$this, 'treeRootNavigationFunction']),
            //            new TwigFunction('sulu_navigation_flat', [$this, 'flatNavigationFunction']),
            //            new TwigFunction('sulu_navigation_tree', [$this, 'treeNavigationFunction']),
            //            new TwigFunction('sulu_breadcrumb', [$this, 'breadcrumbFunction']),
            //            new TwigFunction('sulu_navigation_is_active', [$this, 'navigationIsActiveFunction']),
        ];
    }

    /**
     * @return array<string, mixed>[]
     */
    public function flatRootNavigationFunction(string $navigationContext, int $depth = 1, bool $loadExcerpt = false): array
    {
        $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        $pages = $this->pageRepository->findBy([
            'navigationContexts' => [$navigationContext],
            'depth' => $depth,
            'webspaceKey' => $webspaceKey,
        ]);

        if ([] === $pages) {
            return [];
        }

        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        $result = [];

        /** @var PageInterface $page */
        foreach ($pages as $page) {
            $content = $this->resolvePageContent($page, $locale);
            $result[] = $this->normalizePageContent($content, $loadExcerpt);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>[]
     */
    public function treeRootNavigationFunction(string $navigationContext, int $depth = 1, bool $loadExcerpt = false): array
    {
        $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        $pages = $this->pageRepository->findByAsTree([
            'navigationContexts' => [$navigationContext],
            'depth' => $depth,
            'webspaceKey' => $webspaceKey,
        ]);

        if ([] === $pages) {
            return [];
        }

        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();

        return $this->normalizePageTree($pages, $loadExcerpt, $locale);
    }

    /**
     * @param iterable<PageInterface> $pages
     *
     * @return array<string, mixed>[]
     */
    private function normalizePageTree(iterable $pages, bool $loadExcerpt, string $locale): array
    {
        $result = [];

        foreach ($pages as $page) {
            $content = $this->resolvePageContent($page, $locale);
            $normalizedContent = $this->normalizePageContent($content, $loadExcerpt);
            $normalizedContent['children'] = $this->normalizePageTree($page->getChildren(), $loadExcerpt, $locale);

            $result[] = $normalizedContent;
        }

        return $result;
    }

    /**
     * @return array{
     *      resource: object,
     *      content: mixed,
     *      view: mixed[],
     *      extension: array<string, array<string, mixed>>,
     * }
     */
    private function resolvePageContent(PageInterface $page, string $locale): array
    {
        $contentDimension = $this->contentAggregator->aggregate($page, [
            'locale' => $locale,
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        return $this->contentResolver->resolve($contentDimension);
    }

    /**
     * @param array{
     *      resource: object,
     *      content: mixed,
     *      view: mixed[],
     *      extension: array<string, array<string, mixed>>,
     *  } $content
     *
     * @return array<string, mixed>
     */
    private function normalizePageContent(array $content, bool $loadExcerpt): array
    {
        /** @var array{
         *      extension: array<string, array<string, mixed>>,
         * } $contentData
         */
        $contentData = $content['content'];
        $result = [...$contentData];

        if ($loadExcerpt) {
            $result['excerpt'] = $content['extension']['excerpt'];
        }

        return $result;
    }
}

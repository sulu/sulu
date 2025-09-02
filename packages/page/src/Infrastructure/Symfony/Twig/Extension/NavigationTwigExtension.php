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
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @final
 *
 * @internal This class is internal and should not be extended or overwritten.
 *           You can create an own Twig Extension to override the behaviour.
 */
class NavigationTwigExtension extends AbstractExtension
{
    public function __construct(
        private NavigationRepositoryInterface $navigationRepository,
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
        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();

        return $this->navigationRepository->getNavigationFlat(
            $navigationContext,
            $locale,
            $webspaceKey,
            $depth,
            ['loadExcerpt' => $loadExcerpt]
        );
    }

    /**
     * @return array<string, mixed>[]
     */
    public function treeRootNavigationFunction(string $navigationContext, int $depth = 1, bool $loadExcerpt = false): array
    {
        $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();

        return $this->navigationRepository->getNavigationTree(
            $navigationContext,
            $locale,
            $webspaceKey,
            $depth,
            ['excerpt' => $loadExcerpt]
        );
    }
}

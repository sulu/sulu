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
            new TwigFunction('sulu_navigation_flat', [$this, 'flatNavigationFunction']),
            new TwigFunction('sulu_navigation_tree', [$this, 'treeNavigationFunction']),
            new TwigFunction('sulu_breadcrumb', [$this, 'breadcrumbFunction']),
            new TwigFunction('sulu_navigation_is_active', [$this, 'navigationIsActiveFunction']),
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

    /**
     * @return array<string, mixed>[]
     */
    public function flatNavigationFunction(
        string $uuid,
        ?string $context = null,
        int $depth = 1,
        bool $loadExcerpt = false,
        ?int $level = null
    ): array {
        $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();

        // Handle level parameter: get breadcrumb and use UUID at specified level
        if (null !== $level) {
            $breadcrumb = $this->navigationRepository->getBreadcrumb(
                $uuid,
                $locale,
                $webspaceKey,
                []
            );

            if (!isset($breadcrumb[$level])) {
                return [];
            }

            $levelUuid = $breadcrumb[$level]['uuid'] ?? $breadcrumb[$level]['id'] ?? null;
            if (!\is_string($levelUuid) || '' === $levelUuid) {
                return [];
            }

            $uuid = $levelUuid;
        }

        return $this->navigationRepository->getNavigationFlatByUuid(
            $uuid,
            $locale,
            $webspaceKey,
            $depth,
            $context,
            ['excerpt' => $loadExcerpt]
        );
    }

    /**
     * @return array<string, mixed>[]
     */
    public function treeNavigationFunction(
        string $uuid,
        ?string $context = null,
        int $depth = 1,
        bool $loadExcerpt = false,
        ?int $level = null
    ): array {
        $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();

        // Handle level parameter: get breadcrumb and use UUID at specified level
        if (null !== $level) {
            $breadcrumb = $this->navigationRepository->getBreadcrumb(
                $uuid,
                $locale,
                $webspaceKey,
                []
            );

            if (!isset($breadcrumb[$level])) {
                return [];
            }

            $levelUuid = $breadcrumb[$level]['uuid'] ?? $breadcrumb[$level]['id'] ?? null;
            if (!\is_string($levelUuid) || '' === $levelUuid) {
                return [];
            }

            $uuid = $levelUuid;
        }

        return $this->navigationRepository->getNavigationTreeByUuid(
            $uuid,
            $locale,
            $webspaceKey,
            $depth,
            $context,
            ['excerpt' => $loadExcerpt]
        );
    }

    /**
     * @return array<string, mixed>[]
     */
    public function breadcrumbFunction(string $uuid): array
    {
        $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();

        return $this->navigationRepository->getBreadcrumb(
            $uuid,
            $locale,
            $webspaceKey,
            []
        );
    }

    public function navigationIsActiveFunction(string $requestPath, string $itemPath): bool
    {
        if ($requestPath === $itemPath) {
            return true;
        }

        return (bool) \preg_match(\sprintf('/%s([\/]|$)/', \preg_quote($itemPath, '/')), $requestPath);
    }
}

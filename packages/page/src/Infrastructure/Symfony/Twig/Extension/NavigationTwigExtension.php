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

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Segment;
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
        private RequestAnalyzerInterface $requestAnalyzer,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_page_navigation_root_flat', [$this, 'flatRootNavigationFunction']),
            new TwigFunction('sulu_page_navigation_root_tree', [$this, 'treeRootNavigationFunction']),
            new TwigFunction('sulu_page_navigation_flat', [$this, 'flatNavigationFunction']),
            new TwigFunction('sulu_page_navigation_tree', [$this, 'treeNavigationFunction']),
            new TwigFunction('sulu_page_breadcrumb', [$this, 'breadcrumbFunction']),
            new TwigFunction('sulu_page_navigation_is_active', [$this, 'navigationIsActiveFunction']),
        ];
    }

    /**
     * @param array<string, string>|null $properties
     *
     * @return array<string, mixed>[]
     */
    public function flatRootNavigationFunction(string $navigationContext, int $depth = 1, ?array $properties = null): array
    {
        $webspace = $this->requestAnalyzer->getWebspace();
        $localization = $this->requestAnalyzer->getCurrentLocalization();

        if (null === $webspace || null === $localization) {
            return [];
        }

        $webspaceKey = $webspace->getKey();
        $locale = $localization->getLocale();
        /** @var Segment|null $segment */
        $segment = $this->requestAnalyzer->getSegment();

        return $this->navigationRepository->getNavigationFlat(
            $navigationContext,
            $locale,
            $webspaceKey,
            $segment?->getKey(),
            $depth,
            \array_merge($this->getDefaultProperties(), $properties ?? []),
        );
    }

    /**
     * @param array<string, string>|null $properties
     *
     * @return array<string, mixed>[]
     */
    public function treeRootNavigationFunction(string $navigationContext, int $depth = 1, ?array $properties = null): array
    {
        $webspace = $this->requestAnalyzer->getWebspace();
        $localization = $this->requestAnalyzer->getCurrentLocalization();

        if (null === $webspace || null === $localization) {
            return [];
        }

        $webspaceKey = $webspace->getKey();
        $locale = $localization->getLocale();
        /** @var Segment|null $segment */
        $segment = $this->requestAnalyzer->getSegment();

        return $this->navigationRepository->getNavigationTree(
            $navigationContext,
            $locale,
            $webspaceKey,
            $segment?->getKey(),
            $depth,
            \array_merge($this->getDefaultProperties(), $properties ?? []),
        );
    }

    /**
     * @param array<string, string>|null $properties
     *
     * @return array<string, mixed>[]
     */
    public function flatNavigationFunction(
        string $uuid,
        ?string $context = null,
        int $depth = 1,
        ?int $level = null,
        ?array $properties = null
    ): array {
        $webspace = $this->requestAnalyzer->getWebspace();
        $localization = $this->requestAnalyzer->getCurrentLocalization();

        if (null === $webspace || null === $localization) {
            return [];
        }

        $webspaceKey = $webspace->getKey();
        $locale = $localization->getLocale();

        // Handle level parameter: get breadcrumb and use UUID at specified level
        if (null !== $level) {
            $breadcrumb = $this->navigationRepository->getBreadcrumb(
                $uuid,
                $locale,
                $webspaceKey,
                \array_merge($this->getDefaultProperties(), $properties ?? []),
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
            \array_merge($this->getDefaultProperties(), $properties ?? []),
        );
    }

    /**
     * @param array<string, string>|null $properties
     *
     * @return array<string, mixed>[]
     */
    public function treeNavigationFunction(
        string $uuid,
        ?string $context = null,
        int $depth = 1,
        ?int $level = null,
        ?array $properties = null
    ): array {
        $webspace = $this->requestAnalyzer->getWebspace();
        $localization = $this->requestAnalyzer->getCurrentLocalization();

        if (null === $webspace || null === $localization) {
            return [];
        }

        $webspaceKey = $webspace->getKey();
        $locale = $localization->getLocale();

        // Handle level parameter: get breadcrumb and use UUID at specified level
        if (null !== $level) {
            $breadcrumb = $this->navigationRepository->getBreadcrumb(
                $uuid,
                $locale,
                $webspaceKey,
                \array_merge($this->getDefaultProperties(), $properties ?? []),
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
            \array_merge($this->getDefaultProperties(), $properties ?? []),
        );
    }

    /**
     * @param array<string, string>|null $properties
     *
     * @return array<string, mixed>[]
     */
    public function breadcrumbFunction(string $uuid, ?array $properties = null): array
    {
        $webspace = $this->requestAnalyzer->getWebspace();
        $localization = $this->requestAnalyzer->getCurrentLocalization();

        if (null === $webspace || null === $localization) {
            return [];
        }

        $webspaceKey = $webspace->getKey();
        $locale = $localization->getLocale();

        return $this->navigationRepository->getBreadcrumb(
            $uuid,
            $locale,
            $webspaceKey,
            \array_merge($this->getDefaultProperties(), $properties ?? []),
        );
    }

    public function navigationIsActiveFunction(string $requestPath, string $itemPath): bool
    {
        if ($requestPath === $itemPath) {
            return true;
        }

        return (bool) \preg_match(\sprintf('/%s([\/]|$)/', \preg_quote($itemPath, '/')), $requestPath);
    }

    /**
     * @return array<string, string>
     */
    private function getDefaultProperties(): array
    {
        return [
            'title' => 'title',
            'url' => 'url',
            'targetType' => 'object.linkData[provider]',
        ];
    }
}

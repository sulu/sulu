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

namespace Sulu\CustomUrl\Application\Routing;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Model\CustomUrlRouteInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRouteRepositoryInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Route\Application\Routing\Matcher\RouteCollectionForRequestLoaderInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads routes for custom URLs directly from CustomUrlRoute entities.
 * Handles redirect, canonical, noFollow, noIndex properties and history routes.
 */
final readonly class CustomUrlRouteCollectionLoader implements RouteCollectionForRequestLoaderInterface
{
    public function __construct(
        private CustomUrlRouteRepositoryInterface $customUrlRouteRepository,
        private PageRepositoryInterface $pageRepository,
        private RouteRepositoryInterface $routeRepository,
        private ContentAggregatorInterface $contentAggregator,
        private MetadataProviderRegistry $metadataProviderRegistry,
        private WebspaceManagerInterface $webspaceManager,
        private string $environment,
    ) {
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $collection = new RouteCollection();

        $url = $this->buildUrlFromRequest($request);

        $customUrlRoute = $this->customUrlRouteRepository->findOneBy(['path' => $url]);
        if (!$customUrlRoute) {
            return $collection;
        }

        if ($customUrlRoute->isHistory()) {
            return $this->handleHistoryRoute($request, $customUrlRoute, $collection);
        }

        $customUrl = $customUrlRoute->getCustomUrl();
        if (!$customUrl->isPublished()) {
            return $collection;
        }

        if ($normalized = $this->normalizeTrailingSlash($request)) {
            $collection->add(
                'custom_url_trailing_slash_' . $customUrl->getUuid(),
                $normalized,
            );

            return $collection;
        }

        if ($normalized = $this->normalizeTrailingHtml($request)) {
            $collection->add(
                'custom_url_trailing_html_' . $customUrl->getUuid(),
                $normalized,
            );

            return $collection;
        }

        if ($customUrl->isRedirect() || null === $customUrl->getTargetDocument()) {
            return $this->handleRedirect($request, $customUrl, $collection);
        }

        return $this->handleContent($request, $customUrl, $collection);
    }

    private function buildUrlFromRequest(Request $request): string
    {
        $pathInfo = $request->getPathInfo();

        $lastSlashPos = \strrpos($pathInfo, '/');
        $lastSegment = false !== $lastSlashPos ? \substr($pathInfo, $lastSlashPos + 1) : $pathInfo;

        $dotPos = \strrpos($lastSegment, '.');
        if (false !== $dotPos) {
            $pathInfo = (false !== $lastSlashPos ? \substr($pathInfo, 0, $lastSlashPos + 1) : '')
                . \substr($lastSegment, 0, $dotPos);
        }

        return \rtrim($request->getHost() . $pathInfo, '/');
    }

    private function normalizeTrailingSlash(Request $request): ?SymfonyRoute
    {
        $uri = $request->getRequestUri();

        if ('/' === $uri || !\str_ends_with($uri, '/')) {
            return null;
        }

        return new SymfonyRoute(
            $this->decodePathInfo($request->getPathInfo()),
            [
                '_controller' => RedirectController::class . '::urlRedirectAction',
                'path' => \substr($request->getUri(), 0, -1),
                'permanent' => true,
            ],
        );
    }

    private function normalizeTrailingHtml(Request $request): ?SymfonyRoute
    {
        $uri = $request->getRequestUri();

        if (!\str_ends_with($uri, '.html')) {
            return null;
        }

        return new SymfonyRoute(
            $this->decodePathInfo($request->getPathInfo()),
            [
                '_controller' => RedirectController::class . '::urlRedirectAction',
                'path' => \substr($request->getUri(), 0, -5),
                'permanent' => true,
            ],
        );
    }

    private function handleHistoryRoute(
        Request $request,
        CustomUrlRouteInterface $customUrlRoute,
        RouteCollection $collection,
    ): RouteCollection {
        $targetRoute = $customUrlRoute->getTargetRoute();

        if (!$targetRoute) {
            return $collection;
        }

        $targetCustomUrl = $targetRoute->getCustomUrl();

        if ($targetCustomUrl->isRedirect() || null === $targetCustomUrl->getTargetDocument()) {
            return $this->handleRedirect($request, $targetCustomUrl, $collection);
        }

        $scheme = $request->getScheme();
        $targetUrl = \sprintf('%s://%s', $scheme, $targetRoute->getPath());

        $requestFormat = $request->getRequestFormat(null);
        if ($requestFormat) {
            $targetUrl .= '.' . $requestFormat;
        }

        $collection->add(
            'custom_url_history_redirect_' . $customUrlRoute->getUuid(),
            new SymfonyRoute(
                $this->decodePathInfo($request->getPathInfo()),
                [
                    '_controller' => RedirectController::class . '::urlRedirectAction',
                    'path' => $targetUrl,
                    'permanent' => true,
                ],
            ),
        );

        return $collection;
    }

    private function handleRedirect(
        Request $request,
        CustomUrlInterface $customUrl,
        RouteCollection $collection,
    ): RouteCollection {
        $slug = '/';
        if (null !== $customUrl->getTargetDocument()) {
            $slug = $this->loadSlugForDocument(
                $customUrl->getTargetDocument(),
                $customUrl->getTargetLocale(),
            );
        }

        // Build redirect URL using WebspaceManager
        $url = $this->webspaceManager->findUrlByResourceLocator(
            $slug,
            $this->environment,
            $customUrl->getTargetLocale(),
            $customUrl->getWebspace(),
            $request->getHost(),
            $request->getScheme(),
        );

        // Add format extension
        $requestFormat = $request->getRequestFormat(null);
        if ($requestFormat) {
            $url .= '.' . $requestFormat;
        }

        // Add query string
        $queryString = $request->getQueryString();
        if ($queryString) {
            $url .= '?' . $queryString;
        }

        $collection->add(
            'custom_url_redirect_' . $customUrl->getUuid(),
            new SymfonyRoute(
                $this->decodePathInfo($request->getPathInfo()),
                [
                    '_controller' => RedirectController::class . '::urlRedirectAction',
                    'path' => $url,
                    'permanent' => true,
                ],
            ),
        );

        return $collection;
    }

    private function handleContent(
        Request $request,
        CustomUrlInterface $customUrl,
        RouteCollection $collection,
    ): RouteCollection {
        $targetDocument = $customUrl->getTargetDocument();
        if (!$targetDocument) {
            return $collection;
        }

        $targetLocale = $customUrl->getTargetLocale();
        $page = $this->loadPageEntity($targetDocument, $targetLocale);
        if (!$page) {
            return $collection;
        }

        $dimensionContent = $this->loadDimensionContent($page, $targetLocale);
        if (!$dimensionContent instanceof TemplateInterface) {
            return $collection;
        }

        $templateKey = $dimensionContent->getTemplateKey();
        if (!$templateKey) {
            return $collection;
        }

        $templateMetadata = $this->resolveTemplateMetadata(
            PageInterface::TEMPLATE_TYPE,
            $templateKey,
            $customUrl->getTargetLocale(),
        );

        if (!$templateMetadata instanceof TemplateMetadata) {
            return $collection;
        }

        $view = $templateMetadata->getView();
        $controller = $templateMetadata->getController();

        if (!$view || !$controller) {
            return $collection;
        }

        $defaults = [
            'object' => $dimensionContent,
            'view' => $view,
            '_controller' => $controller,
            '_customUrl' => $customUrl,
            '_seo' => $this->buildSeoDefaults($request, $customUrl),
        ];

        $collection->add(
            'custom_url_content_' . $customUrl->getUuid(),
            new SymfonyRoute(
                $this->decodePathInfo($request->getPathInfo()),
                $defaults,
                [],
                ['utf8' => true],
                $request->getHost(),
            ),
        );

        return $collection;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSeoDefaults(
        Request $request,
        CustomUrlInterface $customUrl,
    ): array {
        $seo = [
            'noFollow' => $customUrl->isNoFollow(),
            'noIndex' => $customUrl->isNoIndex(),
        ];

        if ($customUrl->isCanonical() && $customUrl->getTargetDocument()) {
            $slug = $this->loadSlugForDocument($customUrl->getTargetDocument(), $customUrl->getTargetLocale());

            $seo['canonicalUrl'] = $this->webspaceManager->findUrlByResourceLocator(
                $slug,
                $this->environment,
                $customUrl->getTargetLocale(),
                $customUrl->getWebspace(),
                $request->getHost(),
                $request->getScheme(),
            );
        }

        return $seo;
    }

    private function loadSlugForDocument(string $documentUuid, string $locale): string
    {
        try {
            /** @var Route|null $route */
            $route = $this->routeRepository->findOneBy([
                'resourceId' => $documentUuid,
                'locale' => $locale,
            ]);

            return $route ? $route->getSlug() : '/';
        } catch (\Exception $e) {
            return '/';
        }
    }

    private function loadPageEntity(string $uuid, string $locale): ?PageInterface
    {
        try {
            /** @var PageInterface|null $page */
            $page = $this->pageRepository->findOneBy(
                [
                    'uuid' => $uuid,
                    'locale' => $locale,
                    'stage' => DimensionContentInterface::STAGE_LIVE,
                    'version' => DimensionContentInterface::CURRENT_VERSION,
                ],
                [
                    PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                        DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
                    ],
                ]
            );

            return $page;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return PageDimensionContentInterface|null
     */
    private function loadDimensionContent(PageInterface $page, string $locale): ?DimensionContentInterface
    {
        try {
            return $this->contentAggregator->aggregate(
                $page,
                [
                    'locale' => $locale,
                    'stage' => DimensionContentInterface::STAGE_LIVE,
                    'version' => DimensionContentInterface::CURRENT_VERSION,
                ],
            );
        } catch (\Exception) {
            return null;
        }
    }

    private function resolveTemplateMetadata(string $type, string $templateKey, string $locale): mixed
    {
        $typedMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($type, $locale, []);

        if (!$typedMetadata instanceof TypedFormMetadata) {
            throw new \RuntimeException(\sprintf('Could not find metadata "%s" of type "%s".', 'form', $type));
        }

        $metadata = $typedMetadata->getForms()[$templateKey] ?? null;

        if (!$metadata instanceof FormMetadata) {
            throw new \RuntimeException(\sprintf('Could not find form metadata "%s" of type "%s".', $templateKey, $type));
        }

        $templateMetadata = $metadata->getTemplate();

        if (!$templateMetadata instanceof TemplateMetadata) {
            throw new \RuntimeException(\sprintf('Could not find template metadata "%s" of type "%s".', $templateKey, $type));
        }

        return $templateMetadata;
    }

    private function decodePathInfo(string $pathInfo): string
    {
        return \rawurldecode($pathInfo);
    }
}

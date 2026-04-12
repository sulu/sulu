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

namespace Sulu\Page\Infrastructure\Sulu\Route;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\CacheLifetimeMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestStore;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\ExternalLinkProvider;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkUrlTrait;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\LinkInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Application\Routing\Matcher\RouteDefaultsProviderInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal this class is internal and should not be extended or relied on in custom code
 */
class PageRouteDefaultsProvider implements RouteDefaultsProviderInterface
{
    use LinkUrlTrait;

    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentAggregatorInterface $contentAggregator,
        private MetadataProviderRegistry $metadataProviderRegistry,
        private CacheLifetimeResolverInterface $cacheLifetimeResolver,
        private RouteRepositoryInterface $routeRepository,
        private RouteGeneratorInterface $routeGenerator,
        private bool $audienceTargetingEnabled = false,
        private ?WebspaceManagerInterface $webspaceManager = null,
        private ?RequestStack $requestStack = null,
        private string $environment = 'prod',
    ) {
    }

    public function getDefaults(Route $route): array
    {
        $id = $route->getResourceId();
        $locale = $route->getLocale();
        $dimensionAttributes = [
            'locale' => $locale,
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ];

        $page = $this->pageRepository->findOneBy(
            [
                'uuid' => $id,
            ],
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'dimensionAttributes' => $dimensionAttributes,
                    'selects' => $this->getContentSelects(),
                ],
            ],
        );

        if (null === $page) {
            throw new NotFoundHttpException(\sprintf('No page found for id "%s" and locale "%s"', $id, $locale));
        }

        try {
            $dimensionContent = $this->contentAggregator->aggregate($page, $dimensionAttributes);
        } catch (ContentNotFoundException $exception) {
            throw new NotFoundHttpException(\sprintf('No page found for id "%s" and locale "%s"', $id, $locale), $exception);
        }

        $contentLocale = $dimensionContent->getLocale();
        if (!$contentLocale) {
            throw new NotFoundHttpException(\sprintf('No page found for id "%s" and locale "%s"', $id, $locale));
        }

        $redirectDefaults = $this->resolveRedirectDefaults($dimensionContent, $contentLocale);
        if (null !== $redirectDefaults) {
            return $redirectDefaults;
        }

        $templateKey = $dimensionContent->getTemplateKey();
        if (!$templateKey) {
            throw new NotFoundHttpException(\sprintf('No template found for id "%s" and locale "%s"', $id, $locale));
        }

        $templateMetadata = $this->resolveTemplateMetadata($dimensionContent::getTemplateType(), $templateKey, $contentLocale);

        $defaults = [
            'object' => $dimensionContent,
            'view' => $templateMetadata->getView(),
            '_controller' => $templateMetadata->getController(),
        ];

        $cacheLifetime = $this->getCacheLifetime($templateMetadata);
        if (null !== $cacheLifetime) {
            $defaults[CacheLifetimeRequestStore::ATTRIBUTE_KEY] = $cacheLifetime;
        }

        $seoData = $this->getSeoData($dimensionContent, $page->getWebspaceKey(), $route);
        if ($seoData) {
            $defaults['_seo'] = $seoData;
        }

        return $defaults;
    }

    /**
     * @param DimensionContentInterface<PageInterface> $dimensionContent
     *
     * @return array{_controller: class-string<RedirectController>, path: string, permanent: true, _sulu_route_target?: Route}|null
     */
    private function resolveRedirectDefaults(DimensionContentInterface $dimensionContent, string $locale): ?array
    {
        if (!$dimensionContent instanceof LinkInterface) {
            return null;
        }

        $linkData = $dimensionContent->getLinkData();
        if (!\is_array($linkData)) {
            return null;
        }

        $provider = $linkData['provider'] ?? null;
        $href = $linkData['href'] ?? null;

        if (!\is_string($provider) || !\is_string($href)) {
            return null;
        }

        if (ExternalLinkProvider::ALIAS === $provider) {
            return [
                '_controller' => RedirectController::class,
                'path' => $this->appendQueryAndAnchor($href, $linkData),
                'permanent' => true,
            ];
        }

        if (PageLinkProvider::ALIAS !== $provider) {
            return null;
        }

        $targetRoute = $this->routeRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $href,
            'locale' => $locale,
        ]);

        if (!$targetRoute instanceof Route) {
            throw new NotFoundHttpException(\sprintf('No linked target route found for page "%s" and locale "%s"', $href, $locale));
        }

        $url = $this->routeGenerator->generate(
            $targetRoute->getSlug(),
            $targetRoute->getLocale(),
            $targetRoute->getWebspace(),
        );

        return [
            '_controller' => RedirectController::class,
            'path' => $this->appendQueryAndAnchor($url, $linkData),
            'permanent' => true,
            '_sulu_route_target' => $targetRoute,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function getContentSelects(): array
    {
        $selects = [
            DimensionContentQueryEnhancer::SELECT_EXCERPT_TAGS => true,
            DimensionContentQueryEnhancer::SELECT_EXCERPT_CATEGORIES => true,
            DimensionContentQueryEnhancer::SELECT_EXCERPT_CATEGORIES_TRANSLATION => true,
        ];

        if ($this->audienceTargetingEnabled) {
            $selects[DimensionContentQueryEnhancer::SELECT_EXCERPT_AUDIENCE_TARGET_GROUPS] = true;
        }

        return $selects;
    }

    private function getCacheLifetime(TemplateMetadata $templateMetadata): ?int
    {
        $cacheLifetime = $templateMetadata->getCacheLifetime();
        if (!$cacheLifetime instanceof CacheLifetimeMetadata) {
            return null;
        }

        $cacheLifeTimeType = $cacheLifetime->getType();
        $cacheLifeTimeValue = $cacheLifetime->getValue();

        if (!$this->cacheLifetimeResolver->supports($cacheLifeTimeType, $cacheLifeTimeValue)) {
            throw new \InvalidArgumentException(\sprintf('Invalid cacheLifeTime in route default provider: %s', \json_encode([
                'type' => $cacheLifeTimeType,
                'value' => $cacheLifeTimeValue,
            ], flags: \JSON_THROW_ON_ERROR)));
        }

        return $this->cacheLifetimeResolver->resolve($cacheLifeTimeType, $cacheLifeTimeValue);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getSeoData(PageDimensionContentInterface $dimensionContent, string $webspaceKey, Route $route): ?array
    {
        // Skip auto-canonical when the editor has set one manually.
        $editorCanonical = $dimensionContent->getSeoCanonicalUrl();
        if (null !== $editorCanonical && '' !== $editorCanonical) {
            return null;
        }

        $locale = $dimensionContent->getLocale();
        if (!$locale) {
            return null;
        }

        if (null === $this->webspaceManager || null === $this->requestStack) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();

        // Shadow content canonicalizes to the shadow target locale.
        $canonicalLocale = $dimensionContent->getShadowLocale() ?? $locale;

        $canonicalUrl = $this->webspaceManager->findUrlByResourceLocator(
            $route->getSlug(),
            $this->environment,
            $canonicalLocale,
            $webspaceKey,
            $request?->getHost(),
            $request?->getScheme(),
        );

        if (!$canonicalUrl) {
            return null;
        }

        return [
            'canonicalUrl' => $canonicalUrl,
        ];
    }

    private function resolveTemplateMetadata(string $type, string $templateKey, string $locale): TemplateMetadata
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
}

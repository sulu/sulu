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

namespace Sulu\Article\Infrastructure\Sulu\Route;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Article\Application\Webspace\WebspaceResolver;
use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Sulu\Route\ContentRouteDefaultsProvider;
use Sulu\Route\Application\Routing\Matcher\RouteDefaultsProviderInterface;
use Sulu\Route\Domain\Model\Route;

/**
 * Route defaults provider for articles that extends the content provider with SEO support.
 */
class ArticleRouteDefaultsProvider extends ContentRouteDefaultsProvider implements RouteDefaultsProviderInterface
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ContentAggregatorInterface $contentAggregator,
        MetadataProviderRegistry $metadataProviderRegistry,
        CacheLifetimeResolverInterface $cacheLifetimeResolver,
        private WebspaceManagerInterface $webspaceManager,
        private WebspaceResolver $webspaceResolver,
        private string $environment = 'prod',
    ) {
        parent::__construct($entityManager, $contentAggregator, $metadataProviderRegistry, $cacheLifetimeResolver);
    }

    public function getDefaults(Route $route): array
    {
        $defaults = parent::getDefaults($route);

        // Add SEO data if object supports additional webspaces
        if (isset($defaults['object'])
            && $defaults['object'] instanceof AdditionalWebspacesInterface
        ) {
            $seoData = $this->getSeoData($defaults['object'], $route);
            if ($seoData) {
                $defaults['_seo'] = $seoData;
            }
        }

        return $defaults;
    }

    /**
     * Get SEO data including canonical URL for the article.
     *
     * @return array<string, mixed>|null
     */
    private function getSeoData(AdditionalWebspacesInterface $dimensionContent, Route $route): ?array
    {
        if (!$dimensionContent instanceof DimensionContentInterface) {
            return null;
        }

        $locale = $dimensionContent->getLocale();
        if (!$locale) {
            return null;
        }

        $mainWebspace = $this->webspaceResolver->resolveMainWebspace($dimensionContent, $locale);
        if (!$mainWebspace) {
            return null;
        }

        $canonicalUrl = $this->webspaceManager->findUrlByResourceLocator(
            $route->getSlug(),
            $this->environment,
            $locale,
            $mainWebspace,
        );

        if (!$canonicalUrl) {
            return null;
        }

        return [
            'canonicalUrl' => $canonicalUrl,
        ];
    }
}

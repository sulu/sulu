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

namespace Sulu\Article\Infrastructure\Sulu\Routing;

use Sulu\Article\Application\Webspace\WebspaceResolver;
use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Domain\Model\RoutableInterface;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Route enhancer for articles that adds SEO data including canonical URLs.
 */
class ArticleRouteEnhancer implements RouteEnhancerInterface
{
    public function __construct(
        private WebspaceManagerInterface $webspaceManager,
        private WebspaceResolver $webspaceResolver,
        private string $environment = 'prod',
    ) {
    }

    public function enhance(array $defaults, Request $request): array
    {
        if (!$this->shouldAddCanonicalTag($defaults, $request)) {
            return $defaults;
        }

        $article = $defaults['object'];
        $locale = $article->getLocale();
        
        if (!$locale) {
            return $defaults;
        }

        $mainWebspace = $this->webspaceResolver->resolveMainWebspace($article, $locale);
        if (!$mainWebspace) {
            return $defaults;
        }

        // Get the route from the request attributes
        $suluRoute = $request->attributes->get('_sulu_route');
        if (!$suluRoute) {
            return $defaults;
        }

        $canonicalUrl = $this->webspaceManager->findUrlByResourceLocator(
            $suluRoute->getSlug(),
            $this->environment,
            $locale,
            $mainWebspace,
        );

        if (!$canonicalUrl) {
            return $defaults;
        }

        $seo = ['canonicalUrl' => $canonicalUrl];

        return \array_merge($defaults, ['_seo' => $seo]);
    }

    /**
     * Checks if the enhancer should add a canonical tag to the route attributes.
     */
    private function shouldAddCanonicalTag(array $defaults, Request $request): bool
    {
        if (!\array_key_exists('object', $defaults)) {
            return false;
        }

        $article = $defaults['object'];
        if (!$article instanceof RoutableInterface || !$article instanceof AdditionalWebspacesInterface) {
            return false;
        }

        $sulu = $request->get('_sulu');
        if (!$sulu instanceof RequestAttributes) {
            return false;
        }

        /** @var Webspace|null $webspace */
        $webspace = $sulu->getAttribute('webspace');
        if (!$webspace) {
            return false;
        }

        $additionalWebspaces = $this->webspaceResolver->resolveAdditionalWebspaces($article, $article->getLocale() ?? 'en');
        if (!$additionalWebspaces || !\in_array($webspace->getKey(), $additionalWebspaces, true)) {
            return false;
        }

        return true;
    }
}
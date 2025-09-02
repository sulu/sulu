<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\Routing\Generator;

use Psr\Container\ContainerInterface;
use Sulu\Route\Domain\Exception\MissingRequestContextParameterException;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Translation\LocaleSwitcher;

final class RouteGenerator implements RouteGeneratorInterface
{
    public function __construct(
        private ContainerInterface $siteRouteGeneratorLocator,
        private RequestContext $requestContext,
        private RequestStack $requestStack,
        private LocaleSwitcher $localeSwitcher,
    ) {
    }

    public function generate(string $slug, ?string $locale = null, ?string $site = null): string
    {
        if (null === $site) {
            $requestSite = $this->requestContext->getParameter(RequestAttributeEnum::SITE->value);

            if (!\is_string($requestSite)) {
                $requestSite = $this->requestStack->getCurrentRequest()?->attributes->get(RequestAttributeEnum::SITE->value);
            }

            if (!\is_string($requestSite)) {
                throw new MissingRequestContextParameterException(RequestAttributeEnum::SITE->value);
            }

            $site = $requestSite;
        }

        if (null === $locale) {
            $requestLocale = $this->requestContext->getParameter('_locale');
            $locale = $requestLocale;

            if (!\is_string($locale)) {
                $locale = $this->localeSwitcher->getLocale();
            }
        }

        $siteRouteGenerator = null;
        if ($this->siteRouteGeneratorLocator->has($site)) {
            $siteRouteGenerator = $this->siteRouteGeneratorLocator->get($site);
        } elseif ($this->siteRouteGeneratorLocator->has('.default')) { // TODO discuss how we should handle this, currently a workaround to avoid register one per webspace
            $siteRouteGenerator = $this->siteRouteGeneratorLocator->get('.default');
        }

        \assert($siteRouteGenerator instanceof SiteRouteGeneratorInterface, 'The SiteRouteGenerator for "' . $site . '" must implement SiteRouteGeneratorInterface but got: ' . \get_debug_type($siteRouteGenerator));

        $generatedUrl = $siteRouteGenerator->generate($this->requestContext, $slug, $locale);

        $schemeAndHttpHost = \sprintf(
            '%s://%s%s/',
            $this->requestContext->getScheme(),
            $this->requestContext->getHost(),
            match ($this->requestContext->getScheme()) {
                'http' => 80 !== $this->requestContext->getHttpPort() ? ':' . $this->requestContext->getHttpPort() : '',
                'https' => 443 !== $this->requestContext->getHttpsPort() ? ':' . $this->requestContext->getHttpsPort() : '',
                default => throw new \RuntimeException('Invalid scheme: ' . $this->requestContext->getScheme()),
            },
        );

        if (\str_starts_with($generatedUrl, $schemeAndHttpHost)) {
            return \substr($generatedUrl, \strlen($schemeAndHttpHost) - 1);
        }

        return $generatedUrl;
    }
}

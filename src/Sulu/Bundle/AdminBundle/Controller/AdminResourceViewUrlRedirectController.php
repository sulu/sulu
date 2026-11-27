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

namespace Sulu\Bundle\AdminBundle\Controller;

use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Exception\ResourceViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Redirects the edit button on the website to the admin detail view of the page at the given
 * website url, or to the admin start page when there is no such view or the user cannot access it.
 *
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
final class AdminResourceViewUrlRedirectController
{
    public function __construct(
        private readonly WebspaceManagerInterface $webspaceManager,
        private readonly RouteRepositoryInterface $routeRepository,
        private readonly ResourceViewUrlGeneratorInterface $resourceViewUrlGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function redirectAction(Request $request): RedirectResponse
    {
        return new RedirectResponse($this->generateDetailUrl($request) ?? $this->urlGenerator->generate('sulu_admin'));
    }

    private function generateDetailUrl(Request $request): ?string
    {
        $route = $this->findRoute($request->query->getString('url'));
        if (null === $route) {
            return null;
        }

        $viewParameters = \array_filter([
            'id' => $route->getResourceId(),
            'locale' => $route->getLocale(),
            'webspace' => $route->getWebspace(),
        ], fn (?string $value) => null !== $value && '' !== $value);

        try {
            return $this->resourceViewUrlGenerator->generate($route->getResourceKey(), 'detail', $viewParameters);
        } catch (ResourceViewNotFoundException|ViewNotFoundException|ViewParameterNotFoundException) {
            return null;
        }
    }

    // Same lookup as a website request: portal by url, then route by webspace, locale and slug.
    private function findRoute(string $url): ?Route
    {
        $host = \parse_url($url, \PHP_URL_HOST);
        if (!\is_string($host) || '' === $host) {
            return null;
        }

        $path = \parse_url($url, \PHP_URL_PATH);
        $url = $host . (\is_string($path) ? $path : '');

        $portalInformation = $this->webspaceManager->findPortalInformationByUrl($url);
        if (!$portalInformation || RequestAnalyzerInterface::MATCH_TYPE_FULL !== $portalInformation->getType()) {
            return null;
        }

        // A url without "{localization}" serves the default localization of its portal.
        $locale = $portalInformation->getLocale() ?: $portalInformation->getPortal()->getDefaultLocalization()->getLocale();
        $slug = \substr($url, \strlen($portalInformation->getUrl()));

        return $this->routeRepository->findFirstBy([
            'webspaceOrNull' => $portalInformation->getWebspace()->getKey(),
            'locale' => $locale,
            'slug' => \rawurldecode('' !== $slug ? $slug : '/'),
        ], ['webspace' => 'desc']);
    }
}

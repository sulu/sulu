<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Contains redirect actions.
 */
class RedirectController extends Controller
{
    /**
     * Creates a redirect for configured webspaces.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function redirectWebspaceAction(Request $request)
    {
        $url = $this->resolveRedirectUrl(
            $request->get('redirect'),
            $request->getUri(),
            $request->get('_sulu')->getAttribute('resourceLocatorPrefix')
        );

        return new RedirectResponse($url, 301, ['Cache-Control' => 'private']);
    }

    /**
     * Creates a redirect for *.html to * (without html).
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function redirectAction(Request $request)
    {
        return new RedirectResponse($request->get('url'), 301, ['Cache-Control' => 'private']);
    }

    /**
     * Create a redirect response which uses a route to generate redirect.
     *
     * @param Request $request
     * @param string $route
     * @param bool $permanent
     *
     * @return RedirectResponse
     */
    public function redirectToRouteAction(Request $request, $route, $permanent = false)
    {
        if ('' === $route) {
            throw new HttpException($permanent ? 410 : 404);
        }

        $attributes = array_merge($request->attributes->get('_route_params'), $request->query->all());
        unset($attributes['route'], $attributes['permanent']);

        return new RedirectResponse(
            $this->container->get('router')->generate($route, $attributes, UrlGeneratorInterface::ABSOLUTE_URL),
            $permanent ? 301 : 302,
            ['Cache-Control' => 'private']
        );
    }

    /**
     * Resolve the redirect URL, appending any additional path data.
     *
     * @param string $redirectUrl Redirect webspace URI
     * @param string $requestUri The actual incoming request URI
     * @param string $resourceLocatorPrefix The prefix of the actual portal
     *
     * @return string URL to redirect to
     */
    private function resolveRedirectUrl($redirectUrl, $requestUri, $resourceLocatorPrefix)
    {
        $redirectInfo = $this->parseUrl($redirectUrl);
        $requestInfo = $this->parseUrl($requestUri);

        $url = sprintf('%s://%s', $requestInfo['scheme'], $requestInfo['host']);

        if (isset($redirectInfo['host'])) {
            $url = sprintf('%s://%s', $requestInfo['scheme'], $redirectInfo['host']);
        }

        if (isset($requestInfo['port'])) {
            $url .= ':' . $requestInfo['port'];
        }

        if (isset($redirectInfo['path'])
            && (
                // if requested url not starting with redirectUrl it need to be added
                !isset($requestInfo['path'])
                || strpos($requestInfo['path'], $redirectInfo['path'] . '/') !== 0
            )
        ) {
            $url .= $redirectInfo['path'];
        }

        if (isset($requestInfo['path']) && $resourceLocatorPrefix !== $requestInfo['path']) {
            $path = $requestInfo['path'];
            if (0 === strpos($path, $resourceLocatorPrefix)) {
                $path = substr($path, strlen($resourceLocatorPrefix));
            }

            $url .= $path;
            $url = rtrim($url, '/');
        }

        if (isset($requestInfo['query'])) {
            $url .= '?' . $requestInfo['query'];
        }

        if (isset($requestInfo['fragment'])) {
            $url .= '#' . $requestInfo['fragment'];
        }

        return $url;
    }

    /**
     * Prefix http to the URL if it is missing and
     * then parse the string using parse_url.
     *
     * @param string $url
     *
     * @return string
     */
    private function parseUrl($url)
    {
        if (!preg_match('{^https?://}', $url)) {
            $url = 'http://' . $url;
        }

        return parse_url($url);
    }
}

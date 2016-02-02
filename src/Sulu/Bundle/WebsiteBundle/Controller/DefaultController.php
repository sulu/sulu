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

use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default Controller for rendering templates, uses the themes from the ClientWebsiteBundle.
 */
class DefaultController extends WebsiteController
{
    /**
     * Loads the content from the request (filled by the route provider) and creates a response with this content and
     * the appropriate cache headers.
     *
     * @param \Sulu\Component\Content\Compat\StructureInterface $structure
     * @param bool $preview
     * @param bool $partial
     *
     * @return Response
     */
    public function indexAction(StructureInterface $structure, $preview = false, $partial = false)
    {
        $response = $this->renderStructure(
            $structure,
            [],
            $preview,
            $partial
        );

        return $response;
    }

    /**
     * Creates a redirect for configured webspaces.
     */
    public function redirectWebspaceAction(Request $request)
    {
        $url = $this->resolveRedirectUrl(
            $request->get('redirect'),
            $request->getUri()
        );

        return new RedirectResponse($url, 301);
    }

    /**
     * Creates a redirect for *.html to * (without html).
     */
    public function redirectAction(Request $request)
    {
        return new RedirectResponse($request->get('url'), 301);
    }

    /**
     * Resolve the redirect URL, appending any additional path data.
     *
     * @param string $redirectUrl Redirect webspace URI
     * @param string $requestUri The actual incoming request URI
     *
     * @return string URL to redirect to
     */
    protected function resolveRedirectUrl($redirectUrl, $requestUri)
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

        if (
            isset($redirectInfo['path'])
            && (
                // if requested url not starting with redirectUrl it need to be added
                !isset($requestInfo['path'])
                || strpos($requestInfo['path'], $redirectInfo['path'] . '/') !== 0
            )
        ) {
            $url .= $redirectInfo['path'];
        }

        if (isset($requestInfo['path'])) {
            $url .= $requestInfo['path'];
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
     * @param string
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

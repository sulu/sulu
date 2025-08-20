<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Contains redirect actions.
 */
class RedirectController
{
    /**
     * @var RouterInterface
     */
    protected $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Creates a redirect for *.html to * (without html).
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

        $attributes = \array_merge($request->attributes->get('_route_params'), $request->query->all());
        unset($attributes['route'], $attributes['permanent']);

        return new RedirectResponse(
            $this->router->generate($route, $attributes, UrlGeneratorInterface::ABSOLUTE_URL),
            $permanent ? 301 : 302,
            ['Cache-Control' => 'private']
        );
    }
}

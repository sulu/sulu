<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @deprecated Can be removed when Symfony 5.4 support is canceled. Is replaced by LogoutEventSubscriber.
 */
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        @trigger_deprecation('sulu/sulu', '2.5', __CLASS__ . '() is deprecated and will be removed in 3.0. Use LogoutEventSubscriber instead.');

        $this->router = $router;
    }

    /**
     * @return Response
     */
    public function onLogoutSuccess(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse(null, Response::HTTP_OK);
        } else {
            $response = new RedirectResponse($this->router->generate('sulu_admin'));
        }

        return $response;
    }
}

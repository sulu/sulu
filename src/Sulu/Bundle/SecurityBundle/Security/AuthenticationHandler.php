<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Called after a user gets authenticated at the admin firewall
 * Generates the response (either JSON or a Redirect depending on if the request is a XmlHttpRequest or not).
 */
class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Session
     */
    private $session;

    public function __construct(RouterInterface $router, Session $session)
    {
        $this->router = $router;
        $this->session = $session;
    }

    /**
     * Handler for AuthenticationSuccess. Returns a JsonResponse if request is an AJAX-request.
     * Returns a RedirectResponse otherwise.
     *
     * @param Request $request
     * @param TokenInterface $token
     *
     * @return Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        // get url to redirect (or return in the JSON-response)
        if ($this->session->get('_security.admin.target_path') &&
            strpos($this->session->get('_security.admin.target_path'), '#') !== false
        ) {
            $url = $this->session->get('_security.admin.target_path');
        } else {
            $url = $this->router->generate('sulu_admin');
        }

        if ($request->isXmlHttpRequest()) {
            // if AJAX login
            $array = ['url' => $url];
            $response = new JsonResponse($array, 200);
        } else {
            // if form login
            $response = new RedirectResponse($url);
        }

        return $response;
    }

    /**
     * Handler for AuthenticationFailure. Returns a JsonResponse if request is an AJAX-request.
     * Returns a Redirect-response otherwise.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->isXmlHttpRequest()) {
            // if AJAX login
            $array = ['message' => $exception->getMessage()];
            $response = new JsonResponse($array, 401);
        } else {
            // if form login
            // set authentication exception to session
            $this->session->set(SecurityContextInterface::AUTHENTICATION_ERROR, $exception);
            $response = new RedirectResponse($this->router->generate('sulu_admin.login'));
        }

        return $response;
    }
}

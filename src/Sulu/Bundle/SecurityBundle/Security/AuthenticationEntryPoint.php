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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Called when an AuthenticationException is thrown.
 * Returns a 401 status code for the API, and redirects to the login page for other requests.
 */
class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        if (strpos($request->getPathInfo(), '/admin/api') === 0 || $request->isXmlHttpRequest()) {
            $response = new Response('', 401);
        } else {
            $response = new RedirectResponse($this->urlGenerator->generate('sulu_admin.login'));
        }

        return $response;
    }
}

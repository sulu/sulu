<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Controller to render the login template or the reset-password template
 * Class SecurityController.
 */
class SecurityController extends Controller
{
    /**
     * Renders the login template.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        if (!$request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        if ($this->get('kernel')->getEnvironment() === 'dev') {
            $template = 'SuluAdminBundle:Security:login.html.twig';
        } else {
            $template = 'SuluAdminBundle:Security:login.html.dist.twig';
        }

        return $this->render(
            $template,
            [
                'name' => $this->container->getParameter('sulu_admin.name'),
                'locales' => $this->container->getParameter('sulu_core.locales'),
                'translated_locales' => $this->container->getParameter('sulu_core.translated_locales'),
                'translations' => $this->container->getParameter('sulu_core.translations'),
                'fallback_locale' => $this->container->getParameter('sulu_core.fallback_locale'),
            ]
        );
    }

    /**
     * Renderes the reset-password template.
     *
     * @param string $token the reset token
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetAction($token)
    {
        return $this->render(
            'SuluAdminBundle:Security:login.html.twig',
            [
                'name' => $this->container->getParameter('sulu_admin.name'),
                'locales' => $this->container->getParameter('sulu_core.locales'),
                'token' => $token,
            ]
        );
    }
}

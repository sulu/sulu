<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

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

        if (!$request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $session->remove(Security::AUTHENTICATION_ERROR);
        }

        return $this->render($this->getTemplate(), $this->getParameters());
    }

    /**
     * Renders the reset-password template.
     *
     * @param string $token the reset token
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetAction($token)
    {
        return $this->render($this->getTemplate(), array_merge($this->getParameters(), ['token' => $token]));
    }

    /**
     * Returns twig parameters.
     *
     * @return array
     */
    private function getParameters()
    {
        return [
            'name' => $this->container->getParameter('sulu_admin.name'),
            'locales' => $this->container->getParameter('sulu_core.locales'),
            'translated_locales' => $this->container->getParameter('sulu_core.translated_locales'),
            'translations' => $this->container->getParameter('sulu_core.translations'),
            'fallback_locale' => $this->container->getParameter('sulu_core.fallback_locale'),
        ];
    }

    /**
     * Returns template for environment.
     *
     * @return string
     */
    protected function getTemplate()
    {
        if ($this->get('kernel')->getEnvironment() === 'dev') {
            return 'SuluAdminBundle:Security:login.html.twig';
        }

        return 'SuluAdminBundle:Security:login.html.dist.twig';
    }
}

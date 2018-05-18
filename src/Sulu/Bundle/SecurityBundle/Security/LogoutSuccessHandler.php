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
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function onLogoutSuccess(Request $request)
    {
        //TODO: After removing old admin this if can be removed
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        return new RedirectResponse('/admin/');
    }
}

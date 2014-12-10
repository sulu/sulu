<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProfileController extends Controller
{
    public function changeLanguageAction(Request $request)
    {
        $user = $this->getUser();
        $user->setLocale($request->get('locale'));

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return new JsonResponse($user);
    }
}

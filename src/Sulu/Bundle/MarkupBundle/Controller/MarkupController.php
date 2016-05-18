<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller enables validation of markup for the ui.
 */
class MarkupController extends Controller
{
    /**
     * Provides validation result for given POST-Content.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function validateAction(Request $request)
    {
        $result = $this->get('sulu_markup.parser')->validate($request->getContent());

        return new JsonResponse(['valid' => $result->isValid(), 'content' => $result->getContent()]);
    }
}

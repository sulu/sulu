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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Handles http cache actions.
 */
class CacheController extends Controller
{
    /**
     * Clear the whole http_cache for website.
     *
     * @return JsonResponse
     */
    public function clearAction()
    {
        $this->get('sulu_website.http_cache.clearer')->clear();

        return new JsonResponse([], 200);
    }
}

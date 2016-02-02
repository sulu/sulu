<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{
    public function queryAction(Request $request)
    {
        $q = $request->get('query', '');

        if (strlen($q) <= 3) {
            throw new \Exception('Length of query string must be greater than 3 (Zend Search)');
        }

        $searchManager = $this->get('massive_search.search_manager');
        $hits = $searchManager->createSearch($q)->locale('de')->index('content');

        return $this->render('TestBundle:Search:query.html.twig', [
            'hits' => $hits,
        ]);
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Controller;

use Massive\Bundle\SearchBundle\Search\SearchManager;
use Symfony\Component\HttpFoundation\Request;

class SearchController
{
    /**
     * @var SearchManager
     */
    private $searchManager;

    public function __construct(SearchManager $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    public function queryAction(Request $request)
    {
        $q = $request->get('query', '');

        if (strlen($q) <= 3) {
            throw new \Exception('Length of query string must be greater than 3 (Zend Search)');
        }

        $hits = $this->searchManager->createSearch($q)->locale('de')->index('content');

        return $this->render('TestBundle:Search:query.html.twig', [
            'hits' => $hits,
        ]);
    }
}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Controller;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\View;

/**
 * Sulu search controller
 */
class SearchController
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var ViewHandler
     */
    private $viewHandler;

    /**
     * @param SearchManagerInterface $searchManager
     */
    public function __construct(
        SearchManagerInterface $searchManager,
        ViewHandler $viewHandler
    )
    {
        $this->searchManager = $searchManager;
        $this->viewHandler = $viewHandler;
    }

    /**
     * Perform a search and return a JSON response
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function searchAction(Request $request)
    {
        $query = $request->query->get('q');
        $index = $request->query->get('index') ? : null;
        $locale = $request->query->get('locale') ? : null;

        $query = $this->searchManager->createSearch($query);

        if ($locale) {
            $query->locale($locale);
        }

        if ($index) {
            $query->index($index);
        }

        $hits = $query->execute();

        $view = View::create($hits);

        return $this->viewHandler->handle($view);
    }

    /**
     * Return a JSON encoded scalar array of index names
     *
     * @return JsonResponse
     */
    public function listIndexesAction(Request $request)
    {
        $view = View::create($this->searchManager->getIndexNames());

        return $this->viewHandler->handleView($view);
    }
}

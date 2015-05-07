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
use JMS\Serializer\SerializationContext;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Sulu\Bundle\SearchBundle\Rest\SearchResultRepresentation;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;

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
     * @var ListRestHelper
     */
    private $listRestHelper;

    /**
     * @param SearchManagerInterface $searchManager
     */
    public function __construct(
        SearchManagerInterface $searchManager,
        ViewHandler $viewHandler,
        ListRestHelper $listRestHelper
    )
    {
        $this->searchManager = $searchManager;
        $this->viewHandler = $viewHandler;
        $this->listRestHelper = $listRestHelper;
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
        $category = $request->query->get('category', null);
        $locale = $request->query->get('locale', null);

        $page = $this->listRestHelper->getPage();
        $limit = $this->listRestHelper->getLimit();

        $query = $this->searchManager->createSearch($query);

        if ($locale) {
            $query->locale($locale);
        }

        if ($category) {
            $query->category($category);
        }

        $hits = $query->execute();

        $adapter = new ArrayAdapter($hits);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);

        $representation = new SearchResultRepresentation(
            new CollectionRepresentation($pager->getCurrentPageResults(), 'result'),
            'sulu_search_search',
            array(
                'locale' => $locale,
                'query' => $query,
                'category' => $category,
            ),
            (integer) $page,
            (integer) $limit,
            $pager->getNbPages(),
            'page',
            'limit',
            false,
            count($hits),
            $this->getCategoryTotals($hits)
        );

        $view = View::create($representation);
        $context = SerializationContext::create();
        $context->enableMaxDepthChecks();
        $context->setSerializeNull(true);
        $view->setSerializationContext($context);

        return $this->viewHandler->handle($view);
    }

    /**
     * Return a JSON encoded scalar array of index names
     *
     * @return JsonResponse
     */
    public function categoriesAction()
    {
        return $this->viewHandler->handle(
            View::create($this->searchManager->getCategoryNames())
        );
    }

    /**
     * Return the category totals for the search results
     *
     * @param Hit[]
     *
     * @return array
     */
    private function getCategoryTotals($hits)
    {
        $categoryNames = $this->searchManager->getCategoryNames();
        $categoryCount = array_combine(
            $categoryNames,
            array_fill(0, count($categoryNames), 0)
        );

        foreach ($hits as $hit) {
            $category = $hit->getDocument()->getCategory();
            $categoryCount[$category]++;
        }

        return $categoryCount;
    }
}

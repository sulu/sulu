<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Controller;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\SerializationContext;
use Massive\Bundle\SearchBundle\Search\Metadata\ProviderInterface;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Sulu\Bundle\SearchBundle\Rest\SearchResultRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sulu search controller.
 */
class SearchController
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var ProviderInterface
     */
    private $metadataProvider;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var ViewHandler
     */
    private $viewHandler;

    /**
     * @var ListRestHelper
     */
    private $listRestHelper;

    /**
     * @var array
     */
    private $indexConfig;

    /**
     * @param SearchManagerInterface $searchManager
     * @param ProviderInterface $metadataProvider
     * @param SecurityCheckerInterface $securityChecker
     * @param ViewHandler $viewHandler
     * @param ListRestHelper $listRestHelper
     */
    public function __construct(
        SearchManagerInterface $searchManager,
        ProviderInterface $metadataProvider,
        SecurityCheckerInterface $securityChecker,
        ViewHandler $viewHandler,
        ListRestHelper $listRestHelper,
        array $indexConfiguration
    ) {
        $this->searchManager = $searchManager;
        $this->metadataProvider = $metadataProvider;
        $this->securityChecker = $securityChecker;
        $this->viewHandler = $viewHandler;
        $this->listRestHelper = $listRestHelper;
        $this->indexConfig = $indexConfiguration;
    }

    /**
     * Perform a search and return a JSON response.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $queryString = $request->query->get('q');
        $index = $request->query->get('index', null);
        $locale = $request->query->get('locale', null);

        $page = $this->listRestHelper->getPage();
        $limit = $this->listRestHelper->getLimit();
        $aggregateHits = [];
        $startTime = microtime(true);

        $indexes = $index ? [$index] : $this->getAllowedIndexes();

        $query = $this->searchManager->createSearch($queryString);

        if ($locale) {
            $query->locale($locale);
        }

        $query->indexes($indexes);

        foreach ($query->execute() as $hit) {
            $aggregateHits[] = $hit;
        }

        $time = microtime(true) - $startTime;

        $adapter = new ArrayAdapter($aggregateHits);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);

        $representation = new SearchResultRepresentation(
            new CollectionRepresentation($pager->getCurrentPageResults(), 'result'),
            'sulu_search_search',
            [
                'locale' => $locale,
                'query' => $query,
                'index' => $index,
            ],
            (integer) $page,
            (integer) $limit,
            $pager->getNbPages(),
            'page',
            'limit',
            false,
            count($aggregateHits),
            $this->getIndexTotals($aggregateHits),
            number_format($time, 8)
        );

        $view = View::create($representation);
        $context = SerializationContext::create();
        $context->enableMaxDepthChecks();
        $context->setSerializeNull(true);
        $view->setSerializationContext($context);

        return $this->viewHandler->handle($view);
    }

    /**
     * Return a JSON encoded scalar array of index names.
     *
     * @return Response
     */
    public function indexesAction()
    {
        return $this->viewHandler->handle(
            View::create($this->getAllowedIndexes())
        );
    }

    /**
     * Return the category totals for the search results.
     *
     * @param Hit []
     *
     * @return array
     */
    private function getIndexTotals($hits)
    {
        $indexNames = $this->searchManager->getIndexNames();
        $indexCount = array_combine(
            $indexNames,
            array_fill(0, count($indexNames), 0)
        );

        foreach ($hits as $hit) {
            ++$indexCount[$hit->getDocument()->getIndex()];
        }

        return $indexCount;
    }

    /**
     * @return array
     */
    private function getAllowedIndexes()
    {
        $allowedIndexNames = [];
        $indexNames = $this->searchManager->getIndexNames();

        foreach ($indexNames as $indexName) {
            if (!(isset($this->indexConfig[$indexName]) && isset($this->indexConfig[$indexName]['security_context']))) {
                $allowedIndexNames[] = $indexName;
                continue;
            }

            if ($this->securityChecker->hasPermission($this->indexConfig[$indexName]['security_context'], 'view')) {
                $allowedIndexNames[] = $indexName;
            }
        }

        return $allowedIndexNames;
    }
}

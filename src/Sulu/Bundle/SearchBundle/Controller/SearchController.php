<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Controller;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\SerializationContext;
use Massive\Bundle\SearchBundle\Search\Metadata\ProviderInterface;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Sulu\Bundle\SearchBundle\Rest\SearchResultRepresentation;
use Sulu\Bundle\SearchBundle\Search\Configuration\IndexConfiguration;
use Sulu\Bundle\SearchBundle\Search\Configuration\IndexConfigurationProviderInterface;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
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
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    /**
     * @var ListRestHelperInterface
     */
    private $listRestHelper;

    /**
     * @var IndexConfigurationProviderInterface
     */
    private $indexConfigurationProvider;

    /**
     * @param SearchManagerInterface $searchManager
     * @param ProviderInterface $metadataProvider
     * @param SecurityCheckerInterface $securityChecker
     * @param ViewHandlerInterface $viewHandler
     * @param ListRestHelperInterface $listRestHelper
     * @param IndexConfigurationProviderInterface $indexConfigurationProvider
     */
    public function __construct(
        SearchManagerInterface $searchManager,
        ProviderInterface $metadataProvider,
        SecurityCheckerInterface $securityChecker,
        ViewHandlerInterface $viewHandler,
        ListRestHelperInterface $listRestHelper,
        IndexConfigurationProviderInterface $indexConfigurationProvider
    ) {
        $this->searchManager = $searchManager;
        $this->metadataProvider = $metadataProvider;
        $this->securityChecker = $securityChecker;
        $this->viewHandler = $viewHandler;
        $this->listRestHelper = $listRestHelper;
        $this->indexConfigurationProvider = $indexConfigurationProvider;
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
        $startTime = microtime(true);

        $indexes = $index ? [$index] : $this->getAllowedIndexes();

        $query = $this->searchManager->createSearch($queryString);

        if ($locale) {
            $query->locale($locale);
        }

        $query->indexes($indexes);
        $query->setLimit($limit);

        $time = microtime(true) - $startTime;

        $adapter = new ArrayAdapter(iterator_to_array($query->execute()));
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
            (int) $page,
            (int) $limit,
            $pager->getNbPages(),
            'page',
            'limit',
            false,
            $adapter->getNbResults(),
            $this->getIndexTotals($adapter->getArray()),
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
            View::create(
                array_map(
                    function ($indexName) {
                        $indexConfiguration = $this->indexConfigurationProvider->getIndexConfiguration($indexName);

                        return $indexConfiguration ?: new IndexConfiguration($indexName);
                    },
                    $this->getAllowedIndexes()
                )
            )
        );
    }

    /**
     * Return the category totals for the search results.
     *
     * @param Hit[]
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
            $indexConfiguration = $this->indexConfigurationProvider->getIndexConfiguration($indexName);
            if (!$indexConfiguration) {
                $allowedIndexNames[] = $indexName;
                continue;
            }

            $contexts = $indexConfiguration->getContexts();

            if ($this->securityChecker->hasPermission($indexConfiguration->getSecurityContext(), PermissionTypes::VIEW)
                && (empty($contexts) || array_search('admin', $contexts) !== false)
            ) {
                $allowedIndexNames[] = $indexName;
            }
        }

        return $allowedIndexNames;
    }
}

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

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller handles the search for the website.
 */
class WebsiteSearchController
{
    use RequestParametersTrait;

    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ParameterResolverInterface
     */
    private $parameterResolver;

    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @param SearchManagerInterface $searchManager
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param ParameterResolverInterface $parameterResolver
     * @param EngineInterface $engine
     */
    public function __construct(
        SearchManagerInterface $searchManager,
        RequestAnalyzerInterface $requestAnalyzer,
        ParameterResolverInterface $parameterResolver,
        EngineInterface $engine
    ) {
        $this->searchManager = $searchManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->parameterResolver = $parameterResolver;
        $this->engine = $engine;
    }

    /**
     * Returns the search results for the given query.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function queryAction(Request $request)
    {
        $query = $this->getRequestParameter($request, 'q', true);

        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        $webspace = $this->requestAnalyzer->getWebspace();

        $queryString = '';
        if (strlen($query) < 3) {
            $queryString .= '+("' . self::escapeDoubleQuotes($query) . '") ';
        } else {
            $queryValues = explode(' ', $query);
            foreach ($queryValues as $queryValue) {
                if (strlen($queryValue) > 2) {
                    $queryString .= '+("' . self::escapeDoubleQuotes($queryValue) . '" OR ' .
                        preg_replace('/([^\pL\s\d])/u', '?', $queryValue) . '* OR ' .
                        preg_replace('/([^\pL\s\d])/u', '', $queryValue) . '~) ';
                } else {
                    $queryString .= '+("' . self::escapeDoubleQuotes($queryValue) . '") ';
                }
            }
        }

        $hits = $this->searchManager
            ->createSearch($queryString)
            ->locale($locale)
            ->index('page_' . $webspace->getKey() . '_published')
            ->execute();

        return $this->engine->renderResponse(
            $webspace->getTemplate('search'),
            $this->parameterResolver->resolve(
                ['query' => $query, 'hits' => $hits],
                $this->requestAnalyzer
            )
        );
    }

    /**
     * Returns the string with escaped quotes.
     *
     * @param string $query
     *
     * @return string
     */
    private static function escapeDoubleQuotes($query)
    {
        return str_replace('"', '\\"', $query);
    }
}

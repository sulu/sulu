<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Unit\Controller;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Massive\Bundle\SearchBundle\Search\SearchQueryBuilder;
use Sulu\Bundle\SearchBundle\Controller\WebsiteSearchController;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebsiteSearchControllerTest extends \PHPUnit_Framework_TestCase
{
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
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var WebsiteSearchController
     */
    private $websiteSearchController;

    public function setUp()
    {
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->parameterResolver = $this->prophesize(ParameterResolverInterface::class);
        $this->engine = $this->prophesize(EngineInterface::class);
        $this->container = $this->prophesize(ContainerInterface::class);

        $this->websiteSearchController = new WebsiteSearchController(
            $this->searchManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->parameterResolver->reveal(),
            $this->engine->reveal()
        );
        $this->websiteSearchController->setContainer($this->container->reveal());
    }

    public function testQueryAction()
    {
        $request = new Request(['q' => 'Test']);

        $localization = new Localization();
        $localization->setLanguage('en');

        $webspace = new Webspace();
        $webspace->setKey('sulu');
        $webspace->addTemplate('search', 'search.html.twig');

        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->container->getParameter('sulu_search.website_indexes')->willReturn(['page_{ webspace_key }_published']);

        /** @var \Twig_Environment $twigEnvironment */
        $twigEnvironment = $this->prophesize(\Twig_Environment::class);
        $this->container->get('twig')->willReturn($twigEnvironment->reveal());
        $twigTemplate = $this->prophesize(\Twig_Template::class);
        $twigEnvironment->createTemplate('page_{ webspace_key }_published')->willReturn($twigTemplate);
        $twigTemplate->render(['webspace' => $webspace])->willReturn('page_sulu_published');

        $searchQueryBuilder = $this->prophesize(SearchQueryBuilder::class);
        $this->searchManager->createSearch('+("Test" OR Test* OR Test~) ')->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->locale('en')->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->indexes(['page_sulu_published'])->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->execute()->willReturn([]);

        $this->parameterResolver->resolve(
            ['query' => 'Test', 'hits' => []],
            $this->requestAnalyzer->reveal()
        )->willReturn(['query' => 'Test', 'hits' => []]);

        $this->engine->renderResponse(
            'search.html.twig',
            ['query' => 'Test', 'hits' => []]
        )->willReturn(new Response());

        $this->assertInstanceOf(Response::class, $this->websiteSearchController->queryAction($request));
    }
}

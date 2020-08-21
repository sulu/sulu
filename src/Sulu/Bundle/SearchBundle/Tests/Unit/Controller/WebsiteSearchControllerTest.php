<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Unit\Controller;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Massive\Bundle\SearchBundle\Search\SearchQueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\SearchBundle\Controller\WebsiteSearchController;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class WebsiteSearchControllerTest extends TestCase
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
     * @var Environment
     */
    private $twig;

    /**
     * @var FilesystemLoader
     */
    private $twigLoader;

    /**
     * @var WebsiteSearchController
     */
    private $websiteSearchController;

    public function setUp(): void
    {
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->parameterResolver = $this->prophesize(ParameterResolverInterface::class);
        $this->twig = $this->prophesize(Environment::class);
        $this->twigLoader = $this->prophesize(FilesystemLoader::class);
        $this->twig->getLoader()->willReturn($this->twigLoader->reveal());

        $this->websiteSearchController = new WebsiteSearchController(
            $this->searchManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->parameterResolver->reveal(),
            $this->twig->reveal(),
            ['page_#webspace#_published']
        );
    }

    public function testQueryAction()
    {
        $request = new Request(['q' => 'Test']);

        $localization = new Localization();
        $localization->setLanguage('en');

        $webspace = new Webspace();
        $webspace->setKey('sulu');
        $webspace->addTemplate('search', 'search');

        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $searchQueryBuilder = $this->prophesize(SearchQueryBuilder::class);
        $this->searchManager->createSearch('+("Test" OR Test* OR Test~) ')->willReturn(
            $searchQueryBuilder->reveal()
        );
        $searchQueryBuilder->locale('en')->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->indexes(['page_sulu_published'])->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->execute()->willReturn([]);

        $this->parameterResolver->resolve(
            ['query' => 'Test', 'hits' => []],
            $this->requestAnalyzer->reveal()
        )->willReturn(['query' => 'Test', 'hits' => []]);

        $this->twigLoader->exists(Argument::any())->willReturn(true);

        $this->twig->render(
            'search.html.twig',
            ['query' => 'Test', 'hits' => []]
        )->willReturn(new Response());

        $this->assertInstanceOf(Response::class, $this->websiteSearchController->queryAction($request));
    }

    public function testQueryActionWithDifferentIndexes()
    {
        $this->websiteSearchController = new WebsiteSearchController(
            $this->searchManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->parameterResolver->reveal(),
            $this->twig->reveal(),
            ['examples_published', 'pages_#webspace#_published']
        );

        $request = new Request(['q' => 'Test']);

        $localization = new Localization();
        $localization->setLanguage('en');

        $webspace = new Webspace();
        $webspace->setKey('sulu');
        $webspace->addTemplate('search', 'search');

        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $searchQueryBuilder = $this->prophesize(SearchQueryBuilder::class);
        $this->searchManager->createSearch('+("Test" OR Test* OR Test~) ')->willReturn(
            $searchQueryBuilder->reveal()
        );
        $searchQueryBuilder->locale('en')->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->indexes(['examples_published', 'pages_sulu_published'])->willReturn(
            $searchQueryBuilder->reveal()
        );
        $searchQueryBuilder->execute()->willReturn([]);

        $this->parameterResolver->resolve(
            ['query' => 'Test', 'hits' => []],
            $this->requestAnalyzer->reveal()
        )->willReturn(['query' => 'Test', 'hits' => []]);

        $this->twigLoader->exists(Argument::any())->willReturn(true);

        $this->twig->render(
            'search.html.twig',
            ['query' => 'Test', 'hits' => []]
        )->willReturn(new Response());

        $this->assertInstanceOf(Response::class, $this->websiteSearchController->queryAction($request));
    }
}

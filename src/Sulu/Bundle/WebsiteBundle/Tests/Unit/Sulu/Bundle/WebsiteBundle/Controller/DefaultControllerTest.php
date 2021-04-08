<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DefaultControllerTest extends \PHPUnit_Framework_TestCase
{
    private $defaultController;

    private $container;

    private $twig;

    private $twigLoader;

    private $request;

    private $requestStack;

    private $structure;

    private $parameterResolver;

    private $requestAnalyzer;

    public function setUp()
    {
        $this->structure = $this->prophesize(PageBridge::class);
        $this->structure->getView()->willReturn('pages/default');
        $this->request = $this->prophesize(Request::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());
        $this->twig = $this->prophesize(\Twig_Environment::class);
        $this->twigLoader = $this->prophesize(\Twig_Loader_Filesystem::class);
        $this->twig->getLoader()->willReturn($this->twigLoader->reveal());
        $this->parameterResolver = $this->prophesize(ParameterResolverInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->has('templating')->willReturn(false);
        $this->container->has('twig')->willReturn(true);
        $this->container->get('twig')->willReturn($this->twig->reveal());
        $this->container->get('request_stack')->willReturn($this->requestStack->reveal());
        $this->container->has('sulu_http_cache.cache_lifetime.enhancer')->willReturn(false);
        $this->container->get('sulu_website.resolver.parameter')->willReturn($this->parameterResolver->reveal());
        $this->container->get('sulu_core.webspace.request_analyzer')->willReturn($this->requestAnalyzer->reveal());
        $this->defaultController = new DefaultController();
        $this->defaultController->setContainer($this->container->reveal());
    }

    public function testInvalidTemplate()
    {
        $this->setExpectedException(HttpException::class, function(HttpException $exception) {
            $this->assertSame(406, $exception->getStatusCode());
        });

        $this->request->getRequestFormat()->willReturn('html')->shouldBeCalled();
        $this->twigLoader->exists('pages/default.html.twig')->willReturn(false)->shouldBeCalled();
        $this->defaultController->indexAction($this->structure->reveal(), false, false);
    }

    public function testValidTemplate()
    {
        $this->request->getRequestFormat()->willReturn('html')->shouldBeCalled();
        $this->twigLoader->exists('pages/default.html.twig')->willReturn(true)->shouldBeCalled();
        $this->parameterResolver->resolve(Argument::any(), Argument::any(), Argument::any(), false)
            ->willReturn(['argument' => 'value'])->shouldBeCalled();
        $this->twig->render(
            'pages/default.html.twig',
            ['argument' => 'value']
        )->willReturn('My Content')->shouldBeCalled();
        $response = $this->defaultController->indexAction($this->structure->reveal(), false, false);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('My Content', $response->getContent());
    }
}

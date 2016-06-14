<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Controller;

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\Controller\ExceptionController;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\EngineInterface;

class ExceptionControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var ParameterResolverInterface
     */
    private $parameterResolver;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var FlattenException
     */
    private $exception;

    /**
     * @var Webspace
     */
    private $webspace;

    protected function setUp()
    {
        $this->engine = $this->prophesize(EngineInterface::class);
        $this->parameterResolver = $this->prophesize(ParameterResolverInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->request = $this->prophesize(Request::class);
        $this->exception = $this->prophesize(FlattenException::class);

        $this->exception->getStatusCode()->willReturn(500);
        $this->request->get('showException', Argument::any())->willReturnArgument(1);
        $this->request->getRequestFormat()->willReturn('html');

        $requestHeader = $this->prophesize(HeaderBag::class);
        $requestHeader->get('X-Php-Ob-Level', -1)->willReturn(5);
        $this->request->reveal()->headers = $requestHeader->reveal();

        $this->webspace = $this->prophesize(Webspace::class);
        $theme = $this->prophesize(Theme::class);
        $theme->getErrorTemplate(500)->willReturn('error500.html.twig');
        $this->webspace->getTheme()->willReturn($theme->reveal());
    }

    protected function getExceptionController($debug = false)
    {
        return new ExceptionController(
            $this->engine->reveal(),
            $debug,
            $this->parameterResolver->reveal(),
            $this->requestAnalyzer->reveal()
        );
    }

    public function testShow()
    {
        $exceptionController = $this->getExceptionController();

        $parameters = [
            'status_code' => 500,
            'status_text' => 'Internal Server Error',
            'exception' => $this->exception->reveal(),
            'currentContent' => '',
            'webspace' => $this->webspace->reveal(),
        ];

        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->engine->exists('@Twig/Exception/error500.html.twig')->willReturn(true);
        $this->parameterResolver->resolve(
            [
                'status_code' => 500,
                'status_text' => 'Internal Server Error',
                'exception' => $this->exception->reveal(),
                'currentContent' => '',
            ],
            $this->requestAnalyzer
        )->willReturn($parameters);
        $this->engine->render('error500.html.twig', $parameters)->shouldBeCalled()->willReturn('My Exception Content');

        $response = $exceptionController->showAction($this->request->reveal(), $this->exception->reveal());

        $this->assertEquals('My Exception Content', $response->getContent());
    }

    public function testShowNoWebspace()
    {
        $exceptionController = $this->getExceptionController();

        $this->engine->exists('@Twig/Exception/error500.html.twig')->willReturn(true);

        $this->engine->render('@Twig/Exception/error500.html.twig', Argument::any())
            ->shouldBeCalled()->willReturn('My Exception Content');

        $response = $exceptionController->showAction($this->request->reveal(), $this->exception->reveal());

        $this->assertEquals('My Exception Content', $response->getContent());
    }

    public function testShowDebug()
    {
        $exceptionController = $this->getExceptionController(true);

        $this->engine->exists('@Twig/Exception/exception_full.html.twig')->willReturn(true);

        $this->engine->render('@Twig/Exception/exception_full.html.twig', Argument::any())
            ->shouldBeCalled()->willReturn('My Exception Content');

        $response = $exceptionController->showAction($this->request->reveal(), $this->exception->reveal());

        $this->assertEquals('My Exception Content', $response->getContent());
    }
}

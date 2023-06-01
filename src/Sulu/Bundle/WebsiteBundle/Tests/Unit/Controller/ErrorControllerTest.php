<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Controller\ErrorController;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ErrorController as SymfonyErrorController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ErrorControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SymfonyErrorController>
     */
    private $symfonyErrorController;

    /**
     * @var ObjectProphecy<Environment>
     */
    private $twig;

    /**
     * @var ObjectProphecy<FilesystemLoader>
     */
    private $loader;

    /**
     * @var ObjectProphecy<TemplateAttributeResolverInterface>
     */
    private $templateAttributeResolver;

    public function setUp(): void
    {
        $this->twig = $this->prophesize(Environment::class);
        $this->templateAttributeResolver = $this->prophesize(TemplateAttributeResolverInterface::class);
        $this->symfonyErrorController = $this->prophesize(SymfonyErrorController::class);
        $this->loader = $this->prophesize(FilesystemLoader::class);
    }

    public function testPreview(): void
    {
        $code = 404;
        $request = $this->createRequest();
        $errorController = $this->createErrorController();

        $this->symfonyErrorController->preview($request, $code)
            ->shouldBeCalled()
            ->willReturn(new Response('', $code));

        $response = $errorController->preview($request, $code);
        $this->assertSame($code, $response->getStatusCode());
    }

    public function testNoWebspace(): void
    {
        $code = 404;
        $exception = new HttpException($code);
        $request = $this->createRequest();
        $errorController = $this->createErrorController();

        $this->symfonyErrorController->__invoke($exception)
            ->shouldBeCalled()
            ->willReturn(new Response('', $code));

        $response = $errorController->__invoke($request, $exception);
        $this->assertSame($code, $response->getStatusCode());
    }

    public function testWebspace404Template(): void
    {
        $code = 404;
        $exception = new HttpException($code);
        $webspace = new Webspace();
        $webspace->addTemplate('error-404', 'error/error-404');
        $request = $this->createRequest($webspace);
        $errorController = $this->createErrorController();

        $this->symfonyErrorController->__invoke($exception)->shouldNotBeCalled();
        $this->templateAttributeResolver->resolve(Argument::any())->willReturnArgument(0);

        $this->twig->getLoader()
            ->shouldBeCalled()
            ->willReturn($this->loader->reveal());

        $this->loader->exists('error/error-404.html.twig')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->twig->render('error/error-404.html.twig', Argument::any())
            ->willReturn('Error 404 Template')
            ->shouldBeCalled();

        $response = $errorController->__invoke($request, $exception);
        $this->assertSame($code, $response->getStatusCode());
        $this->assertSame('Error 404 Template', $response->getContent());
    }

    public function testWebspace404WithoutTemplate(): void
    {
        $code = 404;
        $exception = new HttpException($code, 'Page not found');
        $webspace = new Webspace();
        $webspace->addTemplate('error-404', 'error/error-404');
        $request = $this->createRequest($webspace);
        $request->setRequestFormat('xml');
        $errorController = $this->createErrorController();

        $this->templateAttributeResolver->resolve(Argument::any())->willReturnArgument(0);

        $this->twig->getLoader()
            ->shouldBeCalled()
            ->willReturn($this->loader->reveal());

        $this->loader->exists('error/error-404.xml.twig')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->symfonyErrorController->__invoke($exception)
            ->shouldBeCalled()
            ->willReturn(new Response('Page not found', $code));

        $this->twig->render('error/error-404.xml.twig', Argument::any())
            ->shouldNotBeCalled();

        $response = $errorController->__invoke($request, $exception);
        $this->assertSame($code, $response->getStatusCode());
        $this->assertSame('Page not found', $response->getContent());
    }

    public function testWebspaceErrorFallbackTemplate(): void
    {
        $code = 404;
        $exception = new HttpException($code);
        $webspace = new Webspace();
        $webspace->addTemplate('error', 'error/error');
        $request = $this->createRequest($webspace);
        $errorController = $this->createErrorController();

        $this->symfonyErrorController->__invoke($exception)->shouldNotBeCalled();
        $this->templateAttributeResolver->resolve(Argument::any())->willReturnArgument(0);

        $this->twig->getLoader()
            ->shouldBeCalled()
            ->willReturn($this->loader->reveal());

        $this->loader->exists('error/error.html.twig')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->twig->render('error/error.html.twig', Argument::any())
            ->willReturn('Error Fallback Template')
            ->shouldBeCalled();

        $response = $errorController->__invoke($request, $exception);
        $this->assertSame($code, $response->getStatusCode());
        $this->assertSame('Error Fallback Template', $response->getContent());
    }

    private function createErrorController(bool $debug = false): ErrorController
    {
        return new ErrorController(
            $this->symfonyErrorController->reveal(),
            $this->templateAttributeResolver->reveal(),
            $this->twig->reveal(),
            $debug
        );
    }

    private function createRequest(?Webspace $webspace = null): Request
    {
        $requestAttributes = new RequestAttributes(['webspace' => $webspace]);

        $request = new Request();
        $request->attributes->set('_sulu', $requestAttributes);

        return $request;
    }
}

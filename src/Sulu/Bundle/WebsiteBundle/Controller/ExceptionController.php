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

use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * Custom exception controller.
 */
class ExceptionController
{
    /**
     * @var BaseExceptionController
     */
    private $exceptionController;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ParameterResolverInterface
     */
    private $parameterResolver;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param BaseExceptionController $exceptionController
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param ParameterResolverInterface $parameterResolver
     * @param \Twig_Environment $twig
     * @param bool $debug
     */
    public function __construct(
        BaseExceptionController $exceptionController,
        RequestAnalyzerInterface $requestAnalyzer,
        ParameterResolverInterface $parameterResolver,
        \Twig_Environment $twig,
        $debug
    ) {
        $this->exceptionController = $exceptionController;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->parameterResolver = $parameterResolver;
        $this->twig = $twig;
        $this->debug = $debug;
    }

    /**
     * {@see BaseExceptionController::showAction()}.
     */
    public function showAction(
        Request $request,
        FlattenException $exception,
        DebugLoggerInterface $logger = null
    ) {
        $code = $exception->getStatusCode();
        $template = null;
        if ($webspace = $this->requestAnalyzer->getWebspace()) {
            $template = $webspace->getTemplate('error-' . $code);

            if (null === $template) {
                $template = $webspace->getTemplate('error');
            }
        }

        $showException = $request->attributes->get('showException', $this->debug);
        if ($showException || 'html' !== $request->getRequestFormat() || null === $template) {
            return $this->exceptionController->showAction($request, $exception, $logger);
        }

        $context = $this->parameterResolver->resolve(
            [
                'status_code' => $code,
                'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception' => $exception,
                'currentContent' => $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1)),
            ],
            $this->requestAnalyzer
        );

        return new Response(
            $this->twig->render(
                $template,
                $context
            ),
            $code
        );
    }

    /**
     * Returns and cleans output-buffer.
     *
     * @param int $startObLevel
     *
     * @return string
     */
    protected function getAndCleanOutputBuffering($startObLevel)
    {
        if (ob_get_level() <= $startObLevel) {
            return '';
        }

        Response::closeOutputBuffers($startObLevel + 1, true);

        return ob_get_clean();
    }
}

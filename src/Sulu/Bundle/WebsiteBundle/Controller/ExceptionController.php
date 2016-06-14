<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Templating\EngineInterface;

/**
 * Custom exception controller.
 */
class ExceptionController extends BaseExceptionController
{
    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ParameterResolverInterface
     */
    private $parameterResolver;

    public function __construct(
        EngineInterface $engine,
        $debug,
        ParameterResolverInterface $parameterResolver,
        RequestAnalyzerInterface $requestAnalyzer = null
    ) {
        $this->engine = $engine;
        $this->debug = $debug;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->parameterResolver = $parameterResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $code = $exception->getStatusCode();
        $showException = $request->get('showException', $this->debug);
        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $baseTemplate = $this->findTemplate($request, $request->getRequestFormat(), $code, $showException);
        $statusText = array_key_exists($code, Response::$statusTexts) ? Response::$statusTexts[$code] : '';

        $template = null;
        if ($this->requestAnalyzer && $this->requestAnalyzer->getWebspace()) {
            $template = $this->requestAnalyzer->getWebspace()->getTheme()->getErrorTemplate($code);
        }

        if ($showException || null === $template || $request->getRequestFormat() !== 'html') {
            return new Response(
                $this->engine->render(
                    (string) $baseTemplate,
                    [
                        'status_code' => $code,
                        'status_text' => $statusText,
                        'exception' => $exception,
                        'logger' => $logger,
                        'currentContent' => $currentContent,
                    ]
                )
            );
        }

        $parameters = $this->parameterResolver->resolve(
            [
                'status_code' => $code,
                'status_text' => $statusText,
                'exception' => $exception,
                'currentContent' => $currentContent,
            ],
            $this->requestAnalyzer
        );

        return new Response($this->engine->render($template, $parameters), $code);
    }

    /**
     * {@inheritdoc}
     */
    protected function templateExists($template)
    {
        return $this->engine->exists($template);
    }
}

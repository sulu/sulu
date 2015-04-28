<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * Custom exception controller
 */
class ExceptionController extends BaseExceptionController
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ParameterResolverInterface
     */
    private $parameterResolver;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    public function __construct(
        \Twig_Environment $twig,
        $debug,
        ParameterResolverInterface $parameterResolver,
        ContentMapperInterface $contentMapper,
        RequestAnalyzerInterface $requestAnalyzer = null
    ) {
        parent::__construct($twig, $debug);

        $this->requestAnalyzer = $requestAnalyzer;
        $this->contentMapper = $contentMapper;
        $this->parameterResolver = $parameterResolver;
    }

    public function showAction(
        Request $request,
        FlattenException $exception,
        DebugLoggerInterface $logger = null
    ) {
        // remove empty first line
        if (ob_get_length()) {
            ob_clean();
        }

        $code = $exception->getStatusCode();
        $template = $this->requestAnalyzer->getWebspace()->getTheme()->getErrorTemplate($code);

        if ($request->getRequestFormat() === 'html' && $template !== null) {
            $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));

            $parameter = array(
                'statusCode' => $code,
                'statusText' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception' => $exception,
                'currentContent' => $currentContent
            );
            $data = $this->parameterResolver->resolve(
                $parameter,
                $this->requestAnalyzer
            );

            return new Response(
                $this->twig->render(
                    $template,
                    $data
                ),
                $code
            );
        }

        return parent::showAction($request, $exception, $logger);
    }
}

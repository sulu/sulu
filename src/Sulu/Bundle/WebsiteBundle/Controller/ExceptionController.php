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
 * @package Sulu\Bundle\WebsiteBundle\Controller
 */
class ExceptionController extends BaseExceptionController
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var string
     */
    private $errorTemplates;

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
        array $errorTemplates,
        ParameterResolverInterface $parameterResolver,
        ContentMapperInterface $contentMapper,
        RequestAnalyzerInterface $requestAnalyzer = null
    ) {
        parent::__construct($twig, $debug);

        $this->requestAnalyzer = $requestAnalyzer;
        $this->errorTemplates = $errorTemplates;
        $this->contentMapper = $contentMapper;
        $this->parameterResolver = $parameterResolver;
    }

    public function showAction(
        Request $request,
        FlattenException $exception,
        DebugLoggerInterface $logger = null,
        $_format = 'html'
    ) {
        // remove empty first line
        if (ob_get_length()) {
            ob_clean();
        }

        if ($request->getRequestFormat() === 'html') {
            $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
            $code = $exception->getStatusCode();
            $parameter = array(
                'status_code' => $code,
                'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception' => $exception,
                'currentContent' => $currentContent
            );
            $data = $this->parameterResolver->resolve(
                $parameter,
                $this->requestAnalyzer,
                $this->contentMapper->loadStartPage(
                    $this->requestAnalyzer->getWebspace()->getKey(),
                    $this->requestAnalyzer->getCurrentLocalization()->getLocalization()
                )
            );

            if (array_key_exists($code, $this->errorTemplates)) {
                return new Response(
                    $this->twig->render(
                        $this->errorTemplates[$code],
                        $data
                    ),
                    $code
                );
            }
        }

        return parent::showAction($request, $exception, $logger, $request->getRequestFormat());
    }
}

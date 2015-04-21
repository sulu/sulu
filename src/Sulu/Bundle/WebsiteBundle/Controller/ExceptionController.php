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
    private $error404Template;

    /**
     * @varstring
     */
    private $error500Template;

    public function __construct(
        \Twig_Environment $twig,
        $debug,
        $error404Template,
        $error500Template,
        RequestAnalyzerInterface $requestAnalyzer = null
    ) {
        parent::__construct($twig, $debug);

        $this->requestAnalyzer = $requestAnalyzer;
        $this->error404Template = $error404Template;
        $this->error500Template = $error500Template;
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
            if ($exception->getStatusCode() == 404) {
                return new Response(
                    $this->twig->render(
                        $this->error404Template,
                        array(
                            'request' => array(
                                'webspaceKey' => $this->requestAnalyzer->getWebspace()->getKey(),
                                'locale' => $this->requestAnalyzer->getCurrentLocalization(),
                                'portalUrl' => $this->requestAnalyzer->getPortalUrl(),
                                'resourceLocatorPrefix' => $this->requestAnalyzer->getResourceLocatorPrefix(),
                                'resourceLocator' => $this->requestAnalyzer->getResourceLocator(),
                                'get' => $this->requestAnalyzer->getGetParameters(),
                                'post' => $this->requestAnalyzer->getPostParameters(),
                                'analyticsKey' => $this->requestAnalyzer->getAnalyticsKey()
                            ),
                            'path' => $request->getPathInfo(),
                            'urls' => array()
                        )
                    ),
                    404
                );
            } elseif ($exception->getStatusCode() < 500) {
                $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
                $code = $exception->getStatusCode();

                return new Response(
                    $this->twig->render(
                        $this->error500Template,
                        array(
                            'status_code' => $code,
                            'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                            'exception' => $exception,
                            'currentContent' => $currentContent,
                            'request' => array(
                                'webspaceKey' => $this->requestAnalyzer->getWebspace()->getKey(),
                                'locale' => $this->requestAnalyzer->getCurrentLocalization()->getLocalization()
                            ),
                            'urls' => array()
                        )
                    ),
                    $exception->getStatusCode()
                );
            }
        }

        return parent::showAction($request, $exception, $logger, $request->getRequestFormat());
    }
}

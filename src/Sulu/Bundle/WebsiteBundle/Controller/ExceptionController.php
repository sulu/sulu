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

    public function __construct(\Twig_Environment $twig, $debug, RequestAnalyzerInterface $requestAnalyzer = null)
    {
        parent::__construct($twig, $debug);

        $this->requestAnalyzer = $requestAnalyzer;
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
                        'ClientWebsiteBundle:views:error404.html.twig',
                        array(
                            'request' => array(
                                'webspaceKey' => $this->requestAnalyzer->getCurrentWebspace()->getKey(),
                                'locale' => $this->requestAnalyzer->getCurrentLocalization()->getLocalization(),
                                'portalUrl' => $this->requestAnalyzer->getCurrentPortalUrl(),
                                'resourceLocatorPrefix' => $this->requestAnalyzer->getCurrentResourceLocatorPrefix(),
                                'resourceLocator' => $this->requestAnalyzer->getCurrentResourceLocator(),
                                'get' => $this->requestAnalyzer->getCurrentGetParameter(),
                                'post' => $this->requestAnalyzer->getCurrentPostParameter(),
                                'analyticsKey' => $this->requestAnalyzer->getCurrentAnalyticsKey(),
                            ),
                            'path' => $request->getPathInfo()
                        )
                    ),
                    404
                );
            } elseif ($exception->getStatusCode() < 500) {
                $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
                $code = $exception->getStatusCode();

                return new Response(
                    $this->twig->render(
                        'ClientWebsiteBundle:views:error.html.twig',
                        array(
                            'status_code' => $code,
                            'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                            'exception' => $exception,
                            'currentContent' => $currentContent,
                            'request' => array(
                                'webspaceKey' => $this->requestAnalyzer->getCurrentWebspace()->getKey(),
                                'locale' => $this->requestAnalyzer->getCurrentLocalization()->getLocalization()
                            )
                        )
                    ),
                    $exception->getStatusCode()
                );
            }
        }

        return parent::showAction($request, $exception, $logger, $request->getRequestFormat());
    }
}

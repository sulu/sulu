<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Listener;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Kernel exception listener to render error page
 */
class PreviewExceptionListener
{
    /**
     * The template engine
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * Name of the template
     * @var string
     */
    private $templateName;

    public function __construct(EngineInterface $templateEngine, $templateName)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Handles a kernel exception and returns a relevant response.
     * Aims to deliver content to the user that explains the exception, rather than falling
     * back on symfony's exception handler which displays a less verbose error message.
     * @param GetResponseForExceptionEvent $event The exception event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // do nothing if request is no preview request
        if (!$event->getRequest()->get('preview', false)) {
            return;
        }

        $ex = $event->getException();
        $code = 500;

        $response = $this->templateEngine->render(
            $this->findTemplate(),
            array(
                'status_code' => $code,
                'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'code' => $ex->getCode(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage(),
                'trace' => $ex->getTrace(),
                'trace_string' => $ex->getTraceAsString(),
                'previous_content' => $event->getResponse()->getContent()
            )
        );

        $event->setResponse(new Response($response));
    }

    /**
     * Returns template
     * @return string
     */
    private function findTemplate()
    {
        if ($this->templateEngine->exists($this->templateName)) {
            return $this->templateName;
        }

        return 'SuluContentBundle:Preview:error.html.twig';
    }

} 

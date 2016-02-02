<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\FlattenException;

/**
 * Kernel exception listener to render error page.
 */
class PreviewExceptionListener
{
    /**
     * The template engine.
     *
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * Name of the template.
     *
     * @var string
     */
    private $templateName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EngineInterface $templateEngine, LoggerInterface $logger, $templateName = null)
    {
        $this->templateEngine = $templateEngine;
        $this->templateName = $templateName;
        $this->logger = $logger;
    }

    /**
     * Handles a kernel exception and returns a relevant response.
     * Aims to deliver content to the user that explains the exception, rather than falling
     * back on symfony's exception handler which displays a less verbose error message.
     *
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
        $previousContent = $event->getResponse();
        $content = $previousContent !== null ? $previousContent->getContent() : '';
        $statusTexts = Response::$statusTexts;
        $statusText = isset($statusTexts[$code]) ? $statusTexts[$code] : '';
        $exception = FlattenException::create($ex, $code);

        // log exception
        $this->logger->error(sprintf('%s (%s %s)', $ex->getMessage(), $code, $statusText));

        $responseContent = $this->templateEngine->render(
            $this->findTemplate(),
            [
                'status_code' => $code,
                'status_text' => $statusText,
                'exception' => $exception,
                'logger' => null,
                'currentContent' => $content,
            ]
        );

        $event->setResponse(new Response($responseContent));
    }

    /**
     * Returns template.
     *
     * @return string
     */
    private function findTemplate()
    {
        if ($this->templateName !== null && $this->templateEngine->exists($this->templateName)) {
            return $this->templateName;
        }

        return 'TwigBundle:Exception:exception_full.html.twig';
    }
}

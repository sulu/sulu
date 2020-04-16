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

use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twig\Environment;

/**
 * Custom exception controller.
 */
class ErrorController
{
    /**
     * @var TemplateAttributeResolverInterface
     */
    private $templateAttributeResolver;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var ErrorRendererInterface|null
     */
    private $errorRenderer;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param bool $debug
     */
    public function __construct(
        TemplateAttributeResolverInterface $templateAttributeResolver,
        Environment $twig,
        ?ErrorRendererInterface $errorRenderer,
        $debug = false
    ) {
        $this->errorRenderer = $errorRenderer;
        $this->templateAttributeResolver = $templateAttributeResolver;
        $this->twig = $twig;
        $this->debug = $debug;
    }

    public function showAction(Request $request, HttpException $exception)
    {
        $code = $exception->getStatusCode();
        $webspace = $this->getWebspace($request);

        $template = null;
        if ($webspace) {
            $template = $webspace->getTemplate('error-' . $code, $request->getRequestFormat());

            if (null === $template) {
                $template = $webspace->getTemplate('error', $request->getRequestFormat());
            }
        }

        $showException = $request->attributes->get('showException', $this->debug);
        if ($showException || null === $template || !$this->twig->getLoader()->exists($template)) {
            return new Response(
                $this->errorRenderer->render($exception),
                $code
            );
        }

        $context = $this->templateAttributeResolver->resolve(
            [
                'status_code' => $code,
                'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception' => $exception,
                'currentContent' => $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1)),
            ]
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

    private function getWebspace(Request $request): Webspace
    {
        /** @var RequestAttributes $suluAttributes */
        $suluRequestAttributes = $request->attributes->get('_sulu');

        if (!$suluRequestAttributes) {
            return null;
        }

        return $suluRequestAttributes->getAttribute('webspace');
    }
}

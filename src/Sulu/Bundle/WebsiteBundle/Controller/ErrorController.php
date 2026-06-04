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
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ErrorController as SymfonyErrorController;
use Twig\Environment;
use Psr\Cache\CacheItemPoolInterface;

class ErrorController
{
    /**
     * @var SymfonyErrorController
     */
    private $symfonyErrorController;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var TemplateAttributeResolverInterface
     */
    private $templateAttributeResolver;

    /**
     * @var Environment
     */
    private $twig;

    private CacheItemPoolInterface $cache;

    public function __construct(
        SymfonyErrorController $symfonyErrorController,
        TemplateAttributeResolverInterface $templateAttributeResolver,
        Environment $twig,
        CacheItemPoolInterface $cache,
        bool $debug = false,
    ) {
        $this->symfonyErrorController = $symfonyErrorController;
        $this->templateAttributeResolver = $templateAttributeResolver;
        $this->twig = $twig;
        $this->cache = $cache;
        $this->debug = $debug;
    }

    public function __invoke(Request $request, \Throwable $exception): Response
    {
        if ($this->debug && $request->attributes->getBoolean('showException', true)) {
            return $this->symfonyErrorController->__invoke($exception);
        }

        $flattenException = FlattenException::createFromThrowable($exception);
        $code = $flattenException->getStatusCode();
        $errorTemplate = $this->getErrorTemplate($request, $code);

        // render the default twig error template when no webspace template found
        if (!$errorTemplate) {
            return $this->symfonyErrorController->__invoke($exception);
        }

        $webspaceAttributes = $request->attributes->get("_sulu");
        $locale = $webspaceAttributes->getAttribute('locale');
        $webspaceKey = $webspaceAttributes->getAttribute('webspace')->getKey();

        $cacheKey = sprintf('%s-%s-%s', $webspaceKey, $locale, $code);

        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            return new Response($item->get(), $code);
        }

        $htmlResponse = $this->twig->render(
            $errorTemplate,
            $this->templateAttributeResolver->resolve([
                'exception' => $flattenException,
                'status_code' => $flattenException->getStatusCode(),
                'status_text' => $flattenException->getStatusText(),
            ])
        );

        $item->set($htmlResponse);
        $this->cache->save($item);

        return new Response($htmlResponse, $code);
    }

    private function getErrorTemplate(Request $request, int $code): ?string
    {
        $suluAttributes = $request->attributes->get('_sulu');

        if (!$suluAttributes instanceof RequestAttributes) {
            return null;
        }

        $webspace = $suluAttributes->getAttribute('webspace');

        if (!$webspace instanceof Webspace) {
            return null;
        }

        // get the specified or the default error template
        $template = $webspace->getTemplate('error-' . $code, $request->getRequestFormat());
        if (null === $template) {
            $template = $webspace->getTemplate('error', $request->getRequestFormat());
        }

        if (false === $this->twig->getLoader()->exists($template)) {
            return null;
        }

        return $template;
    }

    public function preview(Request $request, int $code): Response
    {
        return $this->symfonyErrorController->preview($request, $code);
    }
}

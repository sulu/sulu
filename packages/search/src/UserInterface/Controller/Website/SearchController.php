<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Search\UserInterface\Controller\Website;

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Search\Condition\Condition;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\UserInterface\Controller\Website\ContentController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class SearchController extends ContentController
{
    public function __construct(
        private readonly EngineInterface $engine,
        private readonly RequestAnalyzerInterface $requestAnalyzer,
        private readonly Environment $twig,
        private readonly TemplateAttributeResolverInterface $templateAttributeResolver,
    ) {
    }

    public function queryAction(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $requestFormat = $request->getRequestFormat() ?? 'html';

        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        $webspace = $this->requestAnalyzer->getWebspace();

        $search = $this->engine->createSearchBuilder('website')
            ->addFilter(Condition::search($query));

        if ($locale) {
            $search->addFilter(Condition::equal('locale', $locale));
        }

        $search->addFilter(Condition::equal('webspaces', $webspace->getKey()));

        $search->highlight(['title', 'content'], '<mark>', '</mark>');
        $result = $search->getResult();
        $hits = [];

        foreach ($result as $document) {
            $hits[] = $document;
        }

        $template = $webspace->getTemplate('search', (string) $request->getRequestFormat());

        if (!$template || !$this->twig->getLoader()->exists($template)) {
            throw new NotFoundHttpException();
        }

        $parameters = ['query' => $query, 'hits' => $hits];

        $parameters = $this->templateAttributeResolver->resolve($parameters);

        $response = new Response($this->renderSuluView($template, $requestFormat, $parameters, false, false));

        // we need to set the content type ourselves here
        // else symfony will use the accept header of the client and the page could be cached with false content-type
        // see following symfony issue: https://github.com/symfony/symfony/issues/35694
        $mimeType = $request->getMimeType((string) $request->getRequestFormat());
        if ($mimeType) {
            $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }
}

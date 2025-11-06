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
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class SearchController
{
    use RequestParametersTrait;

    /**
     * @param mixed[] $resources
     */
    public function __construct(
        private readonly EngineInterface $engine,
        private readonly RequestAnalyzerInterface $requestAnalyzer,
        private readonly Environment $twig,
        private readonly array $resources,
        private readonly TemplateAttributeResolverInterface $templateAttributeResolver,
    ) {
    }

    public function queryAction(Request $request): Response
    {
        $query = $this->getRequestParameter($request, 'q', false, '');

        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        $webspace = $this->requestAnalyzer->getWebspace();

        $search = $this->engine->createSearchBuilder('website')
            ->addFilter(Condition::search($query));

        if ($locale) {
            $search->addFilter(Condition::equal('locale', $locale));
        }

        if ($webspace) {
            $search->addFilter(Condition::equal('webspaces', $webspace->getKey()));
            /*$search->addFilter(
                Condition::or([
                    Condition::equal('webspaces', $webspace->getKey()),
                    Condition::equal('webspaces', $webspace->getKey()),
                    /*Condition::equal('webspaces', '[]'), // Todo: Check this.
                ])
            );
            */
        }

        $result = $search->getResult();
        $hits = [];

        foreach ($result as $document) {
            $hits[] = $document;
        }

        $template = $webspace->getTemplate('search', $request->getRequestFormat());

        if (!$this->twig->getLoader()->exists($template)) {
            throw new NotFoundHttpException();
        }

        $parameters = ['query' => $query, 'hits' => $hits];

        $parameters = $this->templateAttributeResolver->resolve($parameters);

        $response = new Response($this->twig->render($template, $parameters));

        // we need to set the content type ourselves here
        // else symfony will use the accept header of the client and the page could be cached with false content-type
        // see following symfony issue: https://github.com/symfony/symfony/issues/35694
        $mimeType = $request->getMimeType($request->getRequestFormat());
        if ($mimeType) {
            $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }
}

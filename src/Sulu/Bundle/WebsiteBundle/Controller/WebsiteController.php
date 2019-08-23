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

use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeEnhancer;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twig\Template;

/**
 * Basic class to render Website from phpcr content.
 */
abstract class WebsiteController extends Controller
{
    /**
     * Returns a rendered structure.
     *
     * @param StructureInterface $structure The structure, which has been loaded for rendering
     * @param array $attributes Additional attributes, which will be passed to twig
     * @param bool $preview Defines if the site is rendered in preview mode
     * @param bool $partial Defines if only the content block of the template should be rendered
     *
     * @return Response
     */
    protected function renderStructure(
        StructureInterface $structure,
        $attributes = [],
        $preview = false,
        $partial = false
    ) {
        // extract format twig file
        if (!$preview) {
            $request = $this->getRequest();
            $requestFormat = $request->getRequestFormat();
        } else {
            $requestFormat = 'html';
        }

        $viewTemplate = $structure->getView() . '.' . $requestFormat . '.twig';

        if (!$this->get('twig')->getLoader()->exists($viewTemplate)) {
            throw new HttpException(
                406,
                sprintf('Page does not exist in "%s" format.', $requestFormat)
            );
        }

        // get attributes to render template
        $data = $this->getAttributes($attributes, $structure, $preview);

        // if partial render only content block else full page
        if ($partial) {
            $content = $this->renderBlock(
                $viewTemplate,
                'content',
                $data
            );
        } elseif ($preview) {
            $content = $this->renderPreview(
                $viewTemplate,
                $data
            );
        } else {
            $content = $this->renderView(
                $viewTemplate,
                $data
            );
        }

        $response = new Response($content);

        if (!$preview && $this->getCacheTimeLifeEnhancer()) {
            $this->getCacheTimeLifeEnhancer()->enhance($response, $structure);
        }

        return $response;
    }

    /**
     * Generates attributes.
     */
    protected function getAttributes($attributes, StructureInterface $structure = null, $preview = false)
    {
        return $this->get('sulu_website.resolver.parameter')->resolve(
            $attributes,
            $this->get('sulu_core.webspace.request_analyzer'),
            $structure,
            $preview
        );
    }

    /**
     * Returns rendered part of template specified by block.
     */
    protected function renderBlock($template, $block, $attributes = [])
    {
        $twig = $this->get('twig');
        $attributes = $twig->mergeGlobals($attributes);

        /** @var Template $template */
        $template = $twig->loadTemplate($template);

        $level = ob_get_level();
        ob_start();

        try {
            $rendered = $template->renderBlock($block, $attributes);
            ob_end_clean();

            return $rendered;
        } catch (\Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    protected function renderPreview(string $view, array $parameters = []): string
    {
        $parameters['previewParentTemplate'] = $view;
        $parameters['previewContentReplacer'] = Preview::CONTENT_REPLACER;

        return parent::renderView('SuluWebsiteBundle:Preview:preview.html.twig', $parameters);
    }

    /**
     * Returns the current request from the request stack.
     *
     * @return null|Request
     *
     * @deprecated will be remove with 2.0
     */
    public function getRequest()
    {
        return $this->get('request_stack')->getCurrentRequest();
    }

    /**
     * @return null|CacheLifetimeEnhancer
     */
    protected function getCacheTimeLifeEnhancer(): ?CacheLifetimeEnhancer
    {
        if (!$this->has('sulu_http_cache.cache_lifetime.enhancer')) {
            return null;
        }

        return $this->get('sulu_http_cache.cache_lifetime.enhancer');
    }
}
